<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/payment_helpers.php';
require_once __DIR__ . '/../config/payment.php';

requireUserLogin();
$userId = (int) $_SESSION['user_id'];
$pdo    = getDbConnection();

$orderNumber = trim($_GET['order'] ?? '');
$orderStmt   = $pdo->prepare("SELECT * FROM orders WHERE order_number = :num AND user_id = :uid AND payment_method = 'qris' LIMIT 1");
$orderStmt->execute([':num' => $orderNumber, ':uid' => $userId]);
$order = $orderStmt->fetch();

if (!$order) {
    setFlashMessage('error', 'Pesanan QRIS tidak ditemukan.');
    redirectTo('account/orders.php');
}

$paymentStmt = $pdo->prepare('SELECT * FROM payments WHERE order_id = :id ORDER BY id DESC LIMIT 1');
$paymentStmt->execute([':id' => $order['id']]);
$payment = $paymentStmt->fetch();

[$order, $payment] = autoExpirePaymentIfNeeded($pdo, $order, $payment);

$pageTitle = 'Pembayaran QRIS | TechZone';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<main class="max-w-lg mx-auto px-4 py-10">
  <nav class="reveal text-xs text-gray-500 mb-4">
    <a href="../account/order-detail.php?order=<?= e($orderNumber) ?>" class="hover:text-cyan-600 transition-colors"><i class="fa-solid fa-arrow-left mr-1"></i>Kembali ke Detail Pesanan</a>
  </nav>

  <div class="reveal bg-white border border-gray-100 rounded-2xl p-6 text-center shadow-card">
    <span class="inline-flex w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-purple-700 text-white items-center justify-center mb-3 shadow-sm">
      <i class="fa-solid fa-qrcode text-lg"></i>
    </span>
    <h1 class="text-base font-extrabold text-navy-900 mb-1">Pembayaran QRIS</h1>
    <?php if (!empty($order['payment_channel'])): ?>
      <p class="text-xs text-cyan-600 font-semibold mb-1">Bayar dengan <?= e($order['payment_channel']) ?></p>
    <?php endif; ?>
    <p class="text-xs text-gray-400 mb-5">Order: <span class="font-semibold text-navy-900"><?= e($order['order_number']) ?></span></p>

    <?php if ($payment['status'] === 'paid'): ?>
      <div class="py-10">
        <i class="fa-solid fa-circle-check text-emerald-500 text-5xl mb-4 animate-scale-in"></i>
        <p class="text-sm font-semibold text-navy-900">Pembayaran Berhasil Diverifikasi</p>
        <p class="text-xs text-gray-500 mt-1">Pesanan Anda sedang diproses.</p>
        <a href="../account/order-detail.php?order=<?= e($orderNumber) ?>" class="inline-block mt-5 ripple-wrap btn-glow bg-cyan-500 hover:bg-cyan-400 text-navy-900 font-semibold text-sm px-6 py-2.5 rounded-full transition">Lihat Detail Pesanan</a>
      </div>

    <?php elseif ($payment['status'] === 'waiting_verification'): ?>
      <div class="py-10">
        <i class="fa-solid fa-clock text-amber-500 text-5xl mb-4 animate-bounce-slow"></i>
        <p class="text-sm font-semibold text-navy-900">Menunggu Verifikasi Admin</p>
        <p class="text-xs text-gray-500 mt-1">Klaim pembayaran Anda sedang diperiksa oleh tim TechZone.</p>
        <p class="text-xs text-gray-400 mt-3">Total: <span class="font-semibold text-navy-900"><?= e(formatRupiah($payment['amount'])) ?></span></p>
      </div>

    <?php elseif ($payment['status'] === 'expired'): ?>
      <div class="py-10">
        <i class="fa-solid fa-circle-xmark text-red-500 text-5xl mb-4 animate-scale-in"></i>
        <p class="text-sm font-semibold text-navy-900">Waktu Pembayaran Habis</p>
        <p class="text-xs text-gray-500 mt-1">Silakan hubungi kami atau buat pesanan baru.</p>
        <a href="../contact.php" class="inline-block mt-5 border border-gray-200 text-gray-600 font-semibold text-sm px-6 py-2.5 rounded-full hover:bg-gray-50 transition">Hubungi Kami</a>
      </div>

    <?php else: // unpaid ?>
      <p class="text-2xl font-extrabold text-navy-900 mb-5"><?= e(formatRupiah($payment['amount'])) ?></p>

      <div class="w-48 h-48 mx-auto mb-4 border border-gray-100 rounded-xl p-2 shadow-card animate-scale-in">
        <?= renderFakeQrSvg($order['order_number']) ?>
      </div>
      <p class="text-[11px] text-gray-400 mb-1">Scan menggunakan aplikasi QRIS favorit Anda</p>
      <p class="text-[10px] text-gray-300 mb-5 italic">*QR code simulasi — tidak terhubung ke aplikasi pembayaran mana pun</p>

      <div id="techzone-countdown" class="relative overflow-hidden bg-navy-900 text-white rounded-lg py-3 mb-5 animate-pulse-glow"><div class="absolute inset-0 grid-pattern opacity-20"></div>
        <p class="text-[10px] text-gray-400 uppercase tracking-wide">Batas Waktu Pembayaran</p>
        <p id="techzone-countdown-timer" class="text-xl font-extrabold text-cyan-400 font-mono">--:--</p>
      </div>

      <form action="../actions/payment.php" method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="mark_paid">
        <input type="hidden" name="order_number" value="<?= e($order['order_number']) ?>">
        <button type="submit" class="relative w-full ripple-wrap btn-glow bg-cyan-500 hover:bg-cyan-400 text-navy-900 font-semibold py-3 rounded-lg transition">
          Saya Sudah Membayar
        </button>
      </form>
      <p class="text-[11px] text-gray-400 mt-3">Setelah menekan tombol ini, status akan berubah menjadi "Menunggu Verifikasi" hingga admin mengonfirmasi.</p>

      <script>
        var techzoneExpiresAt = new Date('<?= date('c', strtotime($payment['expires_at'])) ?>').getTime();
        function techzoneTick() {
          var now = new Date().getTime();
          var diff = techzoneExpiresAt - now;
          var el = document.getElementById('techzone-countdown-timer');
          if (diff <= 0) {
            el.textContent = '00:00';
            location.reload();
            return;
          }
          var m = Math.floor(diff / 60000);
          var s = Math.floor((diff % 60000) / 1000);
          el.textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
        }
        techzoneTick();
        setInterval(techzoneTick, 1000);
      </script>
    <?php endif; ?>
  </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
