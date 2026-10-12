<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/payment.php';

requireUserLogin();
$userId = (int) $_SESSION['user_id'];
$pdo    = getDbConnection();

$stmt = $pdo->prepare(
    "SELECT p.id AS payment_id, p.transaction_id, p.payment_method, p.amount, p.status AS payment_status, p.created_at AS payment_date,
            o.order_number, o.order_status
     FROM payments p JOIN orders o ON o.id = p.order_id
     WHERE o.user_id = :uid
     ORDER BY p.created_at DESC"
);
$stmt->execute([':uid' => $userId]);
$transactions = $stmt->fetchAll();

$pageTitle = 'Riwayat Transaksi | TechZone';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
  <div>
      <h1 class="reveal text-xl font-extrabold text-navy-900 mb-6">Riwayat Transaksi</h1>

      <?php if (empty($transactions)): ?>
        <div class="bg-white border border-gray-100 rounded-xl p-12 text-center shadow-card transition-shadow hover:shadow-lg">
          <i class="fa-solid fa-receipt text-4xl text-gray-200 mb-3 animate-float"></i>
          <p class="text-gray-500 text-sm">Belum ada transaksi.</p>
        </div>
      <?php else: ?>
        <div class="bg-white border border-gray-100 rounded-xl overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                <tr>
                  <th class="text-left px-4 py-3">ID Transaksi</th>
                  <th class="text-left px-4 py-3">No. Order</th>
                  <th class="text-left px-4 py-3">Tanggal</th>
                  <th class="text-right px-4 py-3">Total</th>
                  <th class="text-left px-4 py-3">Metode</th>
                  <th class="text-left px-4 py-3">Status Bayar</th>
                  <th class="text-left px-4 py-3">Status Order</th>
                  <th class="text-center px-4 py-3">Detail</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-50">
                <?php foreach ($transactions as $t): ?>
                <tr>
                  <td class="px-4 py-3 text-xs font-mono text-gray-500"><?= e($t['transaction_id']) ?></td>
                  <td class="px-4 py-3 font-medium text-navy-900"><?= e($t['order_number']) ?></td>
                  <td class="px-4 py-3 text-xs text-gray-500"><?= e(formatTanggalIndo($t['payment_date'], true)) ?></td>
                  <td class="px-4 py-3 text-right font-semibold text-navy-900"><?= e(formatRupiah($t['amount'])) ?></td>
                  <td class="px-4 py-3 text-xs uppercase text-gray-500"><?= e($t['payment_method']) ?></td>
                  <td class="px-4 py-3"><span class="text-[11px] font-semibold px-2 py-1 rounded-full bg-amber-50 text-amber-700"><?= e(paymentStatusLabel($t['payment_status'], $t['payment_method'])) ?></span></td>
                  <td class="px-4 py-3"><span class="text-[11px] font-semibold px-2 py-1 rounded-full bg-navy-50 text-navy-700"><?= e(orderStatusLabel($t['order_status'])) ?></span></td>
                  <td class="px-4 py-3 text-center">
                    <a href="order-detail.php?order=<?= e($t['order_number']) ?>" class="text-cyan-600 hover:underline text-xs font-medium">Lihat</a>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>