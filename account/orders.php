<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/payment.php';

requireUserLogin();
$userId = (int) $_SESSION['user_id'];
$pdo    = getDbConnection();

$statusFilter = $_GET['status'] ?? '';
$where  = ['user_id = :uid'];
$params = [':uid' => $userId];

if ($statusFilter !== '' && in_array($statusFilter, getValidOrderStatuses(), true)) {
    $where[] = 'order_status = :status';
    $params[':status'] = $statusFilter;
}

$stmt = $pdo->prepare('SELECT * FROM orders WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC');
$stmt->execute($params);
$orders = $stmt->fetchAll();

$pageTitle = 'Pesanan Saya | TechZone';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';

$statusTabs = [
    ''                 => 'Semua',
    'waiting_payment'  => 'Menunggu Pembayaran',
    'processing'       => 'Diproses',
    'shipped'          => 'Dikirim',
    'completed'        => 'Selesai',
    'refund_requested' => 'Pengembalian Dana',
    'cancelled'        => 'Dibatalkan',
];
?>
<main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
  <div>
      <h1 class="reveal text-xl font-extrabold text-navy-900 mb-4 flex items-center gap-2"><i class="fa-solid fa-box text-cyan-500"></i> Pesanan Saya</h1>

      <div class="reveal flex flex-wrap gap-2 mb-6">
        <?php foreach ($statusTabs as $key => $label): ?>
          <a href="orders.php<?= $key !== '' ? '?status=' . $key : '' ?>"
             class="text-xs font-medium px-3 py-1.5 rounded-full transition-all duration-200 <?= $statusFilter === $key ? 'bg-navy-900 text-white shadow-glow scale-105' : 'bg-white border border-gray-200 text-gray-600 hover:border-cyan-300 hover:text-cyan-600' ?>">
            <?= e($label) ?>
          </a>
        <?php endforeach; ?>
      </div>

      <?php if (empty($orders)): ?>
        <div class="reveal bg-white border border-gray-100 rounded-xl p-12 text-center shadow-card">
          <i class="fa-solid fa-box-open text-4xl text-gray-200 mb-3 animate-float animate-float"></i>
          <p class="text-gray-500 text-sm mb-4">Belum ada pesanan.</p>
          <a href="../products.php" class="inline-block ripple-wrap btn-glow bg-cyan-500 hover:bg-cyan-400 text-navy-900 font-semibold px-6 py-2.5 rounded-full transition">Mulai Belanja</a>
        </div>
      <?php else: ?>
        <div class="space-y-3">
          <?php foreach ($orders as $i => $order): ?>
          <a href="order-detail.php?order=<?= e($order['order_number']) ?>"
             class="reveal block bg-white border border-gray-100 hover:border-cyan-300 rounded-xl p-5 card-hover" style="transition-delay: <?= min($i * 50, 300) ?>ms">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
              <p class="text-sm font-bold text-navy-900"><?= e($order['order_number']) ?></p>
              <span class="text-xs text-gray-400"><?= e(formatTanggalIndo($order['created_at'], true)) ?></span>
            </div>
            <div class="flex flex-wrap items-center gap-2 mb-2">
              <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full <?= orderStatusBadgeClass($order['order_status']) ?>"><?= e(orderStatusLabel($order['order_status'])) ?></span>
              <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-amber-50 text-amber-700"><?= e(paymentStatusLabel($order['payment_status'], $order['payment_method'])) ?></span>
              <span class="text-[11px] text-gray-400 uppercase"><?= e($order['payment_method']) ?></span>
            </div>
            <p class="text-sm font-extrabold text-navy-900"><?= e(formatRupiah($order['total'])) ?></p>
          </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>