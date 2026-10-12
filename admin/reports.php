<?php
/**
 * TechZone Admin - Laporan
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../config/payment.php';

$admin = currentAdmin();
$pdo   = getDbConnection();

function sumSales(PDO $pdo, string $sinceExpr): float
{
    $stmt = $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status IN ('paid','cod') AND created_at >= $sinceExpr");
    return (float) $stmt->fetchColumn();
}
function countSales(PDO $pdo, string $sinceExpr): int
{
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status IN ('paid','cod') AND created_at >= $sinceExpr");
    return (int) $stmt->fetchColumn();
}

$salesToday     = sumSales($pdo, 'CURDATE()');
$salesWeek      = sumSales($pdo, 'DATE_SUB(CURDATE(), INTERVAL 7 DAY)');
$salesMonth     = sumSales($pdo, 'DATE_SUB(CURDATE(), INTERVAL 30 DAY)');
$salesYear      = sumSales($pdo, 'DATE_SUB(CURDATE(), INTERVAL 365 DAY)');
$ordersToday    = countSales($pdo, 'CURDATE()');
$ordersWeek     = countSales($pdo, 'DATE_SUB(CURDATE(), INTERVAL 7 DAY)');
$ordersMonth    = countSales($pdo, 'DATE_SUB(CURDATE(), INTERVAL 30 DAY)');
$ordersYear     = countSales($pdo, 'DATE_SUB(CURDATE(), INTERVAL 365 DAY)');

$bestSellers = $pdo->query(
    "SELECT oi.product_name, oi.sku, SUM(oi.quantity) AS total_qty, SUM(oi.subtotal) AS total_revenue
     FROM order_items oi JOIN orders o ON o.id = oi.order_id
     WHERE o.payment_status IN ('paid','cod')
     GROUP BY oi.product_id, oi.product_name, oi.sku
     ORDER BY total_qty DESC LIMIT 10"
)->fetchAll();

$lowStock = $pdo->query("SELECT name, sku, stock FROM products WHERE stock BETWEEN 1 AND 10 ORDER BY stock ASC LIMIT 10")->fetchAll();
$outStock = $pdo->query("SELECT name, sku FROM products WHERE stock = 0 ORDER BY updated_at DESC LIMIT 10")->fetchAll();

$orderStatusCounts = $pdo->query(
    "SELECT order_status, COUNT(*) AS total FROM orders GROUP BY order_status"
)->fetchAll(PDO::FETCH_KEY_PAIR);

$paymentMethodBreakdown = $pdo->query(
    "SELECT payment_method, COUNT(*) AS total, COALESCE(SUM(total),0) AS revenue FROM orders
     WHERE payment_status IN ('paid','cod') GROUP BY payment_method"
)->fetchAll();

$pageTitle = 'Laporan | TechZone Admin';
require __DIR__ . '/../includes/header.php';
?>
<div class="min-h-screen flex bg-gray-50">
  <?php require __DIR__ . '/_sidebar.php'; ?>
  <div id="techzone-admin-content" class="flex-1">
    <?php require __DIR__ . '/_topbar.php'; ?>
    <main class="p-6">
      <div class="flex items-center justify-between mb-6">
        <h1 class="reveal text-2xl font-extrabold text-navy-900">Laporan</h1>
        <a href="export-csv.php" class="bg-navy-900 hover:bg-navy-800 text-white text-sm font-semibold px-4 py-2 rounded-lg transition"><i class="fa-solid fa-file-csv mr-1"></i>Export Semua Transaksi (CSV)</a>
      </div>

      <!-- PENJUALAN -->
      <h2 class="text-sm font-bold text-navy-900 mb-3">Penjualan</h2>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-card transition-shadow hover:shadow-lg"><p class="text-xs text-gray-500">Hari Ini</p><p class="text-lg font-extrabold text-navy-900"><?= e(formatRupiah($salesToday)) ?></p><p class="text-[11px] text-gray-400"><?= $ordersToday ?> pesanan</p></div>
        <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-card transition-shadow hover:shadow-lg"><p class="text-xs text-gray-500">7 Hari Terakhir</p><p class="text-lg font-extrabold text-navy-900"><?= e(formatRupiah($salesWeek)) ?></p><p class="text-[11px] text-gray-400"><?= $ordersWeek ?> pesanan</p></div>
        <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-card transition-shadow hover:shadow-lg"><p class="text-xs text-gray-500">30 Hari Terakhir</p><p class="text-lg font-extrabold text-navy-900"><?= e(formatRupiah($salesMonth)) ?></p><p class="text-[11px] text-gray-400"><?= $ordersMonth ?> pesanan</p></div>
        <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-card transition-shadow hover:shadow-lg"><p class="text-xs text-gray-500">1 Tahun Terakhir</p><p class="text-lg font-extrabold text-navy-900"><?= e(formatRupiah($salesYear)) ?></p><p class="text-[11px] text-gray-400"><?= $ordersYear ?> pesanan</p></div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- PRODUK TERLARIS -->
        <div class="bg-white border border-gray-100 rounded-xl p-5 shadow-card transition-shadow hover:shadow-lg">
          <h2 class="text-sm font-bold text-navy-900 mb-3">Produk Terlaris</h2>
          <?php if (empty($bestSellers)): ?><p class="text-xs text-gray-400">Belum ada data penjualan.</p><?php endif; ?>
          <div class="space-y-2">
            <?php foreach ($bestSellers as $i => $b): ?>
            <div class="flex items-center justify-between text-sm border-b border-gray-50 pb-2 last:border-0">
              <div><span class="text-gray-400 mr-1"><?= $i+1 ?>.</span><span class="text-navy-900 font-medium"><?= e($b['product_name']) ?></span></div>
              <span class="text-xs text-gray-500"><?= (int) $b['total_qty'] ?> terjual</span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- STATUS PESANAN -->
        <div class="bg-white border border-gray-100 rounded-xl p-5 shadow-card transition-shadow hover:shadow-lg">
          <h2 class="text-sm font-bold text-navy-900 mb-3">Pesanan per Status</h2>
          <div class="space-y-2">
            <?php foreach (getValidOrderStatuses() as $s): ?>
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-600"><?= e(orderStatusLabel($s)) ?></span>
              <span class="font-semibold text-navy-900"><?= (int) ($orderStatusCounts[$s] ?? 0) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- STOK -->
        <div class="bg-white border border-gray-100 rounded-xl p-5 shadow-card transition-shadow hover:shadow-lg">
          <h2 class="text-sm font-bold text-navy-900 mb-3">Stok Menipis</h2>
          <?php if (empty($lowStock)): ?><p class="text-xs text-gray-400">Tidak ada produk dengan stok menipis.</p><?php endif; ?>
          <div class="space-y-1.5">
            <?php foreach ($lowStock as $p): ?>
              <div class="flex justify-between text-sm"><span class="text-navy-900"><?= e($p['name']) ?></span><span class="text-amber-600 font-semibold"><?= (int) $p['stock'] ?></span></div>
            <?php endforeach; ?>
          </div>
          <h3 class="text-xs font-bold text-gray-500 uppercase mt-4 mb-2">Stok Habis</h3>
          <?php if (empty($outStock)): ?><p class="text-xs text-gray-400">Tidak ada produk yang habis.</p><?php endif; ?>
          <div class="space-y-1.5">
            <?php foreach ($outStock as $p): ?>
              <div class="text-sm text-red-600"><?= e($p['name']) ?></div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- METODE PEMBAYARAN -->
        <div class="bg-white border border-gray-100 rounded-xl p-5 shadow-card transition-shadow hover:shadow-lg">
          <h2 class="text-sm font-bold text-navy-900 mb-3">Distribusi Metode Pembayaran</h2>
          <?php foreach (['qris' => 'QRIS', 'transfer' => 'Transfer Bank', 'cod' => 'COD'] as $key => $label):
            $found = null;
            foreach ($paymentMethodBreakdown as $pmb) { if ($pmb['payment_method'] === $key) { $found = $pmb; break; } }
          ?>
          <div class="flex items-center justify-between text-sm border-b border-gray-50 py-2 last:border-0">
            <span class="text-gray-600"><?= e($label) ?></span>
            <span class="text-navy-900 font-medium"><?= (int) ($found['total'] ?? 0) ?> transaksi &middot; <?= e(formatRupiah($found['revenue'] ?? 0)) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </main>
  </div>
</div>
</body>
</html>