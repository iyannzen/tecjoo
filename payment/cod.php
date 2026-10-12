<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/payment.php';

requireUserLogin();
$userId = (int) $_SESSION['user_id'];
$pdo    = getDbConnection();

$orderNumber = trim($_GET['order'] ?? '');
$orderStmt   = $pdo->prepare("SELECT * FROM orders WHERE order_number = :num AND user_id = :uid AND payment_method = 'cod' LIMIT 1");
$orderStmt->execute([':num' => $orderNumber, ':uid' => $userId]);
$order = $orderStmt->fetch();

if (!$order) {
    setFlashMessage('error', 'Pesanan COD tidak ditemukan.');
    redirectTo('account/orders.php');
}

$pageTitle = 'Pesanan COD | TechZone';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<main class="max-w-md mx-auto px-4 py-10">
  <div class="reveal bg-white border border-gray-100 rounded-2xl p-8 text-center shadow-card">
    <i class="fa-solid fa-hand-holding-dollar text-cyan-500 text-5xl mb-4"></i>
    <h1 class="text-base font-extrabold text-navy-900 mb-1">Pesanan COD (Bayar di Tempat)</h1>
    <p class="text-xs text-gray-400 mb-5">Order: <span class="font-semibold text-navy-900"><?= e($order['order_number']) ?></span></p>

    <p class="text-sm text-gray-600 mb-1">Total yang perlu dibayar saat barang tiba:</p>
    <p class="text-2xl font-extrabold text-navy-900 mb-5"><?= e(formatRupiah($order['total'])) ?></p>

    <div class="bg-emerald-50 text-emerald-700 text-xs font-medium rounded-lg py-2.5 px-4 mb-5">
      Pesanan Anda sudah diterima dan sedang diproses oleh tim TechZone. Tidak ada pembayaran online yang diperlukan.
    </div>

    <a href="../account/order-detail.php?order=<?= e($order['order_number']) ?>"
       class="inline-block ripple-wrap btn-glow bg-cyan-500 hover:bg-cyan-400 text-navy-900 font-semibold text-sm px-6 py-2.5 rounded-full transition">
      Lihat Detail Pesanan
    </a>
  </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
