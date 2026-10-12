<?php
/**
 * TechZone - Helper Keranjang Belanja
 *
 * Keranjang bekerja untuk pengunjung (guest, diidentifikasi via session_id)
 * maupun user yang sudah login (diidentifikasi via user_id). Saat guest
 * login/register, keranjangnya digabungkan (merge) ke keranjang akun user.
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

/**
 * Mengambil (atau membuat) baris cart untuk pengunjung saat ini.
 * Mengembalikan cart id.
 */
function getOrCreateCartId(PDO $pdo): int
{
    if (isUserLoggedIn()) {
        $userId = (int) $_SESSION['user_id'];

        $stmt = $pdo->prepare('SELECT id FROM carts WHERE user_id = :user_id LIMIT 1');
        $stmt->execute([':user_id' => $userId]);
        $cart = $stmt->fetch();

        if ($cart) {
            return (int) $cart['id'];
        }

        $insert = $pdo->prepare('INSERT INTO carts (user_id, created_at, updated_at) VALUES (:user_id, NOW(), NOW())');
        $insert->execute([':user_id' => $userId]);
        return (int) $pdo->lastInsertId();
    }

    // Guest: identifikasi via PHP session ID.
    $sessionId = session_id();

    $stmt = $pdo->prepare('SELECT id FROM carts WHERE session_id = :sid AND user_id IS NULL LIMIT 1');
    $stmt->execute([':sid' => $sessionId]);
    $cart = $stmt->fetch();

    if ($cart) {
        return (int) $cart['id'];
    }

    $insert = $pdo->prepare('INSERT INTO carts (session_id, created_at, updated_at) VALUES (:sid, NOW(), NOW())');
    $insert->execute([':sid' => $sessionId]);
    return (int) $pdo->lastInsertId();
}

/**
 * Mengambil seluruh item keranjang beserta data produk terkini (join products).
 * Harga yang ditampilkan adalah harga PRODUK SAAT INI (bukan harga beku),
 * agar konsisten dengan aturan "jangan percaya harga dari cache/frontend".
 */
function getCartItems(PDO $pdo, int $cartId): array
{
    $stmt = $pdo->prepare(
        'SELECT ci.id AS cart_item_id, ci.quantity,
                p.id AS product_id, p.slug, p.name, p.sku, p.brand, p.main_image,
                p.price, p.discount_price, p.stock, p.status
         FROM cart_items ci
         JOIN products p ON p.id = ci.product_id
         WHERE ci.cart_id = :cart_id
         ORDER BY ci.id DESC'
    );
    $stmt->execute([':cart_id' => $cartId]);
    return $stmt->fetchAll();
}

/** Menghitung total jumlah item (quantity) di keranjang, untuk badge navbar. */
function getCartItemCount(PDO $pdo, int $cartId): int
{
    $stmt = $pdo->prepare('SELECT COALESCE(SUM(quantity),0) FROM cart_items WHERE cart_id = :cart_id');
    $stmt->execute([':cart_id' => $cartId]);
    return (int) $stmt->fetchColumn();
}

/**
 * Menggabungkan keranjang guest (berdasarkan session_id) ke keranjang user
 * setelah user berhasil login/register. Kuantitas produk yang sama dijumlahkan,
 * dibatasi maksimal stok yang tersedia.
 */
function mergeGuestCartIntoUser(PDO $pdo, string $sessionId, int $userId): void
{
    $guestCartStmt = $pdo->prepare('SELECT id FROM carts WHERE session_id = :sid AND user_id IS NULL LIMIT 1');
    $guestCartStmt->execute([':sid' => $sessionId]);
    $guestCart = $guestCartStmt->fetch();

    if (!$guestCart) {
        return;
    }
    $guestCartId = (int) $guestCart['id'];

    $userCartStmt = $pdo->prepare('SELECT id FROM carts WHERE user_id = :uid LIMIT 1');
    $userCartStmt->execute([':uid' => $userId]);
    $userCart = $userCartStmt->fetch();

    if ($userCart) {
        $userCartId = (int) $userCart['id'];
    } else {
        $createStmt = $pdo->prepare('INSERT INTO carts (user_id, created_at, updated_at) VALUES (:uid, NOW(), NOW())');
        $createStmt->execute([':uid' => $userId]);
        $userCartId = (int) $pdo->lastInsertId();
    }

    $guestItemsStmt = $pdo->prepare('SELECT product_id, quantity, price FROM cart_items WHERE cart_id = :cid');
    $guestItemsStmt->execute([':cid' => $guestCartId]);
    $guestItems = $guestItemsStmt->fetchAll();

    foreach ($guestItems as $item) {
        $productStmt = $pdo->prepare('SELECT stock, price, discount_price FROM products WHERE id = :id LIMIT 1');
        $productStmt->execute([':id' => $item['product_id']]);
        $product = $productStmt->fetch();
        if (!$product) {
            continue;
        }

        $existingStmt = $pdo->prepare('SELECT id, quantity FROM cart_items WHERE cart_id = :cid AND product_id = :pid LIMIT 1');
        $existingStmt->execute([':cid' => $userCartId, ':pid' => $item['product_id']]);
        $existing = $existingStmt->fetch();

        $price = (!empty($product['discount_price']) && (float) $product['discount_price'] > 0)
            ? $product['discount_price'] : $product['price'];

        if ($existing) {
            $newQty = min((int) $existing['quantity'] + (int) $item['quantity'], (int) $product['stock']);
            $updateStmt = $pdo->prepare('UPDATE cart_items SET quantity = :qty, price = :price, updated_at = NOW() WHERE id = :id');
            $updateStmt->execute([':qty' => max(1, $newQty), ':price' => $price, ':id' => $existing['id']]);
        } else {
            $newQty = min((int) $item['quantity'], (int) $product['stock']);
            if ($newQty > 0) {
                $insertStmt = $pdo->prepare(
                    'INSERT INTO cart_items (cart_id, product_id, quantity, price, created_at, updated_at)
                     VALUES (:cid, :pid, :qty, :price, NOW(), NOW())'
                );
                $insertStmt->execute([
                    ':cid' => $userCartId, ':pid' => $item['product_id'], ':qty' => $newQty, ':price' => $price,
                ]);
            }
        }
    }

    // Hapus keranjang guest setelah digabungkan.
    $pdo->prepare('DELETE FROM cart_items WHERE cart_id = :cid')->execute([':cid' => $guestCartId]);
    $pdo->prepare('DELETE FROM carts WHERE id = :cid')->execute([':cid' => $guestCartId]);
}
