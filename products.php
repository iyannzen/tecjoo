<?php
/**
 * TechZone - Katalog Produk
 * Mendukung: pencarian (nama/SKU/brand/kategori/deskripsi), filter (kategori,
 * harga, brand, rating, ketersediaan), sorting, dan pagination.
 * Semua query menggunakan PDO prepared statement.
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/product_helpers.php';

$pdo = getDbConnection();

// ---------------------------------------------------------------------
// Ambil & sanitasi parameter dari query string
// ---------------------------------------------------------------------
$q          = trim($_GET['q'] ?? '');
$categorySlug = trim($_GET['kategori'] ?? '');
$brand      = trim($_GET['brand'] ?? '');
$priceMin   = isset($_GET['harga_min']) && $_GET['harga_min'] !== '' ? (float) $_GET['harga_min'] : null;
$priceMax   = isset($_GET['harga_max']) && $_GET['harga_max'] !== '' ? (float) $_GET['harga_max'] : null;
$ratingMin  = isset($_GET['rating_min']) && $_GET['rating_min'] !== '' ? (float) $_GET['rating_min'] : null;
$onlyStock  = isset($_GET['tersedia']) && $_GET['tersedia'] === '1';
$sort       = $_GET['sort'] ?? ($q !== '' ? 'relevan' : 'terbaru');
$page       = max(1, (int) ($_GET['page'] ?? 1));
$perPage    = 12;

$validSorts = ['relevan', 'terbaru', 'harga_termurah', 'harga_termahal', 'terpopuler', 'rating_tertinggi', 'az'];
if (!in_array($sort, $validSorts, true)) {
    $sort = 'terbaru';
}

// ---------------------------------------------------------------------
// Bangun query dinamis dengan prepared statement
// ---------------------------------------------------------------------
$where  = ["p.status = 'active'"];
$params = [];

// Pencarian multi-kata: SETIAP kata harus cocok di nama/SKU/brand/kategori/deskripsi singkat.
if ($q !== '') {
    [$searchSql, $searchParams] = tzSearchWhere($q);
    if ($searchSql !== '') {
        $where[] = '(' . $searchSql . ')';
        $params  = array_merge($params, $searchParams);
    }
}

if ($categorySlug !== '') {
    $where[]           = 'c.slug = :cat_slug';
    $params[':cat_slug'] = $categorySlug;
}

if ($brand !== '') {
    $where[]        = 'p.brand = :brand';
    $params[':brand'] = $brand;
}

if ($priceMin !== null) {
    $where[]            = '(COALESCE(NULLIF(p.discount_price,0), p.price)) >= :price_min';
    $params[':price_min'] = $priceMin;
}
if ($priceMax !== null) {
    $where[]            = '(COALESCE(NULLIF(p.discount_price,0), p.price)) <= :price_max';
    $params[':price_max'] = $priceMax;
}

if ($ratingMin !== null) {
    $where[]             = 'p.rating >= :rating_min';
    $params[':rating_min'] = $ratingMin;
}

if ($onlyStock) {
    $where[] = 'p.stock > 0';
}

$whereSql = implode(' AND ', $where);

if ($sort === 'relevan' && $q === '') {
    $sort = 'terbaru';
}
$orderParams = [];
$orderBy = match ($sort) {
    'relevan'          => '(p.name LIKE :rel_name) DESC, (p.brand LIKE :rel_brand) DESC, (p.stock > 0) DESC, p.review_count DESC, p.created_at DESC',
    'harga_termurah'   => '(COALESCE(NULLIF(p.discount_price,0), p.price)) ASC',
    'harga_termahal'   => '(COALESCE(NULLIF(p.discount_price,0), p.price)) DESC',
    'terpopuler'       => 'p.review_count DESC, p.rating DESC',
    'rating_tertinggi' => 'p.rating DESC, p.review_count DESC',
    'az'               => 'p.name ASC',
    default            => 'p.created_at DESC',
};

// Total data (untuk pagination)
$countSql  = "SELECT COUNT(*) FROM products p JOIN categories c ON c.id = p.category_id WHERE $whereSql";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalData  = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalData / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$dataSql = "SELECT p.id, p.slug, p.name, p.brand, p.main_image, p.price, p.discount_price, p.stock, p.rating, p.review_count
            FROM products p JOIN categories c ON c.id = p.category_id
            WHERE $whereSql
            ORDER BY $orderBy
            LIMIT :limit OFFSET :offset";
if ($sort === 'relevan') {
    $firstTerm = tzSearchTerms($q)[0] ?? $q;
    $orderParams[':rel_name']  = $firstTerm . '%';
    $orderParams[':rel_brand'] = $firstTerm . '%';
}
$dataStmt = $pdo->prepare($dataSql);
foreach (array_merge($params, $orderParams) as $key => $value) {
    $dataStmt->bindValue($key, $value);
}
$dataStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$dataStmt->execute();
$products = $dataStmt->fetchAll();

// ---------------------------------------------------------------------
// Data pendukung filter: kategori, daftar brand
// ---------------------------------------------------------------------
$categories = $pdo->query("SELECT id, name, slug FROM categories WHERE status = 'active' ORDER BY name ASC")->fetchAll();
$brands     = $pdo->query("SELECT DISTINCT brand FROM products WHERE status = 'active' AND brand IS NOT NULL AND brand <> '' ORDER BY brand ASC")->fetchAll(PDO::FETCH_COLUMN);

$activeCategoryName = '';
foreach ($categories as $c) {
    if ($c['slug'] === $categorySlug) {
        $activeCategoryName = $c['name'];
    }
}

function buildQueryUrl(array $override = []): string
{
    $current = $_GET;
    foreach ($override as $k => $v) {
        if ($v === null) {
            unset($current[$k]);
        } else {
            $current[$k] = $v;
        }
    }
    return 'products.php?' . http_build_query($current);
}

$pageTitle = ($q !== '' ? 'Hasil Pencarian "' . $q . '"' : ($activeCategoryName ?: 'Semua Produk')) . ' | TechZone';
$pageDescription = 'Jelajahi katalog perangkat IoT TechZone: mikrokontroler, sensor, modul komunikasi, automation, display, power module, dan IoT kit.';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<main class="pb-8">

  <!-- HERO BAR -->
  <section class="relative bg-navy-900 overflow-hidden">
    <div class="absolute inset-0 grid-pattern opacity-25"></div>
    <div class="absolute -top-10 left-10 w-56 h-56 bg-cyan-500/20 rounded-full blur-3xl animate-blob"></div>
    <div class="absolute -bottom-16 right-10 w-56 h-56 bg-blue-600/20 rounded-full blur-3xl animate-blob" style="animation-delay:3s"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
      <nav class="text-xs text-gray-400 mb-3 animate-fade-in-up">
        <a href="index.php" class="hover:text-cyan-400 transition-colors">Beranda</a> <span class="mx-1">/</span>
        <span class="text-gray-200 font-medium">Produk</span>
      </nav>

      <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div class="animate-fade-in-up delay-100">
          <h1 class="text-2xl sm:text-3xl font-extrabold text-white flex items-center gap-3">
            <?= $q !== '' ? 'Hasil pencarian "' . e($q) . '"' : e($activeCategoryName ?: 'Semua Produk') ?>
          </h1>
          <p class="text-sm text-gray-400 mt-1.5 flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 bg-navy-800/80 border border-navy-700 text-cyan-400 px-2.5 py-1 rounded-full text-xs font-semibold">
              <i class="fa-solid fa-box text-[10px]"></i> <?= $totalData ?> produk
            </span>
            ditemukan untuk kamu
          </p>
        </div>

        <form method="GET" class="animate-fade-in-up delay-200 flex items-center gap-2 bg-navy-800/80 border border-navy-700 rounded-full px-1 py-1">
          <?php foreach ($_GET as $k => $v) : if ($k === 'sort') continue; ?>
            <input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>">
          <?php endforeach; ?>
          <label class="text-xs text-gray-400 shrink-0 pl-3"><i class="fa-solid fa-arrow-down-wide-short mr-1"></i>Urutkan</label>
          <select name="sort" onchange="this.form.submit()"
                  class="text-sm bg-transparent text-white border-0 rounded-full pl-1 pr-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200 cursor-pointer">
            <?php if ($q !== ''): ?><option class="text-navy-900" value="relevan" <?= $sort === 'relevan' ? 'selected' : '' ?>>Paling Relevan</option><?php endif; ?>
            <option class="text-navy-900" value="terbaru" <?= $sort === 'terbaru' ? 'selected' : '' ?>>Terbaru</option>
            <option class="text-navy-900" value="harga_termurah" <?= $sort === 'harga_termurah' ? 'selected' : '' ?>>Harga Termurah</option>
            <option class="text-navy-900" value="harga_termahal" <?= $sort === 'harga_termahal' ? 'selected' : '' ?>>Harga Termahal</option>
            <option class="text-navy-900" value="terpopuler" <?= $sort === 'terpopuler' ? 'selected' : '' ?>>Terpopuler</option>
            <option class="text-navy-900" value="rating_tertinggi" <?= $sort === 'rating_tertinggi' ? 'selected' : '' ?>>Rating Tertinggi</option>
            <option class="text-navy-900" value="az" <?= $sort === 'az' ? 'selected' : '' ?>>A-Z</option>
          </select>
        </form>
      </div>
    </div>

    <svg class="relative block w-full text-gray-50" viewBox="0 0 1440 24" fill="currentColor" preserveAspectRatio="none" style="height:18px">
      <path d="M0,24 C240,4 480,24 720,10 C960,-2 1200,20 1440,6 L1440,24 L0,24 Z"></path>
    </svg>
  </section>

  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">

  <!-- ACTIVE FILTER CHIPS -->
  <?php
    $activeChips = [];
    if ($categorySlug !== '') $activeChips[] = ['label' => $activeCategoryName, 'url' => buildQueryUrl(['kategori' => null, 'page' => null]), 'icon' => 'fa-tag'];
    if ($brand !== '') $activeChips[] = ['label' => $brand, 'url' => buildQueryUrl(['brand' => null, 'page' => null]), 'icon' => 'fa-industry'];
    if ($priceMin !== null || $priceMax !== null) $activeChips[] = ['label' => 'Harga: ' . ($priceMin !== null ? 'Rp' . number_format($priceMin,0,',','.') : '0') . ' - ' . ($priceMax !== null ? 'Rp' . number_format($priceMax,0,',','.') : '∞'), 'url' => buildQueryUrl(['harga_min' => null, 'harga_max' => null, 'page' => null]), 'icon' => 'fa-coins'];
    if ($ratingMin !== null) $activeChips[] = ['label' => $ratingMin . '+ Rating', 'url' => buildQueryUrl(['rating_min' => null, 'page' => null]), 'icon' => 'fa-star'];
    if ($onlyStock) $activeChips[] = ['label' => 'Tersedia saja', 'url' => buildQueryUrl(['tersedia' => null, 'page' => null]), 'icon' => 'fa-circle-check'];
  ?>
  <?php if (!empty($activeChips)): ?>
    <div class="reveal flex flex-wrap items-center gap-2 mb-6">
      <span class="text-xs text-gray-400 font-medium">Filter aktif:</span>
      <?php foreach ($activeChips as $chip): ?>
        <a href="<?= e($chip['url']) ?>" class="group inline-flex items-center gap-1.5 bg-cyan-50 border border-cyan-200 text-cyan-700 text-xs font-medium pl-3 pr-2 py-1.5 rounded-full hover:bg-cyan-100 transition-all duration-200">
          <i class="fa-solid <?= $chip['icon'] ?> text-[10px]"></i> <?= e($chip['label']) ?>
          <i class="fa-solid fa-xmark text-[10px] ml-0.5 group-hover:rotate-90 transition-transform duration-200"></i>
        </a>
      <?php endforeach; ?>
      <a href="products.php" class="text-xs text-gray-400 hover:text-red-500 font-medium underline transition-colors">Hapus semua</a>
    </div>
  <?php endif; ?>

  <div class="grid grid-cols-1 md:grid-cols-4 gap-6">

    <!-- SIDEBAR FILTER -->
    <aside id="kategori" class="md:col-span-1">
      <button id="techzone-filter-toggle"
              class="md:hidden w-full flex items-center justify-between bg-navy-900 text-white rounded-lg px-4 py-3 text-sm font-medium mb-3 shadow-glow">
        <span><i class="fa-solid fa-sliders mr-2 text-cyan-400"></i>Filter Produk</span>
        <i class="fa-solid fa-chevron-down transition-transform duration-300" id="techzone-filter-chevron"></i>
      </button>

      <form id="techzone-filter-form" method="GET" class="hidden md:block bg-white border border-gray-100 rounded-xl p-5 space-y-6 sticky top-20 shadow-card">
        <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= e($q) ?>"><?php endif; ?>

        <div>
          <h3 class="text-sm font-bold text-navy-900 mb-3 flex items-center gap-2"><i class="fa-solid fa-layer-group text-cyan-500 text-xs"></i>Kategori</h3>
          <ul class="space-y-1 text-sm">
            <li>
              <a href="<?= e(buildQueryUrl(['kategori' => null, 'page' => null])) ?>"
                 class="flex items-center justify-between px-2.5 py-1.5 rounded-lg transition-all duration-200 <?= $categorySlug === '' ? 'bg-cyan-50 text-cyan-700 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:pl-3.5' ?>">
                Semua Kategori
              </a>
            </li>
            <?php foreach ($categories as $cat): ?>
            <li>
              <a href="<?= e(buildQueryUrl(['kategori' => $cat['slug'], 'page' => null])) ?>"
                 class="flex items-center justify-between px-2.5 py-1.5 rounded-lg transition-all duration-200 <?= $categorySlug === $cat['slug'] ? 'bg-cyan-50 text-cyan-700 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:pl-3.5' ?>">
                <?= e($cat['name']) ?>
                <?php if ($categorySlug === $cat['slug']): ?><i class="fa-solid fa-check text-[10px]"></i><?php endif; ?>
              </a>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>

        <div class="pt-5 border-t border-gray-100">
          <h3 class="text-sm font-bold text-navy-900 mb-3 flex items-center gap-2"><i class="fa-solid fa-coins text-cyan-500 text-xs"></i>Rentang Harga</h3>
          <div class="flex items-center gap-2">
            <input type="number" name="harga_min" value="<?= e((string)($priceMin ?? '')) ?>" placeholder="Min"
                   class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-all duration-200">
            <span class="text-gray-400">-</span>
            <input type="number" name="harga_max" value="<?= e((string)($priceMax ?? '')) ?>" placeholder="Maks"
                   class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-all duration-200">
          </div>
        </div>

        <div class="pt-5 border-t border-gray-100">
          <h3 class="text-sm font-bold text-navy-900 mb-3 flex items-center gap-2"><i class="fa-solid fa-industry text-cyan-500 text-xs"></i>Brand</h3>
          <select name="brand" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-all duration-200">
            <option value="">Semua Brand</option>
            <?php foreach ($brands as $b): ?>
              <option value="<?= e($b) ?>" <?= $brand === $b ? 'selected' : '' ?>><?= e($b) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="pt-5 border-t border-gray-100">
          <h3 class="text-sm font-bold text-navy-900 mb-3 flex items-center gap-2"><i class="fa-solid fa-star text-cyan-500 text-xs"></i>Rating Minimum</h3>
          <div class="space-y-2 text-sm">
            <?php foreach ([4, 3, 2, 1] as $r): ?>
              <label class="flex items-center gap-2 cursor-pointer group px-1 py-0.5 rounded-lg hover:bg-gray-50 transition-colors">
                <input type="radio" name="rating_min" value="<?= $r ?>" <?= (int)$ratingMin === $r ? 'checked' : '' ?> class="accent-cyan-500">
                <span class="rating-stars"><?= renderStars((float) $r) ?></span><span class="text-gray-500 group-hover:text-navy-900 transition-colors">ke atas</span>
              </label>
            <?php endforeach; ?>
            <label class="flex items-center gap-2 cursor-pointer group px-1 py-0.5 rounded-lg hover:bg-gray-50 transition-colors">
              <input type="radio" name="rating_min" value="" <?= $ratingMin === null ? 'checked' : '' ?> class="accent-cyan-500">
              <span class="text-gray-500 group-hover:text-navy-900 transition-colors">Semua Rating</span>
            </label>
          </div>
        </div>

        <div class="pt-5 border-t border-gray-100">
          <label class="flex items-center gap-2 text-sm cursor-pointer">
            <input type="checkbox" name="tersedia" value="1" <?= $onlyStock ? 'checked' : '' ?> class="accent-cyan-500 w-4 h-4">
            Hanya tampilkan yang tersedia
          </label>
        </div>

        <div class="flex gap-2 pt-1">
          <button type="submit" class="ripple-wrap btn-glow flex-1 bg-cyan-500 hover:bg-cyan-400 text-navy-900 text-sm font-semibold py-2.5 rounded-lg transition">
            Terapkan Filter
          </button>
          <a href="products.php" class="flex-1 text-center border border-gray-200 text-gray-600 text-sm font-medium py-2.5 rounded-lg hover:bg-gray-50 hover:border-gray-300 transition-all">
            Reset
          </a>
        </div>
      </form>
    </aside>

    <!-- GRID PRODUK -->
    <div class="md:col-span-3">
      <?php if (empty($products)): ?>
        <div class="reveal bg-white border border-gray-100 rounded-2xl p-14 text-center shadow-card">
          <div class="w-20 h-20 mx-auto rounded-full bg-cyan-50 flex items-center justify-center mb-4">
            <i class="fa-solid fa-box-open text-3xl text-cyan-300 animate-float"></i>
          </div>
          <p class="text-navy-900 font-semibold text-sm mb-1">Belum ada produk yang cocok</p>
          <p class="text-gray-500 text-sm">Coba ubah kata kunci atau filter pencarian Anda.</p>
          <?php if ($q !== '' && !empty($categories)): ?>
            <p class="text-xs text-gray-400 mt-5 mb-2">Coba jelajahi kategori:</p>
            <div class="flex flex-wrap justify-center gap-2">
              <?php foreach (array_slice($categories, 0, 6) as $sc): ?>
                <a href="products.php?kategori=<?= e($sc['slug']) ?>" class="text-xs font-semibold px-3 py-1.5 rounded-full border border-cyan-200 bg-cyan-50 text-cyan-700 hover:bg-cyan-100 transition-colors"><?= e($sc['name']) ?></a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <a href="products.php" class="ripple-wrap btn-glow inline-block mt-5 bg-cyan-500 hover:bg-cyan-400 text-navy-900 text-sm font-semibold px-6 py-2.5 rounded-full transition">Lihat semua produk</a>
        </div>
      <?php else: ?>
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
          <?php foreach ($products as $i => $product): ?>
            <div class="reveal" style="transition-delay: <?= min($i * 50, 300) ?>ms">
              <?php renderProductCard($product); ?>
            </div>
          <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="flex items-center justify-center gap-1.5 mt-10">
          <?php if ($page > 1): ?>
            <a href="<?= e(buildQueryUrl(['page' => $page - 1])) ?>" class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-cyan-50 hover:border-cyan-300 hover:text-cyan-600 transition-all">
              <i class="fa-solid fa-chevron-left text-xs"></i>
            </a>
          <?php endif; ?>

          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="<?= e(buildQueryUrl(['page' => $i])) ?>"
               class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium transition-all <?= $i === $page ? 'bg-cyan-500 text-navy-900 shadow-glow scale-105' : 'border border-gray-200 text-gray-600 hover:bg-cyan-50 hover:border-cyan-300 hover:text-cyan-600' ?>">
              <?= $i ?>
            </a>
          <?php endfor; ?>

          <?php if ($page < $totalPages): ?>
            <a href="<?= e(buildQueryUrl(['page' => $page + 1])) ?>" class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-cyan-50 hover:border-cyan-300 hover:text-cyan-600 transition-all">
              <i class="fa-solid fa-chevron-right text-xs"></i>
            </a>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
  </div>
</main>
<script>
  document.getElementById('techzone-filter-toggle')?.addEventListener('click', function () {
    document.getElementById('techzone-filter-form')?.classList.toggle('hidden');
    document.getElementById('techzone-filter-chevron')?.classList.toggle('rotate-180');
  });
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
