<?php
/**
 * TechZone Admin - Dashboard
 * Statistik lengkap (penjualan, laporan, dsb) akan diperkaya di Tahap 5.
 * Versi ini sudah mengambil data ASLI dari database (bukan angka dummy).
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../config/payment.php';

$admin = currentAdmin();
$pdo   = getDbConnection();

function safeCount(PDO $pdo, string $sql, array $params = []): int
{
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        // Tabel terkait (mis. products/orders) mungkin belum diisi pada Tahap 1.
        return 0;
    }
}

$totalProduk       = safeCount($pdo, 'SELECT COUNT(*) FROM products');
$totalUser         = safeCount($pdo, "SELECT COUNT(*) FROM users WHERE role = 'user'");
$totalPesanan      = safeCount($pdo, 'SELECT COUNT(*) FROM orders');
$pesananBaru       = safeCount($pdo, "SELECT COUNT(*) FROM orders WHERE order_status = 'waiting_payment'");
$menungguVerif     = safeCount($pdo, "SELECT COUNT(*) FROM orders WHERE payment_status = 'waiting_verification'");
$pesananSelesai    = safeCount($pdo, "SELECT COUNT(*) FROM orders WHERE order_status = 'completed'");
$stokMenipis       = safeCount($pdo, 'SELECT COUNT(*) FROM products WHERE stock > 0 AND stock <= 10');
$totalPenjualanRow = 0;
try {
    $stmt = $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status IN ('paid','cod')");
    $totalPenjualanRow = (float) $stmt->fetchColumn();
} catch (Throwable $e) {
    $totalPenjualanRow = 0;
}

// ---- Data tambahan untuk grafik & daftar (semua dari database, aman bila tabel kosong) ----
$hariPendek = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
$omzet7 = [];
for ($i = 6; $i >= 0; $i--) {
    $ts = strtotime("-$i day");
    $omzet7[date('Y-m-d', $ts)] = ['label' => $hariPendek[(int) date('w', $ts)], 'total' => 0.0];
}
try {
    $stmt = $pdo->query("SELECT DATE(created_at) AS d, SUM(total) AS s FROM orders
                         WHERE payment_status IN ('paid','cod') AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                         GROUP BY DATE(created_at)");
    foreach ($stmt->fetchAll() as $row) {
        if (isset($omzet7[$row['d']])) { $omzet7[$row['d']]['total'] = (float) $row['s']; }
    }
} catch (Throwable $e) { /* abaikan */ }
$omzetMax   = max(1, ...array_column($omzet7, 'total'));
$omzetTotal = array_sum(array_column($omzet7, 'total'));

$statusCounts = [];
try {
    foreach ($pdo->query('SELECT order_status, COUNT(*) AS c FROM orders GROUP BY order_status')->fetchAll() as $row) {
        $statusCounts[$row['order_status']] = (int) $row['c'];
    }
} catch (Throwable $e) { /* abaikan */ }
$statusColors = [
    'waiting_payment' => '#f59e0b', 'payment_verified' => '#d6b45c', 'processing' => '#60a5fa',
    'packed' => '#818cf8', 'shipped' => '#c084fc', 'completed' => '#34d399',
    'cancelled' => '#6b7280', 'refund_requested' => '#fb923c', 'returned' => '#f87171',
];
$donutTotal = array_sum($statusCounts);
$donutStops = []; $acc = 0.0;
foreach ($statusCounts as $st => $cnt) {
    $from = $acc; $acc += $cnt / max(1, $donutTotal) * 100;
    $donutStops[] = ($statusColors[$st] ?? '#9ca3af') . ' ' . round($from, 2) . '% ' . round($acc, 2) . '%';
}
$donutCss = $donutTotal > 0 ? 'conic-gradient(' . implode(',', $donutStops) . ')' : 'conic-gradient(rgba(255,255,255,.08) 0 100%)';

$recentOrders = [];
try {
    $recentOrders = $pdo->query('SELECT order_number, recipient_name, total, order_status, created_at FROM orders ORDER BY id DESC LIMIT 6')->fetchAll();
} catch (Throwable $e) { /* abaikan */ }

$lowStock = [];
try {
    $lowStock = $pdo->query('SELECT name, stock FROM products WHERE stock > 0 AND stock <= 10 ORDER BY stock ASC, name ASC LIMIT 6')->fetchAll();
} catch (Throwable $e) { /* abaikan */ }

$jam = (int) date('G');
$sapaan = $jam < 11 ? 'Selamat pagi' : ($jam < 15 ? 'Selamat siang' : ($jam < 19 ? 'Selamat sore' : 'Selamat malam'));

$pageTitle = 'Dashboard Admin | TechZone';
require __DIR__ . '/../includes/header.php';
?>
<div class="min-h-screen flex bg-gray-50">
  <?php require __DIR__ . '/_sidebar.php'; ?>

  <div id="techzone-admin-content" class="flex-1">
    <?php require __DIR__ . '/_topbar.php'; ?>

    <main class="tz-dash">

      <section class="tz-hero-card reveal">
        <div>
          <span class="eyebrow eyebrow--left">Executive Overview</span>
          <h1><?= e($sapaan) ?>, <em class="gradient-text" style="font-style:italic"><?= e($admin['name']) ?></em></h1>
          <p class="sub">Berikut ringkasan performa toko TechZone Anda hari ini.</p>
        </div>
        <div class="acts">
          <a href="payments.php" class="tz-chipbtn tz-chipbtn--gold ripple-wrap magnetic"><i class="fa-solid fa-circle-check"></i> Verifikasi Pembayaran</a>
          <a href="product-add.php" class="tz-chipbtn tz-chipbtn--ghost"><i class="fa-solid fa-plus"></i> Tambah Produk</a>
          <a href="orders.php" class="tz-chipbtn tz-chipbtn--ghost"><i class="fa-solid fa-box"></i> Kelola Pesanan</a>
        </div>
      </section>

      <div class="tz-kpis">
        <?php
        $cards = [
            ['Total Penjualan', $totalPenjualanRow, 'fa-sack-dollar', 'green', true],
            ['Total Pesanan', $totalPesanan, 'fa-box', 'blue', false],
            ['Total Produk', $totalProduk, 'fa-microchip', '', false],
            ['Total User', $totalUser, 'fa-users', 'violet', false],
            ['Pesanan Baru', $pesananBaru, 'fa-bell', 'orange', false],
            ['Menunggu Verifikasi', $menungguVerif, 'fa-clock', 'amber', false],
            ['Stok Menipis', $stokMenipis, 'fa-triangle-exclamation', 'red', false],
            ['Pesanan Selesai', $pesananSelesai, 'fa-circle-check', 'teal', false],
        ];
        foreach ($cards as $i => [$label, $value, $icon, $tone, $isMoney]):
        ?>
        <div class="reveal tz-kpi card-hover <?= $tone ? 'tz-kpi--' . $tone : '' ?>">
          <div class="tz-kpi__ico"><i class="fa-solid <?= $icon ?>"></i></div>
          <p class="tz-kpi__label"><?= e($label) ?></p>
          <p class="tz-kpi__val <?= $isMoney ? 'is-money' : '' ?>">
            <?= $isMoney ? 'Rp ' : '' ?><span data-count-to="<?= (int) $value ?>">0</span>
          </p>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="tz-panels" style="margin-bottom:1.1rem">
        <section class="tz-panel reveal">
          <div class="tz-panel__head">
            <div><h2>Penjualan 7 Hari Terakhir</h2><p>Total: <?= e(formatRupiah($omzetTotal)) ?></p></div>
            <a href="reports.php">Laporan <i class="fa-solid fa-arrow-right text-[10px]"></i></a>
          </div>
          <div class="tz-bars">
            <?php $bi = 0; foreach ($omzet7 as $d): $pct = $d['total'] > 0 ? max(6, round($d['total'] / $omzetMax * 100)) : 4; ?>
              <div class="tz-bar">
                <span class="tz-bar__tip"><?= e(formatRupiah($d['total'])) ?></span>
                <div class="tz-bar__fill <?= $d['total'] > 0 ? '' : 'is-zero' ?>" style="height:<?= $pct ?>%; --i:<?= $bi++ ?>"></div>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="tz-bar-labels"><?php foreach ($omzet7 as $d): ?><span><?= e($d['label']) ?></span><?php endforeach; ?></div>
        </section>

        <section class="tz-panel reveal">
          <div class="tz-panel__head"><div><h2>Status Pesanan</h2><p>Distribusi seluruh pesanan</p></div></div>
          <div class="tz-donut-wrap">
            <div class="tz-donut" style="background: <?= $donutCss ?>">
              <div class="tz-donut__center"><b><span data-count-to="<?= (int) $donutTotal ?>">0</span></b><small>Pesanan</small></div>
            </div>
            <?php if ($donutTotal > 0): ?>
            <ul class="tz-legend">
              <?php foreach ($statusCounts as $st => $cnt): ?>
                <li><span><i style="--c: <?= e($statusColors[$st] ?? '#9ca3af') ?>"></i><?= e(orderStatusLabel($st)) ?></span><b><?= (int) $cnt ?></b></li>
              <?php endforeach; ?>
            </ul>
            <?php else: ?>
              <p class="tz-empty" style="padding:0">Belum ada pesanan.</p>
            <?php endif; ?>
          </div>
        </section>
      </div>

      <div class="tz-panels">
        <section class="tz-panel reveal">
          <div class="tz-panel__head"><div><h2>Pesanan Terbaru</h2><p>6 transaksi terakhir yang masuk</p></div><a href="orders.php">Lihat semua <i class="fa-solid fa-arrow-right text-[10px]"></i></a></div>
          <?php if ($recentOrders): ?>
          <div class="tz-orders">
            <?php foreach ($recentOrders as $o): ?>
              <a href="order-detail.php?order=<?= e($o['order_number']) ?>" class="tz-order">
                <div><p class="tz-order__no"><?= e($o['order_number']) ?></p><p class="tz-order__who"><?= e($o['recipient_name']) ?> &middot; <?= e(formatTanggalIndo($o['created_at'])) ?></p></div>
                <div class="tz-order__right"><p class="tz-order__total"><?= e(formatRupiah($o['total'])) ?></p><span class="badge <?= orderStatusBadgeClass($o['order_status']) ?>"><?= e(orderStatusLabel($o['order_status'])) ?></span></div>
              </a>
            <?php endforeach; ?>
          </div>
          <?php else: ?>
            <div class="tz-empty"><i class="fa-regular fa-folder-open"></i>Belum ada pesanan masuk.</div>
          <?php endif; ?>
        </section>

        <section class="tz-panel reveal">
          <div class="tz-panel__head"><div><h2>Stok Menipis</h2><p>Segera lakukan restok</p></div><a href="inventory.php">Kelola <i class="fa-solid fa-arrow-right text-[10px]"></i></a></div>
          <?php if ($lowStock): ?>
          <div class="tz-orders">
            <?php foreach ($lowStock as $p): ?>
              <a href="inventory.php" class="tz-order">
                <p class="tz-order__no" style="font-weight:600;font-size:.82rem"><?= e($p['name']) ?></p>
                <span class="badge bg-red-50 text-red-600" style="font-weight:800">Sisa <?= (int) $p['stock'] ?></span>
              </a>
            <?php endforeach; ?>
          </div>
          <?php else: ?>
            <div class="tz-empty"><i class="fa-solid fa-circle-check"></i>Semua stok aman.</div>
          <?php endif; ?>
        </section>
      </div>
    </main>
  </div>
</div>
</body>
</html>
