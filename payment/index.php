<?php
/**
 * TechZone - Router Halaman Pembayaran
 * Mengarahkan ke payment/qris.php, payment/transfer.php, atau payment/cod.php
 * sesuai metode pembayaran order, setelah memastikan order milik user yang login.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireUserLogin();

$orderNumber = trim($_GET['order'] ?? '');
if ($orderNumber === '') {
    redirectTo('account/orders.php');
}

$pdo  = getDbConnection();
$stmt = $pdo->prepare('SELECT id, payment_method FROM orders WHERE order_number = :num AND user_id = :uid LIMIT 1');
$stmt->execute([':num' => $orderNumber, ':uid' => (int) $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    setFlashMessage('error', 'Pesanan tidak ditemukan.');
    redirectTo('account/orders.php');
}

$target = match ($order['payment_method']) {
    'qris'     => 'payment/qris.php',
    'transfer' => 'payment/transfer.php',
    'cod'      => 'payment/cod.php',
    'midtrans' => 'payment/midtrans.php',
    default    => 'account/orders.php',
};

redirectTo($target . '?order=' . urlencode($orderNumber));
