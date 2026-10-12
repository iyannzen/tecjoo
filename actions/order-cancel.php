<?php
/**
 * TechZone - Batalkan Pesanan (oleh pembeli)
 *
 * Hanya diizinkan SELAMA pesanan belum dibayar:
 *   - payment_status = 'unpaid' (QRIS/Transfer yang belum diklaim/diverifikasi)
 *   - order_status   = 'waiting_payment'
 * Begitu status menunggu verifikasi / sudah lunas, pembeli tidak bisa membatalkan
 * sendiri lagi -- gunakan alur "Ajukan Pengembalian Dana" (actions/order-refund.php).
 *
 * Saat dibatalkan, stok produk yang sempat dikurangi saat checkout akan
 * dikembalikan (increment) agar tidak "hilang" dari inventori.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/notification_helpers.php';
require_once __DIR__ . '/../includes/automation_helpers.php';
require_once __DIR__ . '/../config/payment.php';

requireUserLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('account/orders.php');
}

verifyCsrfToken($_POST['csrf_token'] ?? null);

$userId      = (int) $_SESSION['user_id'];
$orderNumber = trim($_POST['order_number'] ?? '');
$reason      = trim($_POST['cancel_reason'] ?? '');

if ($orderNumber === '') {
    setFlashMessage('error', 'Nomor pesanan tidak valid.');
    redirectTo('account/orders.php');
}

$pdo = getDbConnection();

tzEnsureOrderHistoryTable($pdo);

// Pesanan Midtrans: pastikan belum dibayar & tutup transaksinya di Midtrans SEBELUM dibatalkan lokal,
// agar pembeli tidak bisa membayar pesanan yang sudah dibatalkan.
$preStmt = $pdo->prepare("SELECT order_number FROM orders WHERE order_number = :num AND user_id = :uid AND payment_method = 'midtrans' AND payment_status = 'unpaid' LIMIT 1");
$preStmt->execute([':num' => $orderNumber, ':uid' => $userId]);
if ($preStmt->fetchColumn()) {
    require_once __DIR__ . '/../includes/midtrans_helpers.php';
    $syncResult = midtransSyncOrder($pdo, $orderNumber);
    if (in_array($syncResult, ['paid', 'late_paid'], true)) {
        setFlashMessage('error', 'Pembayaran untuk pesanan ini ternyata sudah kami terima, sehingga tidak bisa dibatalkan. Silakan ajukan pengembalian dana jika diperlukan.');
        redirectTo('account/order-detail.php?order=' . urlencode($orderNumber));
    }
    if ($syncResult !== 'cancelled') {
        [$cCode, $cRes] = midtransCancelTransaction($orderNumber);
        $cStatus = (string) ($cRes['status_code'] ?? $cCode);
        if (!in_array($cStatus, ['200', '404'], true)) { // 404 = belum ada transaksi di Midtrans (hanya token)
            setFlashMessage('error', 'Pesanan belum bisa dibatalkan karena transaksi di Midtrans sedang diproses. Coba lagi beberapa saat.');
            redirectTo('account/order-detail.php?order=' . urlencode($orderNumber));
        }
    } else {
        setFlashMessage('success', 'Pesanan sudah dibatalkan.');
        redirectTo('account/order-detail.php?order=' . urlencode($orderNumber));
    }
}

try {
    $pdo->beginTransaction();

    $orderStmt = $pdo->prepare('SELECT * FROM orders WHERE order_number = :num AND user_id = :uid LIMIT 1 FOR UPDATE');
    $orderStmt->execute([':num' => $orderNumber, ':uid' => $userId]);
    $order = $orderStmt->fetch();

    if (!$order) {
        throw new RuntimeException('Pesanan tidak ditemukan.');
    }

    $cancellable = $order['order_status'] === 'waiting_payment'
        && in_array($order['payment_status'], ['unpaid'], true);

    if (!$cancellable) {
        throw new RuntimeException('Pesanan ini sudah tidak bisa dibatalkan sendiri karena sudah diproses/dibayar. Silakan ajukan pengembalian dana jika sudah terlanjur membayar.');
    }

    // Kembalikan stok produk dari item pesanan ini.
    $itemsStmt = $pdo->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = :id');
    $itemsStmt->execute([':id' => $order['id']]);
    foreach ($itemsStmt->fetchAll() as $item) {
        if ($item['product_id']) {
            $pdo->prepare('UPDATE products SET stock = stock + :qty WHERE id = :id')
                ->execute([':qty' => (int) $item['quantity'], ':id' => $item['product_id']]);
        }
    }

    $pdo->prepare(
        "UPDATE orders SET order_status = 'cancelled', payment_status = 'failed', cancel_reason = :reason, updated_at = NOW() WHERE id = :id"
    )->execute([':reason' => $reason !== '' ? $reason : null, ':id' => $order['id']]);

    $pdo->prepare("UPDATE payments SET status = 'failed', updated_at = NOW() WHERE order_id = :id AND status IN ('unpaid')")
        ->execute([':id' => $order['id']]);
    recordOrderStatus($pdo, (int) $order['id'], 'cancelled', $reason !== '' ? $reason : 'Dibatalkan pembeli');

    createNotification(
        $pdo,
        $userId,
        'Pesanan Dibatalkan',
        'Pesanan ' . $order['order_number'] . ' berhasil dibatalkan.' . ($reason !== '' ? ' Alasan: ' . $reason : ''),
        'order'
    );

    // Beri tahu semua admin.
    $adminStmt = $pdo->query("SELECT id FROM users WHERE role = 'admin'");
    foreach ($adminStmt->fetchAll() as $admin) {
        createNotification(
            $pdo,
            (int) $admin['id'],
            'Pesanan Dibatalkan Pembeli',
            'Pesanan ' . $order['order_number'] . ' dibatalkan oleh pembeli sebelum pembayaran.',
            'order'
        );
    }

    $pdo->commit();
    setFlashMessage('success', 'Pesanan berhasil dibatalkan.');
} catch (Throwable $e) {
    $pdo->rollBack();
    setFlashMessage('error', $e->getMessage() ?: 'Gagal membatalkan pesanan.');
}

redirectTo('account/order-detail.php?order=' . urlencode($orderNumber));
