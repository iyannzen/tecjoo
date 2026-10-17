<?php
/**
 * TechZone Admin - Sidebar (tema "Noir & Aurum")
 * Di-include di dalam halaman admin setelah admin_auth.php.
 * Sidebar adalah overlay: selalu menutupi konten (tidak mendorong lebar halaman)
 * dan dibuka/ditutup lewat tombol di topbar (lihat _topbar.php).
 */
$base       = techzoneBaseUrl();
$currentUri = basename($_SERVER['SCRIPT_NAME']);

$menuGroups = [
    'UTAMA' => [
        ['Dashboard', 'index.php', 'fa-gauge', true],
        ['Notifikasi', 'notifications.php', 'fa-bell', true],
        ['Chat', 'chat.php', 'fa-comments', true],
    ],
    'KATALOG' => [
        ['Produk', 'products.php', 'fa-microchip', true],
        ['Kategori', 'categories.php', 'fa-tags', true],
        ['Stok', 'inventory.php', 'fa-boxes-stacked', true],
    ],
    'TRANSAKSI' => [
        ['Pesanan', 'orders.php', 'fa-box', true],
        ['Pembayaran', 'payments.php', 'fa-money-check-dollar', true],
        ['Rekening & E-Wallet', 'bank-accounts.php', 'fa-building-columns', true],
        ['Riwayat Transaksi', 'transactions.php', 'fa-receipt', true],
    ],
    'PENGELOLAAN' => [
        ['User', 'users.php', 'fa-users', true],
        ['Review', 'reviews.php', 'fa-star', true],
        ['Kupon', 'coupons.php', 'fa-ticket', true],
        ['Banner', 'banners.php', 'fa-image', true],
    ],
    'LAINNYA' => [
        ['Laporan', 'reports.php', 'fa-chart-line', true],
        ['Pengaturan', 'settings.php', 'fa-gear', true],
    ],
];
$linkIndex = 0;

// Angka pemberitahuan di samping menu (pesanan baru, pembayaran menunggu, stok menipis, notifikasi).
$badgeKeys   = ['notifications.php' => 'notifications', 'chat.php' => 'chat', 'orders.php' => 'orders', 'payments.php' => 'payments', 'inventory.php' => 'stock'];
$badgeCounts = tzAdminBadgeCounts(getDbConnection(), (int) (currentAdmin()['id'] ?? 0));
?>
<aside id="techzone-admin-sidebar" class="tz-aside" aria-label="Menu admin" data-counts-url="<?= e($base) ?>/api/admin-counts.php?scope=admin">
  <div class="tz-aside__head">
    <div class="tz-aside__brand">
      <span class="tz-logo__mark"><i class="fa-solid fa-microchip"></i></span>
      <div class="min-w-0">
        <span class="tz-logo__text">Tech<em class="gradient-text">Zone</em></span>
        <small>Admin Suite</small>
      </div>
    </div>
    <button id="techzone-admin-sidebar-close" type="button" aria-label="Tutup sidebar" class="tz-drawer__close"><i class="fa-solid fa-xmark"></i></button>
  </div>

  <nav class="tz-aside__nav">
    <?php foreach ($menuGroups as $groupLabel => $items): ?>
      <p class="tz-aside__label"><?= e($groupLabel) ?></p>
      <?php foreach ($items as [$label, $file, $icon, $available]): ?>
        <?php if ($available): ?>
          <a href="<?= e($base) ?>/admin/<?= e($file) ?>" style="--i:<?= $linkIndex++ ?>"
             class="tz-aside__link <?= ($currentUri === $file || ($file === 'bank-accounts.php' && $currentUri === 'ewallets.php')) ? 'is-active' : '' ?>">
            <i class="fa-solid <?= $icon ?>"></i> <?= e($label) ?>
            <?php if (isset($badgeKeys[$file])): $bk = $badgeKeys[$file]; $bc = (int) ($badgeCounts[$bk] ?? 0); ?>
              <span class="tz-aside__count" data-badge="<?= e($bk) ?>" <?= $bc > 0 ? '' : 'hidden' ?>><?= $bc > 99 ? '99+' : $bc ?></span>
            <?php endif; ?>
          </a>
        <?php else: ?>
          <span class="tz-aside__link is-disabled" style="--i:<?= $linkIndex++ ?>">
            <i class="fa-solid <?= $icon ?>"></i> <?= e($label) ?> <span class="tz-aside__soon">segera</span>
          </span>
        <?php endif; ?>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>
</aside>

<div id="techzone-admin-overlay" class="tz-overlay"></div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    var sidebar  = document.getElementById('techzone-admin-sidebar');
    var overlay  = document.getElementById('techzone-admin-overlay');
    var toggle   = document.getElementById('techzone-admin-sidebar-toggle');
    var closeBtn = document.getElementById('techzone-admin-sidebar-close');
    if (!sidebar || !overlay) return;

    function openSidebar()  { overlay.classList.add('is-open'); sidebar.classList.add('is-open'); }
    function closeSidebar() { overlay.classList.remove('is-open'); sidebar.classList.remove('is-open'); }

    if (toggle) toggle.addEventListener('click', openSidebar);
    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
    overlay.addEventListener('click', closeSidebar);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeSidebar(); });
  });
</script>
