<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/product_helpers.php';
require_once __DIR__ . '/config/database.php';

$pdo = getDbConnection();

$categoryIcons = [
    'mikrokontroler'   => 'fa-microchip',
    'sensor'           => 'fa-satellite-dish',
    'modul-komunikasi' => 'fa-tower-broadcast',
    'smart-home'       => 'fa-house-signal',
    'relay-automation' => 'fa-toggle-on',
    'motor-aktuator'   => 'fa-gears',
    'display'          => 'fa-display',
    'power-module'     => 'fa-bolt',
    'iot-kit'          => 'fa-box-open',
    'aksesori'         => 'fa-plug',
];

$categories = $pdo->query(
    "SELECT c.id, c.name, c.slug, COUNT(p.id) AS total_produk
     FROM categories c LEFT JOIN products p ON p.category_id = c.id AND p.status = 'active'
     WHERE c.status = 'active'
     GROUP BY c.id ORDER BY c.name ASC"
)->fetchAll();

$featuredProducts = $pdo->query(
    "SELECT id, slug, name, brand, main_image, price, discount_price, stock, rating, review_count
     FROM products WHERE status = 'active' AND featured = 1 ORDER BY created_at DESC LIMIT 8"
)->fetchAll();

$recentlyViewed = getRecentlyViewedProducts($pdo, 0, 4);
$newestProducts = $pdo->query(
    "SELECT id, slug, name, brand, main_image, price, discount_price, stock, rating, review_count
     FROM products WHERE status = 'active' ORDER BY created_at DESC LIMIT 8"
)->fetchAll();

$activeBanners = $pdo->query(
    "SELECT * FROM banners WHERE status = 'active' ORDER BY sort_order ASC LIMIT 3"
)->fetchAll();

$pageTitle       = 'TechZone - Toko Perangkat IoT Terpercaya';
$pageDescription = 'Temukan berbagai perangkat IoT untuk smart home, automation, monitoring, robotics, dan pengembangan proyek teknologi di TechZone.';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<main>
  <!-- HERO -->
  <section class="tz-hero">
    <div class="tz-stage__grid grid-pattern absolute inset-0" style="z-index:-1;opacity:.5"></div>
    <div class="tz-stage__beam" style="opacity:.6"></div>
    <div class="tz-stage__orb tz-stage__orb--1"></div>
    <div class="tz-stage__orb tz-stage__orb--2"></div>
    <canvas class="tz-particles" data-particles></canvas>

    <div class="tz-hero__inner">
      <div class="tz-hero__copy">
        <span class="tz-hero__chip animate-fade-in-up"><b></b> Teknologi IoT untuk Dunia yang Lebih Terhubung</span>
        <h1>
          <span data-split data-split-delay="250">Bangun Teknologi Pintar Bersama</span>
          <span class="brand animate-fade-in-up delay-700"><span class="gradient-text">TechZone</span></span>
        </h1>
        <p class="tz-hero__lead animate-fade-in-up delay-500">
          Temukan berbagai perangkat IoT untuk smart home, automation, monitoring, robotics,
          dan pengembangan proyek teknologi.
        </p>
        <div class="tz-hero__cta animate-fade-in-up delay-700">
          <a href="products.php" class="ripple-wrap btn-glow tz-btn-gold">Belanja Sekarang <i class="fa-solid fa-arrow-right-long"></i></a>
          <a href="#kategori-section" class="tz-btn-ghost">Lihat Produk <i class="fa-solid fa-arrow-down text-xs"></i></a>
        </div>
      </div>

      <div class="tz-hv animate-scale-in delay-300" data-tilt>
        <div class="tz-hv__glow"></div>
        <div class="tz-hv__ring tz-hv__ring--3"></div>
        <div class="tz-hv__ring tz-hv__ring--1"></div>
        <div class="tz-hv__ring tz-hv__ring--2"></div>
        <span class="tz-hv__node"></span><span class="tz-hv__node"></span><span class="tz-hv__node"></span><span class="tz-hv__node"></span>
        <div class="tz-hv__chip"><div class="tz-hv__die"><i class="fa-solid fa-microchip"></i></div></div>

        <div class="tz-hv__card tz-hv__card--1"><i class="fa-solid fa-shield-halved"></i><div><b>Transaksi Aman</b><small>QRIS, Transfer Bank, atau COD</small></div></div>
        <div class="tz-hv__card tz-hv__card--2"><i class="fa-solid fa-truck-fast"></i><div><b>Seluruh Indonesia</b><small>Reguler &amp; Express tersedia</small></div></div>
        <div class="tz-hv__card tz-hv__card--3"><i class="fa-solid fa-headset"></i><div><b>Dukungan Pelanggan</b><small>Siap membantu proyek IoT Anda</small></div></div>
      </div>
    </div>
  </section>

  <!-- MARQUEE KATEGORI -->
  <?php if (!empty($categories)): ?>
  <div class="tz-marquee" aria-hidden="true">
    <div class="tz-marquee__track">
      <?php for ($rep = 0; $rep < 4; $rep++): foreach ($categories as $cat): ?>
        <span class="tz-marquee__item"><i class="fa-solid <?= e($categoryIcons[$cat['slug']] ?? 'fa-microchip') ?>"></i><?= e($cat['name']) ?></span><span class="tz-marquee__sep">&#9670;</span>
      <?php endforeach; endfor; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- BANNER PROMOSI (jika admin mengaktifkan) -->
  <?php if (!empty($activeBanners)): ?>
  <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-14">
    <div class="grid grid-cols-1 <?= count($activeBanners) > 1 ? 'md:grid-cols-' . count($activeBanners) : '' ?> gap-5">
      <?php foreach ($activeBanners as $banner): ?>
        <a href="<?= e($banner['button_url'] ?: 'products.php') ?>" class="reveal card-hover relative rounded-2xl overflow-hidden group block h-44 shadow-card">
          <img src="<?= e(techzoneBaseUrl() . '/' . ltrim($banner['image'], '/')) ?>" alt="<?= e($banner['title']) ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-700 ease-out">
          <div class="absolute inset-0 bg-gradient-to-t from-navy-900/90 via-navy-900/25 to-transparent flex flex-col justify-end p-5">
            <p class="text-white font-bold text-base font-display"><?= e($banner['title']) ?></p>
            <?php if ($banner['subtitle']): ?><p class="text-gray-200 text-xs"><?= e($banner['subtitle']) ?></p><?php endif; ?>
            <?php if ($banner['button_text']): ?><span class="inline-flex items-center gap-1 mt-1 text-cyan-400 text-xs font-bold group-hover:gap-2 transition-all"><?= e($banner['button_text']) ?> <i class="fa-solid fa-arrow-right text-[10px]"></i></span><?php endif; ?>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- KATEGORI -->
  <section id="kategori-section" class="tz-section">
    <div class="reveal tz-sechead">
      <div><span class="eyebrow eyebrow--left">Koleksi</span><h2>Kategori <em>Produk</em></h2></div>
      <a href="products.php">Lihat Semua <i class="fa-solid fa-arrow-right-long"></i></a>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
      <?php foreach ($categories as $i => $cat): ?>
      <a href="products.php?kategori=<?= e($cat['slug']) ?>" style="transition-delay: <?= min($i * 60, 360) ?>ms" class="reveal reveal-zoom group tz-cat card-hover">
        <div class="tz-cat__ico"><i class="fa-solid <?= e($categoryIcons[$cat['slug']] ?? 'fa-microchip') ?>"></i></div>
        <b><?= e($cat['name']) ?></b>
        <small><?= (int) $cat['total_produk'] ?> produk</small>
      </a>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- PRODUK UNGGULAN -->
  <?php if (!empty($featuredProducts)): ?>
  <section class="tz-section">
    <div class="reveal tz-sechead">
      <div><span class="eyebrow eyebrow--left">Pilihan Kurator</span><h2>Produk <em>Unggulan</em></h2></div>
      <a href="products.php">Lihat Semua <i class="fa-solid fa-arrow-right-long"></i></a>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-5">
      <?php foreach ($featuredProducts as $i => $product): ?>
        <div class="reveal" style="transition-delay: <?= min($i * 70, 350) ?>ms">
          <?php renderProductCard($product); ?>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- PRODUK TERBARU -->
  <?php if (!empty($newestProducts)): ?>
  <section class="tz-section">
    <div class="reveal tz-sechead">
      <div><span class="eyebrow eyebrow--left">Baru Tiba</span><h2>Produk <em>Terbaru</em></h2></div>
      <a href="products.php?sort=terbaru">Lihat Semua <i class="fa-solid fa-arrow-right-long"></i></a>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-5">
      <?php foreach ($newestProducts as $i => $product): ?>
        <div class="reveal" style="transition-delay: <?= min($i * 70, 350) ?>ms">
          <?php renderProductCard($product); ?>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- TERAKHIR DILIHAT -->
  <?php if (!empty($recentlyViewed)): ?>
  <section class="tz-section">
    <div class="reveal tz-sechead">
      <div><span class="eyebrow eyebrow--left">Untuk Anda</span><h2>Terakhir <em>Dilihat</em></h2></div>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-5">
      <?php foreach ($recentlyViewed as $i => $product): ?>
        <div class="reveal" style="transition-delay: <?= min($i * 70, 350) ?>ms"><?php renderProductCard($product); ?></div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- BANNER FITUR -->
  <section class="tz-features">
    <div class="tz-stage__grid grid-pattern absolute inset-0" style="z-index:-1;opacity:.45"></div>
    <div class="tz-stage__orb tz-stage__orb--3" style="left:20%;top:-40px"></div>
    <div class="tz-features__grid">
      <div class="reveal tz-feature card-hover">
        <span class="tz-feature__no">01</span>
        <div class="tz-feature__ico"><i class="fa-solid fa-truck-fast"></i></div>
        <h3>Pengiriman ke Seluruh Indonesia</h3>
        <p>Reguler &amp; Express tersedia</p>
      </div>
      <div class="reveal tz-feature card-hover">
        <span class="tz-feature__no">02</span>
        <div class="tz-feature__ico"><i class="fa-solid fa-shield-halved"></i></div>
        <h3>Transaksi Aman</h3>
        <p>QRIS, Transfer Bank, atau COD</p>
      </div>
      <div class="reveal tz-feature card-hover">
        <span class="tz-feature__no">03</span>
        <div class="tz-feature__ico"><i class="fa-solid fa-headset"></i></div>
        <h3>Dukungan Pelanggan</h3>
        <p>Siap membantu kebutuhan proyek IoT Anda</p>
      </div>
    </div>
  </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
