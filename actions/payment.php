<?php
/**
 * TechZone - Proses Klaim Pembayaran (backend)
 *
 * PENTING: Endpoint ini TIDAK PERNAH langsung mengubah status menjadi
 * "Lunas". Klik "Saya Sudah Membayar" hanya memindahkan status menjadi
 * "Menunggu Verifikasi" — status "Lunas" hanya dapat ditetapkan oleh admin
 * melalui admin/payments.php (lihat actions/payment.php tidak menerima
 * parameter status dari klien; nilai akhir selalu ditentukan oleh server).
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/payment_helpers.php';
require_once __DIR__ . '/../includes/automation_helpers.php';

requireUserLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('account/orders.php');
}

verifyCsrfToken($_POST['csrf_token'] ?? null);

$userId      = (int) $_SESSION['user_id'];
$action      = $_POST['action'] ?? '';
$orderNumber = trim($_POST['order_number'] ?? '');

if ($action !== 'mark_paid' || $orderNumber === '') {
    redirectTo('account/orders.php');
}

$pdo = getDbConnection();

$orderStmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = :num AND user_id = :uid AND payment_method = 'qris' LIMIT 1");
$orderStmt->execute([':num' => $orderNumber, ':uid' => $userId]);
$order = $orderStmt->fetch();

if (!$order) {
    setFlashMessage('error', 'Pesanan tidak ditemukan.');
    redirectTo('account/orders.php');
}

$paymentStmt = $pdo->prepare('SELECT * FROM payments WHERE order_id = :id ORDER BY id DESC LIMIT 1');
$paymentStmt->execute([':id' => $order['id']]);
$payment = $paymentStmt->fetch();

[$order, $payment] = autoExpirePaymentIfNeeded($pdo, $order, $payment);

if ($payment['status'] !== 'unpaid') {
    setFlashMessage('error', 'Status pembayaran ini sudah tidak dapat diklaim ulang.');
    redirectTo('payment/qris.php?order=' . urlencode($orderNumber));
}

try {
    $pdo->beginTransaction();

    $pdo->prepare("UPDATE payments SET status = 'waiting_verification', updated_at = NOW() WHERE id = :id")
        ->execute([':id' => $payment['id']]);
    $pdo->prepare("UPDATE orders SET payment_status = 'waiting_verification', updated_at = NOW() WHERE id = :id")
        ->execute([':id' => $order['id']]);

    $pdo->commit();

    notifyAdmins($pdo, 'Pembayaran Masuk', 'Pembayaran QRIS untuk pesanan ' . $orderNumber . ' menunggu verifikasi Anda.', 'payment');
    logActivity($userId, 'claim_payment', 'User mengklaim sudah membayar untuk order ' . $orderNumber . ' (QRIS).');
    setFlashMessage('success', 'Klaim pembayaran diterima. Menunggu verifikasi admin.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[TechZone][Mark Paid Error] ' . $e->getMessage());
    setFlashMessage('error', 'Terjadi kesalahan. Silakan coba kembali.');
}

redirectTo('payment/qris.php?order=' . urlencode($orderNumber));
