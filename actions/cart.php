<?php
/**
 * TechZone - Proses Keranjang Belanja (backend)
 * Mendukung: add, update, remove, apply_coupon, remove_coupon.
 * Semua harga & stok divalidasi ulang dari database (tidak percaya input frontend).
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/cart_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('cart.php');
}

verifyCsrfToken($_POST['csrf_token'] ?? null);

$pdo    = getDbConnection();
$cartId = getOrCreateCartId($pdo);
$action = $_POST['action'] ?? '';

switch ($action) {

    case 'add': {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $quantity  = max(1, (int) ($_POST['quantity'] ?? 1));
        $redirectAfter = ($_GET['redirect'] ?? '') === 'checkout' ? 'checkout.php' : 'cart.php';

        $stmt = $pdo->prepare("SELECT id, price, discount_price, stock FROM products WHERE id = :id AND status = 'active' LIMIT 1");
        $stmt->execute([':id' => $productId]);
        $product = $stmt->fetch();

        if (!$product) {
            setFlashMessage('error', 'Produk tidak ditemukan.');
            redirectTo('products.php');
        }

        $existingStmt = $pdo->prepare('SELECT id, quantity FROM cart_items WHERE cart_id = :cid AND product_id = :pid LIMIT 1');
        $existingStmt->execute([':cid' => $cartId, ':pid' => $productId]);
        $existing = $existingStmt->fetch();

        $requestedQty = $existing ? ((int) $existing['quantity'] + $quantity) : $quantity;

        if ($requestedQty > (int) $product['stock']) {
            setFlashMessage('error', 'Jumlah produk melebihi stok yang tersedia.');
            redirectTo($_SERVER['HTTP_REFERER'] ?? 'products.php');
        }

        $price = (!empty($product['discount_price']) && (float) $product['discount_price'] > 0)
            ? $product['discount_price'] : $product['price'];

        if ($existing) {
            $update = $pdo->prepare('UPDATE cart_items SET quantity = :qty, price = :price, updated_at = NOW() WHERE id = :id');
            $update->execute([':qty' => $requestedQty, ':price' => $price, ':id' => $existing['id']]);
        } else {
            $insert = $pdo->prepare(
                'INSERT INTO cart_items (cart_id, product_id, quantity, price, created_at, updated_at)
                 VALUES (:cid, :pid, :qty, :price, NOW(), NOW())'
            );
            $insert->execute([':cid' => $cartId, ':pid' => $productId, ':qty' => $requestedQty, ':price' => $price]);
        }

        setFlashMessage('success', 'Produk berhasil ditambahkan ke keranjang.');
        redirectTo($redirectAfter);
    }

    case 'update': {
        $cartItemId = (int) ($_POST['cart_item_id'] ?? 0);
        $quantity   = (int) ($_POST['quantity'] ?? 1);

        $itemStmt = $pdo->prepare(
            'SELECT ci.id, ci.product_id, p.stock, p.price, p.discount_price
             FROM cart_items ci JOIN products p ON p.id = ci.product_id
             WHERE ci.id = :id AND ci.cart_id = :cid LIMIT 1'
        );
        $itemStmt->execute([':id' => $cartItemId, ':cid' => $cartId]);
        $item = $itemStmt->fetch();

        if (!$item) {
            setFlashMessage('error', 'Item keranjang tidak ditemukan.');
            redirectTo('cart.php');
        }

        if ($quantity <= 0) {
            $pdo->prepare('DELETE FROM cart_items WHERE id = :id')->execute([':id' => $cartItemId]);
            setFlashMessage('success', 'Produk dihapus dari keranjang.');
            redirectTo('cart.php');
        }

        if ($quantity > (int) $item['stock']) {
            setFlashMessage('error', 'Jumlah produk melebihi stok yang tersedia.');
            redirectTo('cart.php');
        }

        $price = (!empty($item['discount_price']) && (float) $item['discount_price'] > 0)
            ? $item['discount_price'] : $item['price'];

        $update = $pdo->prepare('UPDATE cart_items SET quantity = :qty, price = :price, updated_at = NOW() WHERE id = :id');
        $update->execute([':qty' => $quantity, ':price' => $price, ':id' => $cartItemId]);

        setFlashMessage('success', 'Jumlah produk berhasil diperbarui.');
        redirectTo('cart.php');
    }

    case 'remove': {
        $cartItemId = (int) ($_POST['cart_item_id'] ?? 0);
        $pdo->prepare('DELETE FROM cart_items WHERE id = :id AND cart_id = :cid')
            ->execute([':id' => $cartItemId, ':cid' => $cartId]);

        setFlashMessage('success', 'Produk dihapus dari keranjang.');
        redirectTo('cart.php');
    }

    case 'apply_coupon': {
        $code = strtoupper(trim($_POST['coupon_code'] ?? ''));

        if ($code === '') {
            setFlashMessage('error', 'Masukkan kode kupon terlebih dahulu.');
            redirectTo('cart.php');
        }

        $items    = getCartItems($pdo, $cartId);
        $subtotal = 0.0;
        foreach ($items as $it) {
            $p = (!empty($it['discount_price']) && (float) $it['discount_price'] > 0) ? $it['discount_price'] : $it['price'];
            $subtotal += $p * (int) $it['quantity'];
        }

        $couponStmt = $pdo->prepare("SELECT * FROM coupons WHERE code = :code AND status = 'active' LIMIT 1");
        $couponStmt->execute([':code' => $code]);
        $coupon = $couponStmt->fetch();

        $today = date('Y-m-d');

        if (!$coupon) {
            setFlashMessage('error', 'Kode kupon tidak ditemukan atau sudah tidak aktif.');
            redirectTo('cart.php');
        } elseif ($coupon['start_date'] && $coupon['start_date'] > $today) {
            setFlashMessage('error', 'Kupon belum berlaku.');
            redirectTo('cart.php');
        } elseif ($coupon['end_date'] && $coupon['end_date'] < $today) {
            setFlashMessage('error', 'Kupon sudah kedaluwarsa.');
            redirectTo('cart.php');
        } elseif ($coupon['usage_limit'] !== null && (int) $coupon['used_count'] >= (int) $coupon['usage_limit']) {
            setFlashMessage('error', 'Kupon sudah mencapai batas penggunaan.');
            redirectTo('cart.php');
        } elseif ($subtotal < (float) $coupon['minimum_purchase']) {
            setFlashMessage('error', 'Minimum pembelian untuk kupon ini adalah ' . formatRupiah($coupon['minimum_purchase']) . '.');
            redirectTo('cart.php');
        }

        $discount = $coupon['type'] === 'percentage'
            ? $subtotal * ((float) $coupon['value'] / 100)
            : (float) $coupon['value'];

        if (!empty($coupon['maximum_discount']) && (float) $coupon['maximum_discount'] > 0) {
            $discount = min($discount, (float) $coupon['maximum_discount']);
        }
        $discount = min($discount, $subtotal);

        $_SESSION['applied_coupon'] = [
            'id'     => (int) $coupon['id'],
            'code'   => $coupon['code'],
            'amount' => round($discount, 2),
        ];

        setFlashMessage('success', 'Kupon "' . $coupon['code'] . '" berhasil diterapkan.');
        redirectTo('cart.php');
    }

    case 'remove_coupon': {
        unset($_SESSION['applied_coupon']);
        setFlashMessage('success', 'Kupon dibatalkan.');
        redirectTo('cart.php');
    }

    default:
        redirectTo('cart.php');
}
