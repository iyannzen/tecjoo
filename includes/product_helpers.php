<?php
/**
 * TechZone - Helper Produk (dipakai di homepage, katalog, kategori, hasil pencarian)
 */
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';

/** Menghasilkan markup HTML rating bintang (Font Awesome) dari nilai rating 0-5. */
function renderStars(float $rating, string $size = 'text-xs'): string
{
    $rating = max(0, min(5, $rating));
    $full   = (int) floor($rating);
    $half   = ($rating - $full) >= 0.5;
    $empty  = 5 - $full - ($half ? 1 : 0);

    $html = '<span class="inline-flex items-center gap-0.5 ' . $size . ' text-amber-400">';
    for ($i = 0; $i < $full; $i++) {
        $html .= '<i class="fa-solid fa-star"></i>';
    }
    if ($half) {
        $html .= '<i class="fa-solid fa-star-half-stroke"></i>';
    }
    for ($i = 0; $i < $empty; $i++) {
        $html .= '<i class="fa-regular fa-star"></i>';
    }
    $html .= '</span>';

    return $html;
}

/**
 * Menghitung harga efektif (harga diskon jika ada, jika tidak harga normal).
 */
function effectivePrice(array $product): float
{
    if (!empty($product['discount_price']) && (float) $product['discount_price'] > 0
        && (float) $product['discount_price'] < (float) $product['price']) {
        return (float) $product['discount_price'];
    }
    return (float) $product['price'];
}

/** Menghitung persentase diskon (dibulatkan), 0 jika tidak ada diskon. */
function discountPercent(array $product): int
{
    $price    = (float) $product['price'];
    $discount = (float) ($product['discount_price'] ?? 0);
    if ($discount <= 0 || $discount >= $price || $price <= 0) {
        return 0;
    }
    return (int) round((1 - ($discount / $price)) * 100);
}

/** Menghitung ulang rating rata-rata & jumlah review (hanya status visible) sebuah produk. */
function recalcProductRating(PDO $pdo, int $productId): void
{
    $aggStmt = $pdo->prepare("SELECT AVG(rating) AS avg_rating, COUNT(*) AS total FROM reviews WHERE product_id = :pid AND status = 'visible'");
    $aggStmt->execute([':pid' => $productId]);
    $agg = $aggStmt->fetch();

    $pdo->prepare('UPDATE products SET rating = :rating, review_count = :count, updated_at = NOW() WHERE id = :pid')
        ->execute([':rating' => round((float) ($agg['avg_rating'] ?? 0), 2), ':count' => (int) $agg['total'], ':pid' => $productId]);
}
function productImageUrl(array $product): string
{
    $base  = techzoneBaseUrl();
    $image = $product['main_image'] ?? '';
    if ($image === '') {
        return $base . '/assets/images/placeholder-product.svg';
    }
    return $base . '/' . ltrim($image, '/');
}

/** Label ketersediaan stok. */
function stockLabel(int $stock): array
{
    if ($stock <= 0) {
        return ['Habis', 'text-red-600 bg-red-50'];
    }
    if ($stock <= 10) {
        return ['Stok Menipis', 'text-amber-600 bg-amber-50'];
    }
    return ['Tersedia', 'text-emerald-600 bg-emerald-50'];
}

/**
 * Render satu kartu produk (dipakai berulang di grid produk).
 * $product minimal berisi: id, slug, name, brand, main_image, price, discount_price, stock, rating, review_count
 */
function renderProductCard(array $product): void
{
    $base     = techzoneBaseUrl();
    $price    = effectivePrice($product);
    $discount = discountPercent($product);
    $rating   = (float) ($product['rating'] ?? 0);
    $reviews  = (int) ($product['review_count'] ?? 0);
    $stock    = (int) ($product['stock'] ?? 0);
    ?>
    <div class="group relative bg-white rounded-xl border border-gray-100 hover:border-cyan-300 card-hover overflow-hidden flex flex-col h-full">
      <a href="<?= e($base) ?>/product-detail.php?slug=<?= e($product['slug']) ?>" class="relative aspect-square bg-gradient-to-br from-gray-50 to-gray-100 overflow-hidden block">
        <img src="<?= e(productImageUrl($product)) ?>" alt="<?= e($product['name']) ?>" loading="lazy"
             class="w-full h-full object-cover group-hover:scale-110 transition duration-500 ease-out">
        <div class="absolute inset-0 bg-gradient-to-t from-black/15 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

        <div class="absolute top-2 left-2 flex flex-col gap-1">
          <?php if ($discount > 0): ?>
            <span class="bg-gradient-to-r from-red-500 to-orange-500 text-white text-[10px] font-bold px-2 py-1 rounded-full shadow-sm animate-pulse-glow w-fit">-<?= $discount ?>%</span>
          <?php endif; ?>
          <?php if ($rating >= 4.5 && $reviews >= 5): ?>
            <span class="bg-navy-900/90 text-cyan-400 text-[9px] font-bold px-2 py-1 rounded-full w-fit backdrop-blur-sm"><i class="fa-solid fa-fire mr-0.5"></i>Terlaris</span>
          <?php endif; ?>
        </div>

        <?php if ($stock <= 0): ?>
          <span class="absolute inset-0 bg-white/80 backdrop-blur-[1px] flex items-center justify-center text-xs font-bold text-red-600">Stok Habis</span>
        <?php elseif ($stock <= 5): ?>
          <span class="absolute bottom-2 left-2 bg-amber-500/95 text-white text-[9px] font-bold px-2 py-1 rounded-full">Sisa <?= $stock ?></span>
        <?php endif; ?>

        <span class="absolute bottom-2 right-2 w-8 h-8 rounded-full bg-white/95 text-cyan-600 flex items-center justify-center text-xs shadow-md opacity-0 translate-y-2 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300">
          <i class="fa-solid fa-eye"></i>
        </span>
      </a>

      <div class="p-3 flex flex-col flex-1">
        <p class="text-[11px] text-gray-400 uppercase tracking-wide mb-0.5"><?= e($product['brand'] ?? '') ?></p>
        <a href="<?= e($base) ?>/product-detail.php?slug=<?= e($product['slug']) ?>" class="text-sm font-semibold text-navy-900 line-clamp-2 mb-1 flex-1 hover:text-cyan-600 transition-colors"><?= e($product['name']) ?></a>
        <div class="rating-stars flex items-center gap-1 mb-1.5">
          <?= renderStars($rating) ?>
          <span class="text-[11px] text-gray-400">(<?= $reviews ?>)</span>
        </div>
        <div class="flex items-end justify-between gap-2">
          <div class="flex flex-col">
            <span class="text-sm font-bold text-navy-900"><?= e(formatRupiah($price)) ?></span>
            <?php if ($discount > 0): ?>
              <span class="text-[11px] text-gray-400 line-through"><?= e(formatRupiah($product['price'])) ?></span>
            <?php endif; ?>
          </div>
          <?php if ($stock > 0): ?>
          <form action="<?= e($base) ?>/actions/cart.php" method="POST" onclick="event.stopPropagation()">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
            <input type="hidden" name="quantity" value="1">
            <button type="submit" title="<?= isUserLoggedIn() ? 'Tambah ke keranjang' : 'Masuk untuk menambah ke keranjang' ?>"
                    class="ripple-wrap w-8 h-8 rounded-full bg-navy-900 hover:bg-cyan-500 text-white hover:text-navy-900 flex items-center justify-center text-xs shrink-0 transition-all duration-300 hover:scale-110 hover:rotate-6 shadow-sm">
              <i class="fa-solid fa-cart-plus"></i>
            </button>
          </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php
}


/* ======================================================================
 * Pencarian multi-kata, rekomendasi, dan "Terakhir Dilihat"
 * ====================================================================== */

/** Pecah kata kunci menjadi maksimal 5 kata (min. 2 huruf per kata). */
function tzSearchTerms(string $q): array
{
    $parts = preg_split('/\s+/u', trim($q), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $terms = [];
    foreach ($parts as $p) {
        if (mb_strlen($p) >= 2) {
            $terms[] = mb_substr($p, 0, 40);
        }
        if (count($terms) >= 5) {
            break;
        }
    }
    if (!$terms && trim($q) !== '') {
        $terms[] = mb_substr(trim($q), 0, 40);
    }
    return $terms;
}

/**
 * Bangun potongan WHERE: SETIAP kata harus cocok di salah satu kolom
 * (nama, SKU, brand, kategori, deskripsi singkat). Mengembalikan [sql, params].
 * Alias tabel: p = products, c = categories.
 */
function tzSearchWhere(string $q): array
{
    $clauses = [];
    $params  = [];
    foreach (tzSearchTerms($q) as $i => $term) {
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term) . '%';
        $k    = ':sq' . $i . '_';
        $clauses[] = "(p.name LIKE {$k}1 OR p.sku LIKE {$k}2 OR p.brand LIKE {$k}3 OR c.name LIKE {$k}4 OR p.short_description LIKE {$k}5)";
        for ($n = 1; $n <= 5; $n++) {
            $params[$k . $n] = $like;
        }
    }
    return [implode(' AND ', $clauses), $params];
}

/** Simpan produk yang baru dilihat ke cookie (maks. 12, 30 hari). Panggil SEBELUM output. */
function trackRecentlyViewed(int $productId): void
{
    $ids = array_values(array_filter(array_map('intval', explode(',', (string) ($_COOKIE['tz_recent'] ?? ''))), fn($v) => $v > 0));
    $ids = array_values(array_unique(array_merge([$productId], $ids)));
    $ids = array_slice($ids, 0, 12);
    if (!headers_sent()) {
        setcookie('tz_recent', implode(',', $ids), ['expires' => time() + 86400 * 30, 'path' => '/', 'samesite' => 'Lax']);
    }
    $_COOKIE['tz_recent'] = implode(',', $ids);
}

/** Ambil produk "Terakhir Dilihat" (tanpa $excludeId), urut dari yang paling baru dilihat. */
function getRecentlyViewedProducts(PDO $pdo, int $excludeId = 0, int $limit = 4): array
{
    $ids = array_values(array_filter(array_map('intval', explode(',', (string) ($_COOKIE['tz_recent'] ?? ''))), fn($v) => $v > 0 && $v !== $excludeId));
    $ids = array_slice(array_unique($ids), 0, 12);
    if (!$ids) {
        return [];
    }
    $ph   = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id, slug, name, brand, main_image, price, discount_price, stock, rating, review_count
                           FROM products WHERE status = 'active' AND id IN ($ph)");
    $stmt->execute($ids);
    $byId = [];
    foreach ($stmt->fetchAll() as $row) {
        $byId[(int) $row['id']] = $row;
    }
    $out = [];
    foreach ($ids as $id) {
        if (isset($byId[$id])) {
            $out[] = $byId[$id];
        }
        if (count($out) >= $limit) {
            break;
        }
    }
    return $out;
}

/** Produk terkait: kategori sama, diutamakan brand sama & harga paling dekat; sisa slot diisi produk populer lain. */
function getRelatedProducts(PDO $pdo, array $product, int $limit = 4): array
{
    $effective = effectivePrice($product);
    $stmt = $pdo->prepare(
        "SELECT id, slug, name, brand, main_image, price, discount_price, stock, rating, review_count
         FROM products
         WHERE category_id = :cat AND id <> :id AND status = 'active'
         ORDER BY (brand = :brand) DESC, (stock > 0) DESC, ABS(COALESCE(NULLIF(discount_price,0), price) - :price) ASC
         LIMIT " . (int) $limit
    );
    $stmt->execute([':cat' => $product['category_id'], ':id' => $product['id'], ':brand' => (string) $product['brand'], ':price' => $effective]);
    $rows = $stmt->fetchAll();

    if (count($rows) < $limit) {
        $skip = array_merge([(int) $product['id']], array_map(fn($r) => (int) $r['id'], $rows));
        $ph   = implode(',', array_fill(0, count($skip), '?'));
        $fill = $pdo->prepare(
            "SELECT id, slug, name, brand, main_image, price, discount_price, stock, rating, review_count
             FROM products WHERE status = 'active' AND stock > 0 AND id NOT IN ($ph)
             ORDER BY review_count DESC, rating DESC LIMIT " . (int) ($limit - count($rows))
        );
        $fill->execute($skip);
        $rows = array_merge($rows, $fill->fetchAll());
    }
    return $rows;
}

/** "Sering dibeli bersama": produk lain yang muncul di pesanan yang sama (pesanan tidak dibatalkan). */
function getFrequentlyBoughtTogether(PDO $pdo, int $productId, int $limit = 4): array
{
    try {
        $stmt = $pdo->prepare(
            "SELECT p.id, p.slug, p.name, p.brand, p.main_image, p.price, p.discount_price, p.stock, p.rating, p.review_count, COUNT(*) AS together
             FROM order_items a
             JOIN orders o ON o.id = a.order_id AND o.order_status NOT IN ('cancelled','returned')
             JOIN order_items b ON b.order_id = a.order_id AND b.product_id <> a.product_id
             JOIN products p ON p.id = b.product_id AND p.status = 'active' AND p.stock > 0
             WHERE a.product_id = :pid
             GROUP BY p.id, p.slug, p.name, p.brand, p.main_image, p.price, p.discount_price, p.stock, p.rating, p.review_count
             ORDER BY together DESC, p.review_count DESC
             LIMIT " . (int) $limit
        );
        $stmt->execute([':pid' => $productId]);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

/** Render satu baris produk (grid kartu) dengan judul seksi. */
function renderProductSection(string $title, string $icon, array $products, string $extraClass = 'mt-16'): void
{
    if (!$products) {
        return;
    }
    echo '<div class="' . e($extraClass) . '"><h2 class="reveal text-lg font-bold text-navy-900 mb-4 flex items-center gap-2"><i class="fa-solid ' . e($icon) . ' text-cyan-500"></i> ' . e($title) . '</h2>';
    echo '<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">';
    foreach ($products as $i => $p) {
        echo '<div class="reveal" style="transition-delay: ' . min($i * 60, 240) . 'ms">';
        renderProductCard($p);
        echo '</div>';
    }
    echo '</div></div>';
}
