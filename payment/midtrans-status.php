<?php
/**
 * TechZone - Endpoint JSON status pembayaran Midtrans (dipakai polling di halaman bayar).
 * Hanya untuk pemilik order yang sedang login.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/midtrans_helpers.php';

header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'unauthorized']);
    exit;
}
session_write_close(); // jangan kunci session selama memanggil API eksternal

$orderNumber = trim($_GET['order'] ?? '');
$pdo = getDbConnection();
$st  = $pdo->prepare("SELECT payment_status, order_status FROM orders WHERE order_number = :n AND user_id = :u AND payment_method = 'midtrans' LIMIT 1");
$st->execute([':n' => $orderNumber, ':u' => (int) $_SESSION['user_id']]);
$o = $st->fetch();
if (!$o) {
    http_response_code(404);
    echo json_encode(['status' => 'not_found']);
    exit;
}

if ($o['payment_status'] === 'unpaid' && $o['order_status'] === 'waiting_payment') {
    midtransSyncOrder($pdo, $orderNumber);
    $st->execute([':n' => $orderNumber, ':u' => (int) $_SESSION['user_id']]);
    $o = $st->fetch();
}

echo json_encode(['status' => $o['payment_status'], 'order_status' => $o['order_status']]);
