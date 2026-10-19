<?php
/**
 * TechZone - Detail Produk
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/product_helpers.php';

$slug = trim($_GET['slug'] ?? '');
if ($slug === '') {
    redirectTo('products.php');
}

$pdo  = getDbConnection();
$stmt = $pdo->prepare("SELECT p.*, c.name AS category_name, c.slug AS category_slug
                        FROM products p JOIN categories c ON c.id = p.category_id
                        WHERE p.slug = :slug AND p.status = 'active' LIMIT 1");
$stmt->execute([':slug' => $slug]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$specifications = json_decode($product['specifications'] ?? '[]', true) ?: [];
$features       = json_decode($product['features'] ?? '[]', true) ?: [];
$applications   = json_decode($product['applications'] ?? '[]', true) ?: [];
$compatibility  = json_decode($product['compatibility'] ?? '[]', true) ?: [];
$packageContents = json_decode($product['package_contents'] ?? '[]', true) ?: [];

$galleryStmt = $pdo->prepare('SELECT image FROM product_images WHERE product_id = :id ORDER BY sort_order ASC');
$galleryStmt->execute([':id' => $product['id']]);
$galleryImages = array_column($galleryStmt->fetchAll(), 'image');
array_unshift($galleryImages, $product['main_image']);
$galleryImages = array_values(array_unique(array_filter($galleryImages)));

// Rekomendasi: terkait (kategori/brand/harga serupa), sering dibeli bersama, dan terakhir dilihat.
$relatedProducts  = getRelatedProducts($pdo, $product, 4);
$boughtTogether   = getFrequentlyBoughtTogether($pdo, (int) $product['id'], 4);
$recentlyViewed   = getRecentlyViewedProducts($pdo, (int) $product['id'], 4); // ambil dulu sebelum produk ini dicatat
trackRecentlyViewed((int) $product['id']);

$reviewStmt = $pdo->prepare("SELECT r.rating, r.review, r.created_at, u.name AS user_name
                              FROM reviews r JOIN users u ON u.id = r.user_id
                              WHERE r.product_id = :id AND r.status = 'visible'
                              ORDER BY r.created_at DESC LIMIT 20");
$reviewStmt->execute([':id' => $product['id']]);
$reviews = $reviewStmt->fetchAll();

$price    = effectivePrice($product);
$discount = discountPercent($product);
[$stockText, $stockClass] = stockLabel((int) $product['stock']);

$inWishlist = false;
if (isUserLoggedIn()) {
    $wishStmt = $pdo->prepare('SELECT id FROM wishlists WHERE user_id = :uid AND product_id = :pid LIMIT 1');
    $wishStmt->execute([':uid' => $_SESSION['user_id'], ':pid' => $product['id']]);
    $inWishlist = (bool) $wishStmt->fetch();
}

$pageTitle       = e($product['name']) . ' | TechZone';
$pageDescription = $product['short_description'] ?: mb_substr(strip_tags($product['description']), 0, 155);
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

  <nav class="reveal text-xs text-gray-500 mb-6 flex items-center flex-wrap gap-1">
    <a href="index.php" class="hover:text-cyan-600 transition-colors">Beranda</a> <i class="fa-solid fa-chevron-right text-[8px] text-gray-300 mx-0.5"></i>
    <a href="products.php" class="hover:text-cyan-600 transition-colors">Produk</a> <i class="fa-solid fa-chevron-right text-[8px] text-gray-300 mx-0.5"></i>
    <a href="products.php?kategori=<?= e($product['category_slug']) ?>" class="hover:text-cyan-600 transition-colors"><?= e($product['category_name']) ?></a> <i class="fa-solid fa-chevron-right text-[8px] text-gray-300 mx-0.5"></i>
    <span class="text-navy-900 font-medium truncate max-w-[200px]"><?= e($product['name']) ?></span>
  </nav>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">

    <!-- GALERI -->
    <div>
      <div class="reveal aspect-square bg-gray-50 rounded-2xl overflow-hidden border border-gray-100 mb-3 group shadow-card">
        <img id="techzone-main-image" src="<?= e(techzoneBaseUrl() . '/' . ltrim($galleryImages[0] ?? '', '/')) ?>"
             alt="<?= e($product['name']) ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
      </div>
      <?php if (count($galleryImages) > 1): ?>
      <div class="grid grid-cols-5 gap-2">
        <?php foreach ($galleryImages as $img): ?>
          <button type="button"
                  class="techzone-thumb aspect-square rounded-lg overflow-hidden border-2 border-transparent hover:border-cyan-400 transition"
                  data-src="<?= e(techzoneBaseUrl() . '/' . ltrim($img, '/')) ?>">
            <img src="<?= e(techzoneBaseUrl() . '/' . ltrim($img, '/')) ?>" class="w-full h-full object-cover" alt="">
          </button>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- INFO PRODUK -->
    <div class="reveal delay-100">
      <div class="flex items-center justify-between mb-1">
        <p class="text-xs text-cyan-600 font-semibold uppercase tracking-wide"><?= e($product['brand']) ?></p>
        <span class="text-[11px] text-gray-400">SKU: <?= e($product['sku']) ?></span>
      </div>
      <h1 class="text-2xl font-extrabold text-navy-900 mb-2 leading-snug"><?= e($product['name']) ?></h1>

      <div class="flex items-center gap-2 mb-4">
        <span class="rating-stars flex items-center gap-1 bg-amber-50 px-2 py-1 rounded-lg">
          <?= renderStars((float) $product['rating'], 'text-sm') ?>
          <span class="text-xs font-semibold text-amber-700 ml-0.5"><?= (float) $product['rating'] ?></span>
        </span>
        <span class="text-sm text-gray-500">(<?= (int) $product['review_count'] ?> ulasan)</span>
      </div>

      <div class="relative overflow-hidden bg-white border border-gray-200 rounded-xl p-5 mb-4">
        <div class="relative flex items-baseline gap-3 flex-wrap">
          <span class="text-3xl font-extrabold text-navy-900"><?= e(formatRupiah($price)) ?></span>
          <?php if ($discount > 0): ?>
            <span class="text-base text-gray-400 line-through"><?= e(formatRupiah($product['price'])) ?></span>
            <span class="badge-pop bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full">Hemat <?= $discount ?>%</span>
          <?php endif; ?>
        </div>
        <span class="relative inline-block text-xs font-semibold px-2.5 py-1 rounded-full mt-3 transition-all <?= $stockClass ?>">
          <?= $stockText ?> <?= (int)$product['stock'] > 0 ? '(' . (int)$product['stock'] . ' unit)' : '' ?>
        </span>
      </div>

      <p class="text-sm text-gray-600 leading-relaxed mb-6"><?= e($product['short_description']) ?></p>

      <?php /* Aksi utama (Chat, Tambah ke Keranjang, Beli Sekarang) ada di bar bawah yang ikut scroll (.tz-mbar). Di sini hanya jumlah + wishlist. */ ?>
      <div class="flex items-center flex-wrap gap-x-5 gap-y-3 mb-6">
      <?php if ((int) $product['stock'] > 0): ?>
      <form id="techzone-cart-form" action="<?= e(techzoneBaseUrl()) ?>/actions/cart.php" method="POST" class="inline-flex">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
        <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden">
          <button type="button" onclick="techzoneChangeQty(-1)" class="w-9 h-10 text-gray-500 hover:text-navy-900 hover:bg-gray-100 transition-colors">-</button>
          <input type="number" name="quantity" id="techzone-qty" value="1" min="1" max="<?= (int)$product['stock'] ?>"
                 class="w-12 text-center text-sm border-x border-gray-300 h-10 focus:outline-none">
          <button type="button" onclick="techzoneChangeQty(1)" class="w-9 h-10 text-gray-500 hover:text-navy-900 hover:bg-gray-100 transition-colors">+</button>
        </div>
      </form>
      <?php else: ?>
        <div class="bg-gray-100 text-gray-500 text-sm font-medium text-center py-2.5 px-4 rounded-lg">Stok produk ini sedang habis</div>
      <?php endif; ?>
      <form action="<?= e(techzoneBaseUrl()) ?>/actions/wishlist.php" method="POST" class="inline">
        <?= csrfField() ?>
        <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
        <input type="hidden" name="action" value="toggle">
        <button type="submit" class="text-sm <?= $inWishlist ? 'text-red-500' : 'text-gray-500 hover:text-red-500' ?> transition-all hover:scale-105">
          <i class="fa-<?= $inWishlist ? 'solid' : 'regular' ?> fa-heart mr-1.5 <?= $inWishlist ? 'animate-scale-in' : '' ?>"></i><?= $inWishlist ? 'Tersimpan di Wishlist' : 'Simpan ke Wishlist' ?>
        </button>
      </form>
      </div>

      <?php if (!isUserLoggedIn()): ?>
        <p class="text-xs text-gray-500 -mt-3 mb-5"><i class="fa-solid fa-lock mr-1 text-cyan-600"></i>Masuk dulu untuk menambah ke keranjang, membeli, atau menyimpan ke wishlist. <a href="<?= e(techzoneBaseUrl()) ?>/login.php" class="text-cyan-600 font-semibold hover:underline">Masuk</a></p>
      <?php endif; ?>

      <div class="mt-6 pt-6 border-t border-gray-100 grid grid-cols-2 gap-3 text-xs text-gray-600">
        <div class="flex items-center gap-2 bg-gray-50 rounded-lg px-3 py-2"><i class="fa-solid fa-weight-hanging w-4 text-cyan-500"></i> Berat: <?= (int)$product['weight'] ?> gram</div>
        <div class="flex items-center gap-2 bg-gray-50 rounded-lg px-3 py-2"><i class="fa-solid fa-ruler-combined w-4 text-cyan-500"></i> Dimensi: <?= e($product['dimensions']) ?></div>
        <div class="flex items-center gap-2 bg-gray-50 rounded-lg px-3 py-2"><i class="fa-solid fa-shield-halved w-4 text-cyan-500"></i> Garansi kesesuaian</div>
        <div class="flex items-center gap-2 bg-gray-50 rounded-lg px-3 py-2"><i class="fa-solid fa-truck-fast w-4 text-cyan-500"></i> Dikirim cepat</div>
      </div>
    </div>
  </div>

  <!-- TABS -->
  <div class="reveal mt-14 bg-white rounded-2xl border border-gray-100 shadow-card p-6 sm:p-8">
    <div id="techzone-tabs" class="flex flex-wrap gap-1 border-b border-gray-200 mb-6 -mx-1">
      <?php
      $tabs = [
          'deskripsi'     => 'Deskripsi',
          'spesifikasi'   => 'Spesifikasi',
          'fitur'         => 'Fitur',
          'kegunaan'      => 'Kegunaan',
          'kompatibilitas'=> 'Kompatibilitas',
          'isi-paket'     => 'Isi Paket',
          'review'        => 'Review (' . count($reviews) . ')',
      ];
      $first = true;
      foreach ($tabs as $key => $label):
      ?>
        <button type="button" data-tab="<?= $key ?>"
                class="techzone-tab-btn px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-all duration-200 rounded-t-lg
                <?= $first ? 'border-cyan-500 text-cyan-600 bg-cyan-50/50' : 'border-transparent text-gray-500 hover:text-navy-900 hover:bg-gray-50' ?>">
          <?= e($label) ?>
        </button>
      <?php $first = false; endforeach; ?>
    </div>

    <div id="tab-deskripsi" class="techzone-tab-panel animate-fade-in">
      <p class="text-sm text-gray-600 leading-relaxed whitespace-pre-line max-w-3xl"><?= e($product['description']) ?></p>
    </div>

    <div id="tab-spesifikasi" class="techzone-tab-panel hidden">
      <div class="max-w-2xl divide-y divide-gray-100 rounded-lg overflow-hidden border border-gray-100">
          <?php foreach ($specifications as $key => $value): ?>
          <div class="flex text-sm hover:bg-cyan-50/30 transition-colors">
            <div class="py-2.5 px-4 text-gray-500 w-1/3 bg-gray-50/60"><?= e($key) ?></div>
            <div class="py-2.5 px-4 font-medium text-navy-900"><?= e($value) ?></div>
          </div>
          <?php endforeach; ?>
      </div>
    </div>

    <div id="tab-fitur" class="techzone-tab-panel hidden">
      <ul class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 max-w-2xl">
        <?php foreach ($features as $f): ?>
          <li class="flex items-start gap-2 text-sm text-gray-600 bg-gray-50 rounded-lg px-3 py-2 hover:bg-cyan-50 transition-colors"><i class="fa-solid fa-circle-check text-cyan-500 mt-0.5"></i><?= e($f) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div id="tab-kegunaan" class="techzone-tab-panel hidden">
      <ul class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 max-w-2xl">
        <?php foreach ($applications as $a): ?>
          <li class="flex items-start gap-2 text-sm text-gray-600 bg-gray-50 rounded-lg px-3 py-2 hover:bg-cyan-50 transition-colors"><i class="fa-solid fa-bolt text-cyan-500 mt-0.5"></i><?= e($a) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div id="tab-kompatibilitas" class="techzone-tab-panel hidden">
      <div class="flex flex-wrap gap-2 max-w-2xl">
        <?php foreach ($compatibility as $c): ?>
          <span class="bg-gray-100 text-gray-700 text-xs font-medium px-3 py-1.5 rounded-full hover:bg-cyan-100 hover:text-cyan-700 transition-colors"><?= e($c) ?></span>
        <?php endforeach; ?>
      </div>
    </div>

    <div id="tab-isi-paket" class="techzone-tab-panel hidden">
      <ul class="space-y-2 max-w-2xl">
        <?php foreach ($packageContents as $pkg): ?>
          <li class="flex items-start gap-2 text-sm text-gray-600 bg-gray-50 rounded-lg px-3 py-2 hover:bg-cyan-50 transition-colors"><i class="fa-solid fa-box text-cyan-500 mt-0.5"></i><?= e($pkg) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div id="tab-review" class="techzone-tab-panel hidden">
      <?php if (empty($reviews)): ?>
        <p class="text-sm text-gray-500">Belum ada review untuk produk ini.</p>
      <?php else: ?>
        <div class="space-y-3 max-w-2xl">
          <?php foreach ($reviews as $r): ?>
            <div class="border border-gray-100 rounded-xl p-4 hover:border-cyan-200 hover:shadow-sm transition-all duration-200">
              <div class="flex items-center gap-3 mb-1.5">
                <span class="w-8 h-8 rounded-full bg-gradient-to-br from-cyan-400 to-blue-500 text-white text-xs font-bold flex items-center justify-center shrink-0"><?= e(strtoupper(substr($r['user_name'], 0, 1))) ?></span>
                <div class="flex-1">
                  <p class="text-sm font-semibold text-navy-900"><?= e($r['user_name']) ?></p>
                  <span class="text-[11px] text-gray-400"><?= e(formatTanggalIndo($r['created_at'])) ?></span>
                </div>
                <?= renderStars((float) $r['rating']) ?>
              </div>
              <p class="text-sm text-gray-600 mt-1.5 pl-11"><?= e($r['review']) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <p class="text-xs text-gray-400 mt-4">Review hanya dapat diberikan oleh pembeli dengan status pesanan Selesai.</p>
    </div>
  </div>

  <!-- PRODUK TERKAIT -->
  <?php renderProductSection('Sering Dibeli Bersama', 'fa-people-carry-box', $boughtTogether); ?>
  <?php renderProductSection('Produk Terkait', 'fa-shapes', $relatedProducts); ?>
  <?php renderProductSection('Terakhir Dilihat', 'fa-clock-rotate-left', $recentlyViewed); ?>
</main>

<?php $mbChat = techzoneBaseUrl() . '/chat.php?product=' . rawurlencode($product['slug']); ?>
<div class="tz-mbar" role="group" aria-label="Aksi cepat produk">
  <div class="tz-mbar__in">
    <a href="<?= e($mbChat) ?>" id="techzone-chat-btn" class="tz-mbar__chat"><i class="fa-regular fa-comments"></i><span>Chat</span></a>
    <?php if ((int) $product['stock'] > 0): ?>
      <button type="submit" form="techzone-cart-form" class="tz-mbar__cart" aria-label="Tambah ke keranjang"><i class="fa-solid fa-cart-plus"></i><span class="tz-mbar__cart-text">Tambah ke Keranjang</span></button>
      <button type="submit" form="techzone-cart-form" formaction="<?= e(techzoneBaseUrl()) ?>/actions/cart.php?redirect=checkout" class="tz-mbar__buy">Beli Sekarang</button>
    <?php else: ?>
      <span class="tz-mbar__out">Stok habis</span>
    <?php endif; ?>
  </div>
</div>

<script>
  function techzoneChangeQty(delta) {
    var input = document.getElementById('techzone-qty');
    var max = parseInt(input.max || '999', 10);
    var value = parseInt(input.value || '1', 10) + delta;
    if (value < 1) value = 1;
    if (value > max) value = max;
    input.value = value;
  }

  document.querySelectorAll('.techzone-thumb').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.getElementById('techzone-main-image').src = this.dataset.src;
    });
  });

  document.querySelectorAll('.techzone-tab-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.techzone-tab-btn').forEach(b => b.classList.remove('border-cyan-500', 'text-cyan-600'));
      document.querySelectorAll('.techzone-tab-btn').forEach(b => b.classList.add('border-transparent', 'text-gray-500'));
      this.classList.remove('border-transparent', 'text-gray-500');
      this.classList.add('border-cyan-500', 'text-cyan-600');

      document.querySelectorAll('.techzone-tab-panel').forEach(p => p.classList.add('hidden'));
      document.getElementById('tab-' + this.dataset.tab)?.classList.remove('hidden');
    });
  });
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
