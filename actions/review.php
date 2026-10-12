<?php
/**
 * TechZone - Proses Review Produk (backend)
 * Review hanya dapat diberikan untuk produk dari order milik user yang
 * BERSTATUS SELESAI, dan hanya sekali per (order, produk).
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/product_helpers.php';

requireUserLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('account/orders.php');
}

verifyCsrfToken($_POST['csrf_token'] ?? null);

$userId      = (int) $_SESSION['user_id'];
$orderNumber = trim($_POST['order_number'] ?? '');
$productId   = (int) ($_POST['product_id'] ?? 0);
$rating      = (int) ($_POST['rating'] ?? 0);
$reviewText  = trim($_POST['review'] ?? '');

if ($rating < 1 || $rating > 5) {
    setFlashMessage('error', 'Rating harus antara 1 sampai 5.');
    redirectTo('account/order-detail.php?order=' . urlencode($orderNumber));
}

$pdo = getDbConnection();

$orderStmt = $pdo->prepare("SELECT id FROM orders WHERE order_number = :num AND user_id = :uid AND order_status = 'completed' LIMIT 1");
$orderStmt->execute([':num' => $orderNumber, ':uid' => $userId]);
$order = $orderStmt->fetch();

if (!$order) {
    setFlashMessage('error', 'Review hanya dapat diberikan untuk pesanan yang berstatus Selesai.');
    redirectTo('account/order-detail.php?order=' . urlencode($orderNumber));
}

// Pastikan produk ini memang bagian dari order tersebut.
$itemCheck = $pdo->prepare('SELECT id FROM order_items WHERE order_id = :oid AND product_id = :pid LIMIT 1');
$itemCheck->execute([':oid' => $order['id'], ':pid' => $productId]);
if (!$itemCheck->fetch()) {
    setFlashMessage('error', 'Produk tidak ditemukan pada pesanan ini.');
    redirectTo('account/order-detail.php?order=' . urlencode($orderNumber));
}

// Cegah review ganda untuk kombinasi order+produk yang sama (juga dijaga UNIQUE KEY di database).
$existingCheck = $pdo->prepare('SELECT id FROM reviews WHERE order_id = :oid AND product_id = :pid AND user_id = :uid LIMIT 1');
$existingCheck->execute([':oid' => $order['id'], ':pid' => $productId, ':uid' => $userId]);
if ($existingCheck->fetch()) {
    setFlashMessage('error', 'Anda sudah memberikan review untuk produk ini pada pesanan tersebut.');
    redirectTo('account/order-detail.php?order=' . urlencode($orderNumber));
}

try {
    $pdo->beginTransaction();

    $pdo->prepare(
        "INSERT INTO reviews (product_id, user_id, order_id, rating, review, status, created_at, updated_at)
         VALUES (:pid, :uid, :oid, :rating, :review, 'visible', NOW(), NOW())"
    )->execute([
        ':pid' => $productId, ':uid' => $userId, ':oid' => $order['id'], ':rating' => $rating, ':review' => $reviewText,
    ]);

    // Hitung ulang rating rata-rata & jumlah review produk (hanya yang status visible).
    recalcProductRating($pdo, $productId);

    $pdo->commit();

    logActivity($userId, 'submit_review', 'Memberikan review untuk produk #' . $productId . ' pada order ' . $orderNumber);
    setFlashMessage('success', 'Terima kasih! Review Anda berhasil disimpan.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('[TechZone][Review Error] ' . $e->getMessage());
    setFlashMessage('error', 'Terjadi kesalahan saat menyimpan review.');
}

redirectTo('account/order-detail.php?order=' . urlencode($orderNumber));
