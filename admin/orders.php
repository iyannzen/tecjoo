<?php
/**
 * TechZone Admin - Kelola Pesanan (Daftar)
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../config/payment.php';

$admin = currentAdmin();
$pdo   = getDbConnection();

$orderNumberQ   = trim($_GET['order_number'] ?? '');
$userQ          = trim($_GET['user'] ?? '');
$dateFrom       = trim($_GET['date_from'] ?? '');
$dateTo         = trim($_GET['date_to'] ?? '');
$orderStatusQ   = $_GET['order_status'] ?? '';
$paymentStatusQ = $_GET['payment_status'] ?? '';
$paymentMethodQ = $_GET['payment_method'] ?? '';
$page           = max(1, (int) ($_GET['page'] ?? 1));
$perPage        = 15;

$where  = [];
$params = [];

if ($orderNumberQ !== '') { $where[] = 'o.order_number LIKE :onum'; $params[':onum'] = '%' . $orderNumberQ . '%'; }
if ($userQ !== '') { $where[] = '(u.name LIKE :user OR u.email LIKE :user2)'; $params[':user'] = '%' . $userQ . '%'; $params[':user2'] = '%' . $userQ . '%'; }
if ($dateFrom !== '') { $where[] = 'DATE(o.created_at) >= :date_from'; $params[':date_from'] = $dateFrom; }
if ($dateTo !== '') { $where[] = 'DATE(o.created_at) <= :date_to'; $params[':date_to'] = $dateTo; }
if (in_array($orderStatusQ, getValidOrderStatuses(), true)) { $where[] = 'o.order_status = :ostatus'; $params[':ostatus'] = $orderStatusQ; }
if (in_array($paymentStatusQ, getValidPaymentStatuses(), true)) { $where[] = 'o.payment_status = :pstatus'; $params[':pstatus'] = $paymentStatusQ; }
if (in_array($paymentMethodQ, getValidPaymentMethods(), true)) { $where[] = 'o.payment_method = :pmethod'; $params[':pmethod'] = $paymentMethodQ; }

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders o JOIN users u ON u.id = o.user_id $whereSql");
$countStmt->execute($params);
$total      = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$stmt = $pdo->prepare(
    "SELECT o.*, u.name AS user_name, u.email AS user_email FROM orders o JOIN users u ON u.id = o.user_id
     $whereSql ORDER BY o.created_at DESC, o.id DESC LIMIT :limit OFFSET :offset"
);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll();

function adminOrdersUrl(array $override = []): string
{
    $current = $_GET;
    foreach ($override as $k => $v) { if ($v === null) unset($current[$k]); else $current[$k] = $v; }
    return 'orders.php?' . http_build_query($current);
}

$pageTitle = 'Kelola Pesanan | TechZone Admin';
require __DIR__ . '/../includes/header.php';
?>
<div class="min-h-screen flex bg-gray-50">
  <?php require __DIR__ . '/_sidebar.php'; ?>
  <div id="techzone-admin-content" class="flex-1">
    <?php require __DIR__ . '/_topbar.php'; ?>
    <main class="p-6">
      <h1 class="reveal text-2xl font-extrabold text-navy-900 mb-1">Kelola Pesanan</h1>
      <p class="text-sm text-gray-500 mb-6"><?= $total ?> pesanan ditemukan</p>

      <form method="GET" class="bg-white border border-gray-100 rounded-xl p-4 mb-6 grid grid-cols-2 md:grid-cols-4 gap-3 shadow-card transition-shadow hover:shadow-lg">
        <input type="text" name="order_number" value="<?= e($orderNumberQ) ?>" placeholder="No. Order" class="text-sm border border-gray-300 rounded-lg px-3 py-2">
        <input type="text" name="user" value="<?= e($userQ) ?>" placeholder="Nama/Email User" class="text-sm border border-gray-300 rounded-lg px-3 py-2">
        <input type="date" name="date_from" value="<?= e($dateFrom) ?>" class="text-sm border border-gray-300 rounded-lg px-3 py-2">
        <input type="date" name="date_to" value="<?= e($dateTo) ?>" class="text-sm border border-gray-300 rounded-lg px-3 py-2">
        <select name="order_status" class="text-sm border border-gray-300 rounded-lg px-3 py-2">
          <option value="">Semua Status Order</option>
          <?php foreach (getValidOrderStatuses() as $s): ?>
            <option value="<?= e($s) ?>" <?= $orderStatusQ === $s ? 'selected' : '' ?>><?= e(orderStatusLabel($s)) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="payment_status" class="text-sm border border-gray-300 rounded-lg px-3 py-2">
          <option value="">Semua Status Bayar</option>
          <?php foreach (getValidPaymentStatuses() as $s): ?>
            <option value="<?= e($s) ?>" <?= $paymentStatusQ === $s ? 'selected' : '' ?>><?= e(paymentStatusLabel($s)) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="payment_method" class="text-sm border border-gray-300 rounded-lg px-3 py-2">
          <option value="">Semua Metode</option>
          <?php foreach (getValidPaymentMethods() as $m): ?>
            <option value="<?= e($m) ?>" <?= $paymentMethodQ === $m ? 'selected' : '' ?>><?= strtoupper(e($m)) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="flex gap-2">
          <button type="submit" class="bg-navy-900 text-white text-sm font-semibold px-4 py-2 rounded-lg hover:bg-navy-800 flex-1">Filter</button>
          <a href="orders.php" class="text-gray-500 text-sm font-medium px-3 py-2 hover:text-navy-900">Reset</a>
        </div>
      </form>

      <div class="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
            <tr>
              <th class="text-left px-4 py-3">Order</th>
              <th class="text-left px-4 py-3">User</th>
              <th class="text-right px-4 py-3">Total</th>
              <th class="text-left px-4 py-3">Metode</th>
              <th class="text-left px-4 py-3">Status Bayar</th>
              <th class="text-left px-4 py-3">Status Order</th>
              <th class="text-center px-4 py-3">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <?php foreach ($orders as $o): ?>
            <tr>
              <td class="px-4 py-3">
                <p class="font-medium text-navy-900"><?= e($o['order_number']) ?></p>
                <p class="text-xs text-gray-400"><?= e(formatTanggalIndo($o['created_at'], true)) ?></p>
              </td>
              <td class="px-4 py-3">
                <p class="text-navy-900"><?= e($o['user_name']) ?></p>
                <p class="text-xs text-gray-400"><?= e($o['user_email']) ?></p>
              </td>
              <td class="px-4 py-3 text-right font-semibold text-navy-900"><?= e(formatRupiah($o['total'])) ?></td>
              <td class="px-4 py-3 text-xs uppercase text-gray-500"><?= e($o['payment_method']) ?></td>
              <td class="px-4 py-3"><span class="text-[11px] font-semibold px-2 py-1 rounded-full bg-amber-50 text-amber-700"><?= e(paymentStatusLabel($o['payment_status'], $o['payment_method'])) ?></span></td>
              <td class="px-4 py-3"><span class="text-[11px] font-semibold px-2 py-1 rounded-full <?= orderStatusBadgeClass($o['order_status']) ?>"><?= e(orderStatusLabel($o['order_status'])) ?></span></td>
              <td class="px-4 py-3 text-center">
                <a href="order-detail.php?order=<?= e($o['order_number']) ?>" class="text-cyan-600 hover:underline text-xs font-medium">Kelola</a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        </div>
      </div>

      <?php if ($totalPages > 1): ?>
      <div class="flex items-center justify-center gap-1.5 mt-6">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <a href="<?= e(adminOrdersUrl(['page' => $i])) ?>" class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium <?= $i === $page ? 'bg-cyan-500 text-navy-900' : 'border border-gray-200 text-gray-600 hover:bg-gray-50' ?>"><?= $i ?></a>
        <?php endfor; ?>
      </div>
      <?php endif; ?>
    </main>
  </div>
</div>
</body>
</html>