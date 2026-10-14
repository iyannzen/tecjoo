<?php
/**
 * TechZone - Proses Wishlist (backend)
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('products.php');
}

verifyCsrfToken($_POST['csrf_token'] ?? null);

requireUserLoginForAction('Silakan masuk terlebih dahulu untuk menggunakan wishlist.');

$pdo       = getDbConnection();
$userId    = (int) $_SESSION['user_id'];
$productId = (int) ($_POST['product_id'] ?? 0);
$action    = $_POST['action'] ?? 'toggle';

$productStmt = $pdo->prepare("SELECT id FROM products WHERE id = :id AND status = 'active' LIMIT 1");
$productStmt->execute([':id' => $productId]);
if (!$productStmt->fetch()) {
    setFlashMessage('error', 'Produk tidak ditemukan.');
    redirectTo($_SERVER['HTTP_REFERER'] ?? 'products.php');
}

$existingStmt = $pdo->prepare('SELECT id FROM wishlists WHERE user_id = :uid AND product_id = :pid LIMIT 1');
$existingStmt->execute([':uid' => $userId, ':pid' => $productId]);
$existing = $existingStmt->fetch();

if ($action === 'remove' || ($action === 'toggle' && $existing)) {
    $pdo->prepare('DELETE FROM wishlists WHERE user_id = :uid AND product_id = :pid')
        ->execute([':uid' => $userId, ':pid' => $productId]);
    setFlashMessage('success', 'Produk dihapus dari wishlist.');
} elseif (!$existing) {
    $pdo->prepare('INSERT INTO wishlists (user_id, product_id, created_at) VALUES (:uid, :pid, NOW())')
        ->execute([':uid' => $userId, ':pid' => $productId]);
    setFlashMessage('success', 'Produk ditambahkan ke wishlist.');
}

redirectTo($_SERVER['HTTP_REFERER'] ?? 'account/wishlist.php');
