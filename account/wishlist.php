<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/cart_helpers.php';
require_once __DIR__ . '/../includes/product_helpers.php';

requireUserLogin();
$userId = (int) $_SESSION['user_id'];
$pdo    = getDbConnection();

// Pindahkan ke keranjang.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'move_to_cart') {
    verifyCsrfToken($_POST['csrf_token'] ?? null);
    $productId = (int) ($_POST['product_id'] ?? 0);

    $productStmt = $pdo->prepare("SELECT id, price, discount_price, stock FROM products WHERE id = :id AND status = 'active' LIMIT 1");
    $productStmt->execute([':id' => $productId]);
    $product = $productStmt->fetch();

    if ($product && (int) $product['stock'] > 0) {
        $cartId = getOrCreateCartId($pdo);
        $price  = (!empty($product['discount_price']) && (float) $product['discount_price'] > 0) ? $product['discount_price'] : $product['price'];

        $existingStmt = $pdo->prepare('SELECT id, quantity FROM cart_items WHERE cart_id = :cid AND product_id = :pid LIMIT 1');
        $existingStmt->execute([':cid' => $cartId, ':pid' => $productId]);
        $existing = $existingStmt->fetch();

        if ($existing) {
            $newQty = min((int) $existing['quantity'] + 1, (int) $product['stock']);
            $pdo->prepare('UPDATE cart_items SET quantity = :qty, price = :price, updated_at = NOW() WHERE id = :id')
                ->execute([':qty' => $newQty, ':price' => $price, ':id' => $existing['id']]);
        } else {
            $pdo->prepare('INSERT INTO cart_items (cart_id, product_id, quantity, price, created_at, updated_at) VALUES (:cid, :pid, 1, :price, NOW(), NOW())')
                ->execute([':cid' => $cartId, ':pid' => $productId, ':price' => $price]);
        }

        $pdo->prepare('DELETE FROM wishlists WHERE user_id = :uid AND product_id = :pid')->execute([':uid' => $userId, ':pid' => $productId]);
        setFlashMessage('success', 'Produk dipindahkan ke keranjang.');
    } else {
        setFlashMessage('error', 'Produk tidak tersedia atau stok habis.');
    }
    redirectTo('account/wishlist.php');
}

$stmt = $pdo->prepare(
    "SELECT p.id, p.slug, p.name, p.brand, p.main_image, p.price, p.discount_price, p.stock, p.rating, p.review_count
     FROM wishlists w JOIN products p ON p.id = w.product_id
     WHERE w.user_id = :uid ORDER BY w.created_at DESC"
);
$stmt->execute([':uid' => $userId]);
$wishlist = $stmt->fetchAll();

$pageTitle = 'Wishlist Saya | TechZone';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
  <div>
      <h1 class="reveal text-xl font-extrabold text-navy-900 mb-6 flex items-center gap-2"><i class="fa-solid fa-heart text-red-500"></i> Wishlist Saya</h1>

      <?php if (empty($wishlist)): ?>
        <div class="reveal bg-white border border-gray-100 rounded-xl p-12 text-center shadow-card">
          <i class="fa-regular fa-heart text-4xl text-gray-200 mb-3 animate-float animate-float"></i>
          <p class="text-gray-500 text-sm mb-4">Belum ada produk favorit.</p>
          <a href="../products.php" class="inline-block ripple-wrap btn-glow bg-cyan-500 hover:bg-cyan-400 text-navy-900 font-semibold px-6 py-2.5 rounded-full transition">Jelajahi Produk</a>
        </div>
      <?php else: ?>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
          <?php foreach ($wishlist as $item): ?>
            <div class="reveal group bg-white rounded-xl border border-gray-100 overflow-hidden card-hover">
              <a href="../product-detail.php?slug=<?= e($item['slug']) ?>" class="block aspect-square bg-gray-50 overflow-hidden">
                <img src="<?= e(productImageUrl($item)) ?>" alt="<?= e($item['name']) ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
              </a>
              <div class="p-3">
                <a href="../product-detail.php?slug=<?= e($item['slug']) ?>" class="text-sm font-semibold text-navy-900 line-clamp-2 hover:text-cyan-600 transition-colors"><?= e($item['name']) ?></a>
                <p class="text-sm font-bold text-navy-900 mt-1"><?= e(formatRupiah(effectivePrice($item))) ?></p>
                <div class="flex items-center gap-2 mt-3">
                  <form method="POST" class="flex-1">
                    <?= csrfField() ?>
                    <input type="hidden" name="form_action" value="move_to_cart">
                    <input type="hidden" name="product_id" value="<?= (int) $item['id'] ?>">
                    <button type="submit" <?= (int)$item['stock'] <= 0 ? 'disabled' : '' ?>
                            class="w-full text-xs font-semibold bg-navy-900 text-white py-2 rounded-lg hover:bg-navy-800 transition disabled:opacity-40 disabled:cursor-not-allowed">
                      <?= (int)$item['stock'] <= 0 ? 'Stok Habis' : 'Ke Keranjang' ?>
                    </button>
                  </form>
                  <form action="../actions/wishlist.php" method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="product_id" value="<?= (int) $item['id'] ?>">
                    <button type="submit" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:text-red-500 hover:border-red-200 hover:scale-110 transition-all duration-200">
                      <i class="fa-solid fa-trash text-xs"></i>
                    </button>
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>