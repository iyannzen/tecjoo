<?php
/**
 * TechZone Admin - Riwayat Transaksi
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../config/payment.php';

$admin = currentAdmin();
$pdo   = getDbConnection();

$dateFrom  = trim($_GET['date_from'] ?? '');
$dateTo    = trim($_GET['date_to'] ?? '');
$userQ     = trim($_GET['user'] ?? '');
$methodQ   = $_GET['method'] ?? '';
$statusQ   = $_GET['status'] ?? '';
$orderNumQ = trim($_GET['order_number'] ?? '');

$where  = [];
$params = [];
if ($dateFrom !== '') { $where[] = 'DATE(p.created_at) >= :df'; $params[':df'] = $dateFrom; }
if ($dateTo !== '') { $where[] = 'DATE(p.created_at) <= :dt'; $params[':dt'] = $dateTo; }
if ($userQ !== '') { $where[] = '(u.name LIKE :user OR u.email LIKE :user2)'; $params[':user'] = '%'.$userQ.'%'; $params[':user2'] = '%'.$userQ.'%'; }
if (in_array($methodQ, getValidPaymentMethods(), true)) { $where[] = 'p.payment_method = :method'; $params[':method'] = $methodQ; }
if (in_array($statusQ, getValidPaymentStatuses(), true)) { $where[] = 'p.status = :status'; $params[':status'] = $statusQ; }
if ($orderNumQ !== '') { $where[] = 'o.order_number LIKE :onum'; $params[':onum'] = '%'.$orderNumQ.'%'; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare(
    "SELECT p.*, o.order_number, o.order_status, u.name AS user_name, u.email AS user_email
     FROM payments p JOIN orders o ON o.id = p.order_id JOIN users u ON u.id = o.user_id
     $whereSql ORDER BY p.created_at DESC LIMIT 200"
);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

$pageTitle = 'Riwayat Transaksi | TechZone Admin';
require __DIR__ . '/../includes/header.php';
?>
<div class="min-h-screen flex bg-gray-50">
  <?php require __DIR__ . '/_sidebar.php'; ?>
  <div id="techzone-admin-content" class="flex-1">
    <?php require __DIR__ . '/_topbar.php'; ?>
    <main class="p-6">
      <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h1 class="reveal text-2xl font-extrabold text-navy-900">Riwayat Transaksi</h1>
        <a href="export-csv.php?<?= e(http_build_query($_GET)) ?>" class="bg-navy-900 hover:bg-navy-800 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
          <i class="fa-solid fa-file-csv mr-1"></i>Export CSV
        </a>
      </div>

      <form method="GET" class="bg-white border border-gray-100 rounded-xl p-4 mb-6 grid grid-cols-2 md:grid-cols-3 gap-3 shadow-card transition-shadow hover:shadow-lg">
        <input type="text" name="order_number" value="<?= e($orderNumQ) ?>" placeholder="No. Order" class="text-sm border border-gray-300 rounded-lg px-3 py-2">
        <input type="text" name="user" value="<?= e($userQ) ?>" placeholder="Nama/Email User" class="text-sm border border-gray-300 rounded-lg px-3 py-2">
        <select name="method" class="text-sm border border-gray-300 rounded-lg px-3 py-2">
          <option value="">Semua Metode</option>
          <?php foreach (getValidPaymentMethods() as $m): ?><option value="<?= e($m) ?>" <?= $methodQ===$m?'selected':'' ?>><?= strtoupper(e($m)) ?></option><?php endforeach; ?>
        </select>
        <select name="status" class="text-sm border border-gray-300 rounded-lg px-3 py-2">
          <option value="">Semua Status</option>
          <?php foreach (getValidPaymentStatuses() as $s): ?><option value="<?= e($s) ?>" <?= $statusQ===$s?'selected':'' ?>><?= e(paymentStatusLabel($s)) ?></option><?php endforeach; ?>
        </select>
        <input type="date" name="date_from" value="<?= e($dateFrom) ?>" class="text-sm border border-gray-300 rounded-lg px-3 py-2">
        <input type="date" name="date_to" value="<?= e($dateTo) ?>" class="text-sm border border-gray-300 rounded-lg px-3 py-2">
        <div class="flex gap-2 col-span-2 md:col-span-1">
          <button type="submit" class="bg-navy-900 text-white text-sm font-semibold px-4 py-2 rounded-lg hover:bg-navy-800 flex-1">Filter</button>
          <a href="transactions.php" class="text-gray-500 text-sm font-medium px-3 py-2 hover:text-navy-900">Reset</a>
        </div>
      </form>

      <div class="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
            <tr><th class="text-left px-4 py-3">ID Transaksi</th><th class="text-left px-4 py-3">Order</th><th class="text-left px-4 py-3">User</th><th class="text-left px-4 py-3">Tanggal</th><th class="text-right px-4 py-3">Jumlah</th><th class="text-left px-4 py-3">Metode</th><th class="text-left px-4 py-3">Status</th></tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <?php foreach ($transactions as $t): ?>
            <tr>
              <td class="px-4 py-3 font-mono text-xs text-gray-500"><?= e($t['transaction_id']) ?></td>
              <td class="px-4 py-3 font-medium text-navy-900"><a href="order-detail.php?order=<?= e($t['order_number']) ?>" class="hover:text-cyan-600"><?= e($t['order_number']) ?></a></td>
              <td class="px-4 py-3"><p class="text-navy-900"><?= e($t['user_name']) ?></p><p class="text-xs text-gray-400"><?= e($t['user_email']) ?></p></td>
              <td class="px-4 py-3 text-xs text-gray-500"><?= e(formatTanggalIndo($t['created_at'], true)) ?></td>
              <td class="px-4 py-3 text-right font-semibold text-navy-900"><?= e(formatRupiah($t['amount'])) ?></td>
              <td class="px-4 py-3 text-xs uppercase text-gray-500"><?= e($t['payment_method']) ?></td>
              <td class="px-4 py-3"><span class="text-[11px] font-semibold px-2 py-1 rounded-full bg-amber-50 text-amber-700"><?= e(paymentStatusLabel($t['status'], $t['payment_method'])) ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($transactions)): ?><tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">Tidak ada transaksi ditemukan.</td></tr><?php endif; ?>
          </tbody>
        </table>
        </div>
      </div>
    </main>
  </div>
</div>
</body>
</html>