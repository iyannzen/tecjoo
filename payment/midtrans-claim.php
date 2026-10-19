<?php
/**
 * TechZone - Klaim pembayaran Midtrans (JSON, dipanggil dari halaman bayar).
 *
 * Alur: user memilih metode di popup Snap (GoPay, BCA VA, dll) lalu menekan bayar ->
 * endpoint ini memindahkan pembayaran ke "Menunggu Verifikasi" dan memberi notifikasi ke admin.
 * Status "Lunas" TIDAK pernah ditetapkan di sini; hanya admin (admin/payments.php) yang
 * dapat mengonfirmasi. Parameter dari klien hanya dipakai sebagai label channel (disaring).
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/midtrans_helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'method_not_allowed']);
    exit;
}
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'unauthorized']);
    exit;
}

verifyCsrfToken($_POST['csrf_token'] ?? null);

$userId      = (int) $_SESSION['user_id'];
$orderNumber = trim($_POST['order'] ?? '');
$source      = $_POST['source'] ?? '';          // success | pending | close
$clientType  = substr(preg_replace('/[^a-z_]/', '', strtolower((string) ($_POST['payment_type'] ?? ''))), 0, 30);
$clientBank  = substr(preg_replace('/[^a-z0-9 ]/', '', strtolower((string) ($_POST['bank'] ?? ''))), 0, 20);

$pdo = getDbConnection();
$st  = $pdo->prepare("SELECT * FROM orders WHERE order_number = :n AND user_id = :u AND payment_method = 'midtrans' LIMIT 1");
$st->execute([':n' => $orderNumber, ':u' => $userId]);
$order = $st->fetch();
if (!$order) {
    http_response_code(404);
    echo json_encode(['status' => 'not_found']);
    exit;
}

// Hanya pesanan yang masih menunggu pembayaran yang dapat diklaim.
if (!($order['payment_status'] === 'unpaid' && $order['order_status'] === 'waiting_payment')) {
    echo json_encode(['status' => $order['payment_status'], 'order_status' => $order['order_status']]);
    exit;
}

session_write_close();

// Label channel: utamakan data dari server Midtrans, fallback ke pilihan di popup.
$channel = null;
$confirmedByMidtrans = false;
if (midtransIsConfigured()) {
    [$code, $res] = midtransGetStatus($orderNumber);
    if ($code === 200 && is_array($res) && !empty($res['transaction_status']) && ($res['status_code'] ?? '') !== '404') {
        $confirmedByMidtrans = in_array($res['transaction_status'], ['pending', 'settlement', 'capture'], true);
        $channel = midtransChannelLabel($res);
    }
}

// Ditutup tanpa memilih metode (belum ada transaksi di Midtrans) -> bukan klaim.
if ($source === 'close' && !$confirmedByMidtrans) {
    echo json_encode(['status' => 'unpaid']);
    exit;
}
if (!in_array($source, ['success', 'pending', 'close'], true)) {
    http_response_code(400);
    echo json_encode(['status' => 'bad_request']);
    exit;
}

if ($channel === null && $clientType !== '') {
    $channel = midtransChannelLabel(['payment_type' => $clientType, 'va_numbers' => $clientBank !== '' ? [['bank' => $clientBank]] : []]);
}

$paymentStmt = $pdo->prepare('SELECT * FROM payments WHERE order_id = :id ORDER BY id DESC LIMIT 1');
$paymentStmt->execute([':id' => $order['id']]);
$payment = $paymentStmt->fetch();
if (!$payment) {
    http_response_code(404);
    echo json_encode(['status' => 'not_found']);
    exit;
}

try {
    $pdo->beginTransaction();

    $upd = $pdo->prepare("UPDATE payments SET status = 'waiting_verification', updated_at = NOW() WHERE id = :id AND status = 'unpaid'");
    $upd->execute([':id' => $payment['id']]);
    $changed = $upd->rowCount() > 0;

    if ($changed) {
        $pdo->prepare("UPDATE orders SET payment_status = 'waiting_verification', payment_channel = COALESCE(:ch, payment_channel), updated_at = NOW() WHERE id = :id AND payment_status = 'unpaid'")
            ->execute([':ch' => $channel, ':id' => $order['id']]);
    }
    $pdo->commit();

    if ($changed) {
        notifyAdmins(
            $pdo,
            'Pembayaran Masuk',
            'Pembayaran Midtrans' . ($channel ? ' (' . $channel . ')' : '') . ' untuk pesanan ' . $orderNumber . ' (' . formatRupiah($order['total']) . ') menunggu konfirmasi Anda.',
            'payment',
            'admin/payments.php?status=waiting_verification'
        );
        logActivity($userId, 'claim_payment', 'User mengklaim pembayaran Midtrans untuk order ' . $orderNumber . ($channel ? ' (' . $channel . ')' : '') . '.');
    }
    echo json_encode(['status' => 'waiting_verification']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[TechZone][Midtrans Claim] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error']);
}
