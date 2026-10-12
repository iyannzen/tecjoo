<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/cart_helpers.php';
$navUser    = isUserLoggedIn() ? currentUser() : null;
$navIsAdmin = isAdminLoggedIn();
$base       = techzoneBaseUrl();

$cartBadgeCount = 0;
try {
    $navPdo = getDbConnection();
    $cartBadgeCount = getCartItemCount($navPdo, getOrCreateCartId($navPdo));
} catch (Throwable $e) {
    $cartBadgeCount = 0;
}

$navUnreadNotif = 0;
if ($navUser) {
    require_once __DIR__ . '/notification_helpers.php';
    try {
        $navUnreadNotif = getUnreadNotificationCount(getDbConnection(), (int) $navUser['id']);
    } catch (Throwable $e) {
        $navUnreadNotif = 0;
    }
}
?>
<?php $navCurrent = basename($_SERVER['SCRIPT_NAME'] ?? ''); $navInitial = strtoupper(substr(($navIsAdmin ? ($_SESSION['name'] ?? 'A') : ($navUser['name'] ?? 'T')), 0, 1)); ?>
<div class="tz-announce" aria-hidden="true">
  <div class="tz-announce__track">
    <?php for ($r = 0; $r < 2; $r++): ?>
      <?php for ($k = 0; $k < 2; $k++): ?>
      <span class="tz-announce__item"><i class="fa-solid fa-gem"></i> Teknologi IoT <b>Premium</b></span><span class="tz-announce__sep">&#9670;</span>
      <span class="tz-announce__item"><i class="fa-solid fa-truck-fast"></i> Pengiriman ke <b>Seluruh Indonesia</b></span><span class="tz-announce__sep">&#9670;</span>
      <span class="tz-announce__item"><i class="fa-solid fa-shield-halved"></i> Bayar via <b>QRIS</b> &middot; <b>Transfer Bank</b> &middot; <b>COD</b></span><span class="tz-announce__sep">&#9670;</span>
      <?php endfor; ?>
    <?php endfor; ?>
  </div>
</div>

<header id="techzone-header" class="tz-nav">
  <nav class="tz-nav__inner">
    <div class="tz-nav__brand">
      <button id="techzone-menu-trigger" type="button" aria-label="Buka menu" class="tz-burger"><i class="fa-solid fa-bars-staggered"></i><span class="tz-burger__label">Menu</span></button>
      <a href="<?= e($base) ?>/index.php" class="tz-logo" aria-label="TechZone">
        <span class="tz-logo__mark"><i class="fa-solid fa-microchip"></i></span>
        <span class="tz-logo__text">Tech<em class="gradient-text">Zone</em></span>
      </a>
    </div>

    <div class="tz-nav__links no-scrollbar">
      <a href="<?= e($base) ?>/index.php" class="nav-link <?= $navCurrent === 'index.php' ? 'active' : '' ?>">Beranda</a>
      <a href="<?= e($base) ?>/products.php" class="nav-link <?= $navCurrent === 'products.php' ? 'active' : '' ?>">Produk</a>
      <a href="<?= e($base) ?>/products.php#kategori" class="nav-link">Kategori</a>
      <a href="<?= e($base) ?>/about.php" class="nav-link <?= $navCurrent === 'about.php' ? 'active' : '' ?>">Tentang Kami</a>
      <a href="<?= e($base) ?>/contact.php" class="nav-link <?= $navCurrent === 'contact.php' ? 'active' : '' ?>">Kontak</a>
    </div>

    <form action="<?= e($base) ?>/products.php" method="GET" class="tz-nav__search" data-suggest="<?= e($base) ?>/api/search-suggest.php">
      <input type="text" name="q" placeholder="Cari produk..." autocomplete="off" aria-label="Cari produk">
      <button type="submit" aria-label="Cari"><i class="fa-solid fa-magnifying-glass text-sm"></i></button>
    </form>

    <div class="tz-nav__actions">
      <a href="<?= e($base) ?>/products.php#cari" class="tz-icon-btn tz-nav__searchbtn" aria-label="Cari produk" title="Cari produk"><i class="fa-solid fa-magnifying-glass"></i></a>
      <button type="button" class="tz-icon-btn tz-hide-xs" id="tz-theme-toggle" aria-label="Ganti tema terang/gelap" title="Mode terang / gelap"><i class="fa-solid fa-moon tz-theme-ico-moon"></i><i class="fa-solid fa-sun tz-theme-ico-sun"></i></button>
      <a href="<?= e($base) ?>/cart.php" class="tz-icon-btn" aria-label="Keranjang">
        <i class="fa-solid fa-bag-shopping"></i>
        <?php if ($cartBadgeCount > 0): ?><span class="tz-badge badge-pop"><?= $cartBadgeCount > 9 ? '9+' : $cartBadgeCount ?></span><?php endif; ?>
      </a>
      <?php if ($navUser && !$navIsAdmin):
          $tzNdItems   = getRecentNotifications(getDbConnection(), (int) $navUser['id'], 6);
          $tzNdUnread  = $navUnreadNotif;
          $tzNdAdmin   = false;
          $tzNdPageUrl = $base . '/account/notifications.php';
          include __DIR__ . '/notification_dropdown.php';
      endif; ?>
      <?php if (!$navUser && !$navIsAdmin): ?>
        <a href="<?= e($base) ?>/login.php" class="tz-signin"><i class="fa-solid fa-right-to-bracket"></i><span>Masuk</span></a>
      <?php else: ?>
        <a href="<?= e($base) ?>/<?= $navIsAdmin ? 'admin/index.php' : 'account/index.php' ?>" class="tz-icon-btn tz-hide-xs" aria-label="Akun">
          <i class="fa-regular fa-user"></i>
        </a>
      <?php endif; ?>
    </div>
  </nav>
</header>

<div id="techzone-menu-overlay" class="tz-overlay"></div>
<aside id="techzone-menu-panel" class="tz-drawer" aria-label="Menu">
  <div class="tz-drawer__head">
    <div class="tz-drawer__user">
      <span class="tz-avatar"><?= e($navInitial) ?></span>
      <div class="min-w-0">
        <?php if ($navIsAdmin): ?>
          <p class="tz-drawer__name"><?= e($_SESSION['name'] ?? 'Admin') ?></p><p class="tz-drawer__role">Administrator</p>
        <?php elseif ($navUser): ?>
          <p class="tz-drawer__name"><?= e($navUser['name']) ?></p><p class="tz-drawer__role">Member TechZone</p>
        <?php else: ?>
          <p class="tz-drawer__name">Selamat Datang</p><p class="tz-drawer__role">Tamu</p>
        <?php endif; ?>
      </div>
    </div>
    <button id="techzone-menu-close" type="button" aria-label="Tutup menu" class="tz-drawer__close"><i class="fa-solid fa-xmark"></i></button>
  </div>

  <nav class="tz-drawer__nav">
    <div class="tz-drawer__main">
      <p class="tz-drawer__label">Menu Utama</p>
      <?php
      $mainItems = [
          ['Beranda',      'index.php',            'fa-house',        $navCurrent === 'index.php'],
          ['Produk',       'products.php',         'fa-microchip',    $navCurrent === 'products.php'],
          ['Kategori',     'products.php#kategori','fa-layer-group',  false],
          ['Tentang Kami', 'about.php',            'fa-circle-info',  $navCurrent === 'about.php'],
          ['Kontak',       'contact.php',          'fa-headset',      $navCurrent === 'contact.php'],
      ];
      foreach ($mainItems as $mi => [$mLabel, $mPath, $mIcon, $mActive]): ?>
        <a href="<?= e($base) ?>/<?= e($mPath) ?>" class="tz-drawer__link <?= $mActive ? 'tz-drawer__link--active' : '' ?>" style="--i:<?= $mi ?>"<?= $mActive ? ' aria-current="page"' : '' ?>>
          <span><i class="fa-solid <?= $mIcon ?> ico"></i><?= e($mLabel) ?></span>
          <i class="fa-solid fa-arrow-right text-[10px] opacity-40"></i>
        </a>
      <?php endforeach; ?>
    </div>

    <?php
    if ($navIsAdmin) {
        $drawerItems = [['Panel Admin', 'admin/index.php', 'fa-shield-halved', 0]];
    } elseif ($navUser) {
        $drawerItems = [
            ['Dashboard Akun', 'account/index.php', 'fa-gauge', 0],
            ['Profil', 'account/profile.php', 'fa-user', 0],
            ['Alamat', 'account/addresses.php', 'fa-location-dot', 0],
            ['Pesanan Saya', 'account/orders.php', 'fa-box', 0],
            ['Riwayat Transaksi', 'account/transactions.php', 'fa-receipt', 0],
            ['Wishlist', 'account/wishlist.php', 'fa-heart', 0],
            ['Notifikasi', 'account/notifications.php', 'fa-bell', $navUnreadNotif],
        ];
    } else {
        $drawerItems = [['Masuk', 'login.php', 'fa-right-to-bracket', 0], ['Daftar', 'register.php', 'fa-user-plus', 0]];
    }
    ?>
    <p class="tz-drawer__label">Akun</p>
    <?php foreach ($drawerItems as $i => [$label, $path, $icon, $count]): ?>
      <a href="<?= e($base) ?>/<?= e($path) ?>" class="tz-drawer__link <?= $label === 'Daftar' ? 'tz-drawer__link--gold' : '' ?>" style="--i:<?= $i + 5 ?>">
        <span><i class="fa-solid <?= $icon ?> ico"></i><?= e($label) ?></span>
        <?php if ($count > 0): ?><span class="tz-dbadge"><?= $count > 9 ? '9+' : (int) $count ?></span><?php else: ?><i class="fa-solid fa-arrow-right text-[10px] opacity-40"></i><?php endif; ?>
      </a>
    <?php endforeach; ?>

    <p class="tz-drawer__label">Jelajahi</p>
    <a href="<?= e($base) ?>/products.php" class="tz-drawer__link tz-desk-only" style="--i:13"><span><i class="fa-solid fa-microchip ico"></i>Semua Produk</span><i class="fa-solid fa-arrow-right text-[10px] opacity-40"></i></a>
    <a href="<?= e($base) ?>/cart.php" class="tz-drawer__link" style="--i:14"><span><i class="fa-solid fa-bag-shopping ico"></i>Keranjang</span><?php if ($cartBadgeCount > 0): ?><span class="tz-dbadge"><?= (int) $cartBadgeCount ?></span><?php else: ?><i class="fa-solid fa-arrow-right text-[10px] opacity-40"></i><?php endif; ?></a>
    <a href="<?= e($base) ?>/faq.php" class="tz-drawer__link" style="--i:15"><span><i class="fa-solid fa-circle-question ico"></i>Bantuan &amp; FAQ</span><i class="fa-solid fa-arrow-right text-[10px] opacity-40"></i></a>
    <button type="button" class="tz-drawer__link tz-drawer__theme js-theme-toggle" style="--i:16"><span><i class="fa-solid fa-circle-half-stroke ico"></i>Mode Terang / Gelap</span></button>
  </nav>

  <?php if ($navIsAdmin || $navUser): ?>
    <div class="tz-drawer__foot">
      <a href="<?= e($base) ?>/logout.php<?= $navIsAdmin ? '?scope=admin' : '' ?>" class="tz-drawer__out"><i class="fa-solid fa-right-from-bracket"></i> Keluar</a>
    </div>
  <?php endif; ?>
</aside>

<script>
  (function () {
    var trigger  = document.getElementById('techzone-menu-trigger');
    var panel    = document.getElementById('techzone-menu-panel');
    var overlay  = document.getElementById('techzone-menu-overlay');
    var closeBtn = document.getElementById('techzone-menu-close');
    if (!trigger || !panel || !overlay) return;

    function openPanel()  { overlay.classList.add('is-open'); panel.classList.add('is-open'); document.body.style.overflow = 'hidden'; }
    function closePanel() { overlay.classList.remove('is-open'); panel.classList.remove('is-open'); document.body.style.overflow = ''; }

    trigger.addEventListener('click', openPanel);
    if (closeBtn) closeBtn.addEventListener('click', closePanel);
    overlay.addEventListener('click', closePanel);
    panel.querySelectorAll('a').forEach(function (a) { a.addEventListener('click', closePanel); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closePanel(); });
  })();
</script>
