<?php
/**
 * TechZone - Ajukan Pengembalian Dana (oleh pembeli)
 *
 * Untuk pesanan yang SUDAH terlanjur dibayar (payment_status = 'paid') dan
 * belum berstatus selesai/dibatalkan/sudah diajukan sebelumnya. Order akan
 * berpindah ke status 'refund_requested' menunggu tinjauan admin (lihat
 * admin/order-detail.php untuk proses persetujuan/penolakan).
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
$reason      = trim($_POST['refund_reason'] ?? '');

if ($orderNumber === '') {
    setFlashMessage('error', 'Nomor pesanan tidak valid.');
    redirectTo('account/orders.php');
}
if ($reason === '') {
    setFlashMessage('error', 'Mohon isi alasan pengembalian dana.');
    redirectTo('account/order-detail.php?order=' . urlencode($orderNumber));
}

$pdo = getDbConnection();

tzEnsureOrderHistoryTable($pdo);

try {
    $pdo->beginTransaction();

    $orderStmt = $pdo->prepare('SELECT * FROM orders WHERE order_number = :num AND user_id = :uid LIMIT 1 FOR UPDATE');
    $orderStmt->execute([':num' => $orderNumber, ':uid' => $userId]);
    $order = $orderStmt->fetch();

    if (!$order) {
        throw new RuntimeException('Pesanan tidak ditemukan.');
    }

    $notAllowedStatuses = ['cancelled', 'refund_requested', 'returned'];
    $refundable = $order['payment_status'] === 'paid' && !in_array($order['order_status'], $notAllowedStatuses, true);

    if (!$refundable) {
        throw new RuntimeException('Pesanan ini belum memenuhi syarat untuk pengajuan pengembalian dana.');
    }

    $pdo->prepare(
        "UPDATE orders SET order_status = 'refund_requested', refund_reason = :reason, updated_at = NOW() WHERE id = :id"
    )->execute([':reason' => $reason, ':id' => $order['id']]);
    recordOrderStatus($pdo, (int) $order['id'], 'refund_requested', $reason);

    createNotification(
        $pdo,
        $userId,
        'Pengajuan Pengembalian Dana Diterima',
        'Pengajuan pengembalian dana untuk pesanan ' . $order['order_number'] . ' sedang ditinjau tim kami.',
        'order'
    );

    $adminStmt = $pdo->query("SELECT id FROM users WHERE role = 'admin'");
    foreach ($adminStmt->fetchAll() as $admin) {
        createNotification(
            $pdo,
            (int) $admin['id'],
            'Permintaan Pengembalian Dana',
            'Pesanan ' . $order['order_number'] . ' mengajukan pengembalian dana. Alasan: ' . $reason,
            'order'
        );
    }

    $pdo->commit();
    setFlashMessage('success', 'Pengajuan pengembalian dana berhasil dikirim. Tim kami akan meninjau dalam 1-3 hari kerja.');
} catch (Throwable $e) {
    $pdo->rollBack();
    setFlashMessage('error', $e->getMessage() ?: 'Gagal mengajukan pengembalian dana.');
}

redirectTo('account/order-detail.php?order=' . urlencode($orderNumber));
