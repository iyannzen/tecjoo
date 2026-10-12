<?php
/**
 * TechZone - Halaman Pembayaran Midtrans (Snap)
 * Membuat Snap token (sekali per order, disimpan di payments.snap_token) lalu membuka popup Snap.
 * Jumlah tagihan SELALU diambil dari database, bukan dari input pengguna.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/midtrans_helpers.php';
require_once __DIR__ . '/../config/payment.php';

requireUserLogin();
$userId = (int) $_SESSION['user_id'];
$pdo    = getDbConnection();

$orderNumber = trim($_GET['order'] ?? '');
$orderStmt   = $pdo->prepare("SELECT * FROM orders WHERE order_number = :num AND user_id = :uid AND payment_method = 'midtrans' LIMIT 1");
$orderStmt->execute([':num' => $orderNumber, ':uid' => $userId]);
$order = $orderStmt->fetch();

if (!$order) {
    setFlashMessage('error', 'Pesanan Midtrans tidak ditemukan.');
    redirectTo('account/orders.php');
}

$loadPayment = function () use ($pdo, $order) {
    $s = $pdo->prepare('SELECT * FROM payments WHERE order_id = :id ORDER BY id DESC LIMIT 1');
    $s->execute([':id' => $order['id']]);
    return $s->fetch();
};
$payment = $loadPayment();

// Sinkronkan dulu dari Midtrans (jika webhook belum/tidak sampai, mis. di localhost).
if ($order['payment_status'] === 'unpaid' && $order['order_status'] === 'waiting_payment') {
    midtransSyncOrder($pdo, $orderNumber);
    $orderStmt->execute([':num' => $orderNumber, ':uid' => $userId]);
    $order   = $orderStmt->fetch();
    $payment = $loadPayment();
}

$errorMessage = null;
$canPay = $order['payment_status'] === 'unpaid' && $order['order_status'] === 'waiting_payment';

// Buat Snap token bila belum ada.
if ($canPay && empty($payment['snap_token'])) {
    $uStmt = $pdo->prepare('SELECT email FROM users WHERE id = :id');
    $uStmt->execute([':id' => $userId]);
    $user = $uStmt->fetch() ?: [];

    $res = midtransCreateSnapToken($pdo, $order, $user);
    if ($res['token']) {
        $expires = date('Y-m-d H:i:s', strtotime('+' . tzPaymentExpiryHours() . ' hours'));
        $pdo->prepare('UPDATE payments SET snap_token = :t, expires_at = :e, updated_at = NOW() WHERE id = :id')
            ->execute([':t' => $res['token'], ':e' => $expires, ':id' => $payment['id']]);
        $payment = $loadPayment();
    } else {
        $errorMessage = $res['error'];
    }
}

$pageTitle = 'Pembayaran Online | TechZone';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
$detailUrl = '../account/order-detail.php?order=' . urlencode($orderNumber);
?>
<main class="max-w-lg mx-auto px-4 py-10">
  <nav class="reveal text-xs text-gray-500 mb-4">
    <a href="<?= e($detailUrl) ?>" class="hover:text-cyan-600 transition-colors"><i class="fa-solid fa-arrow-left mr-1"></i>Kembali ke Detail Pesanan</a>
  </nav>

  <div class="reveal bg-white border border-gray-100 rounded-2xl p-6 text-center shadow-card">
    <span class="inline-flex w-12 h-12 rounded-xl bg-gradient-to-br from-cyan-500 to-blue-700 text-white items-center justify-center mb-3 shadow-sm">
      <i class="fa-solid fa-credit-card text-lg"></i>
    </span>
    <h1 class="text-base font-extrabold text-navy-900 mb-1">Pembayaran Online</h1>
    <p class="text-xs text-gray-400 mb-1">Virtual Account, QRIS, e-wallet, kartu &amp; gerai retail</p>
    <p class="text-xs text-gray-400 mb-5">Order: <span class="font-semibold text-navy-900"><?= e($order['order_number']) ?></span></p>

    <?php if ($order['payment_status'] === 'paid'): ?>
      <div class="py-8">
        <i class="fa-solid fa-circle-check text-emerald-500 text-5xl mb-4 animate-scale-in"></i>
        <p class="text-sm font-semibold text-navy-900">Pembayaran Berhasil</p>
        <p class="text-xs text-gray-500 mt-1">Terima kasih! Pesanan Anda sedang diproses.</p>
        <a href="<?= e($detailUrl) ?>" class="inline-block mt-5 ripple-wrap btn-glow bg-cyan-500 hover:bg-cyan-400 text-navy-900 font-semibold text-sm px-6 py-2.5 rounded-full transition">Lihat Detail Pesanan</a>
      </div>

    <?php elseif ($order['payment_status'] === 'expired' || $order['order_status'] === 'cancelled' || $order['payment_status'] === 'failed'): ?>
      <div class="py-8">
        <i class="fa-solid fa-circle-xmark text-red-500 text-5xl mb-4 animate-scale-in"></i>
        <p class="text-sm font-semibold text-navy-900">Pembayaran Tidak Dapat Dilanjutkan</p>
        <p class="text-xs text-gray-500 mt-1">Pesanan ini sudah dibatalkan atau kadaluarsa. Silakan buat pesanan baru.</p>
        <a href="../products.php" class="inline-block mt-5 border border-gray-200 text-gray-600 font-semibold text-sm px-6 py-2.5 rounded-full hover:bg-gray-50 transition">Belanja Lagi</a>
      </div>

    <?php else: ?>
      <p class="text-2xl font-extrabold text-navy-900 mb-5"><?= e(formatRupiah($order['total'])) ?></p>

      <?php if ($errorMessage || empty($payment['snap_token'])): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-lg p-3 mb-4 text-left">
          <i class="fa-solid fa-triangle-exclamation mr-1"></i>Tidak dapat memulai pembayaran: <?= e($errorMessage ?: 'Token pembayaran tidak tersedia.') ?>
        </div>
        <a href="" class="inline-block ripple-wrap btn-glow bg-cyan-500 hover:bg-cyan-400 text-navy-900 font-semibold text-sm px-6 py-2.5 rounded-full transition">Coba Lagi</a>
      <?php else: ?>
        <?php if (MIDTRANS_IS_PRODUCTION === false): ?>
          <p class="text-[10px] text-amber-600 bg-amber-50 rounded-md py-1 px-2 mb-3 inline-block">MODE SANDBOX &mdash; transaksi uji coba</p>
        <?php endif; ?>
        <button id="techzone-pay-btn" type="button" class="relative w-full ripple-wrap btn-glow bg-cyan-500 hover:bg-cyan-400 text-navy-900 font-semibold py-3 rounded-lg transition">
          <i class="fa-solid fa-lock mr-1.5"></i>Bayar Sekarang
        </button>
        <p id="techzone-pay-hint" class="text-[11px] text-gray-400 mt-3">Anda akan memilih metode pembayaran di jendela aman Midtrans. Status akan diperbarui otomatis setelah pembayaran diterima.</p>

        <script src="<?= e(MIDTRANS_SNAP_JS) ?>" data-client-key="<?= e(MIDTRANS_CLIENT_KEY) ?>"></script>
        <script>
          (function () {
            var token = <?= json_encode($payment['snap_token']) ?>;
            var statusUrl = 'midtrans-status.php?order=<?= e(urlencode($orderNumber)) ?>';
            var selfUrl = 'midtrans.php?order=<?= e(urlencode($orderNumber)) ?>';
            var btn = document.getElementById('techzone-pay-btn');
            var hint = document.getElementById('techzone-pay-hint');
            var polling = null;

            function startPolling() {
              if (polling) return;
              hint.textContent = 'Menunggu konfirmasi pembayaran dari Midtrans...';
              polling = setInterval(function () {
                fetch(statusUrl, { credentials: 'same-origin' })
                  .then(function (r) { return r.json(); })
                  .then(function (d) {
                    if (d.status && d.status !== 'unpaid') { location.href = selfUrl; }
                  }).catch(function () {});
              }, 5000);
            }

            function openSnap() {
              snap.pay(token, {
                onSuccess: function () { location.href = selfUrl; },
                onPending: function () { startPolling(); },
                onError:   function () { alert('Pembayaran gagal. Silakan coba lagi.'); },
                onClose:   function () { startPolling(); }
              });
            }
            btn.addEventListener('click', openSnap);
            openSnap(); // buka otomatis saat halaman dimuat
          })();
        </script>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
