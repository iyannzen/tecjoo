<?php
/**
 * TechZone - Halaman Pembayaran Midtrans (Snap)
 * Membuat Snap token (sekali per order, disimpan di payments.snap_token) lalu membuka popup Snap.
 * Jumlah tagihan SELALU diambil dari database, bukan dari input pengguna.
 *
 * Alur konfirmasi admin: setelah user memilih metode & menekan bayar di Snap, pembayaran
 * diklaim (payment/midtrans-claim.php) -> status "Menunggu Verifikasi" -> admin konfirmasi
 * di admin/payments.php -> halaman ini otomatis berpindah ke "Pembayaran Berhasil".
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
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
        <p class="text-sm font-semibold text-navy-900">Pembayaran Dikonfirmasi</p>
        <p class="text-xs text-gray-500 mt-1">Terima kasih! Pembayaran Anda sudah dikonfirmasi admin dan pesanan sedang diproses.</p>
        <a href="<?= e($detailUrl) ?>" class="inline-block mt-5 ripple-wrap btn-glow bg-cyan-500 hover:bg-cyan-400 text-navy-900 font-semibold text-sm px-6 py-2.5 rounded-full transition">Lihat Detail Pesanan</a>
        <p class="text-[11px] text-gray-400 mt-3">Anda akan diarahkan ke detail pesanan otomatis...</p>
      </div>
      <script>setTimeout(function () { location.href = <?= json_encode($detailUrl) ?>; }, 3500);</script>

    <?php elseif ($order['payment_status'] === 'waiting_verification'): ?>
      <div class="py-8">
        <i class="fa-solid fa-clock text-amber-500 text-5xl mb-4 animate-bounce-slow"></i>
        <p class="text-sm font-semibold text-navy-900">Menunggu Konfirmasi Admin</p>
        <p class="text-xs text-gray-500 mt-1">Pembayaran <?= e(formatRupiah($order['total'])) ?><?= !empty($order['payment_channel']) ? ' via ' . e($order['payment_channel']) : '' ?> sedang diverifikasi tim TechZone. Halaman ini akan berpindah otomatis setelah dikonfirmasi.</p>
        <a href="<?= e($detailUrl) ?>" class="inline-block mt-5 border border-gray-200 text-gray-600 font-semibold text-sm px-6 py-2.5 rounded-full hover:bg-gray-50 transition">Lihat Detail Pesanan</a>
      </div>
      <script>
        (function () {
          var statusUrl = 'midtrans-status.php?order=<?= e(urlencode($orderNumber)) ?>';
          setInterval(function () {
            fetch(statusUrl, { credentials: 'same-origin' })
              .then(function (r) { return r.json(); })
              .then(function (d) { if (d.status && d.status !== 'waiting_verification') { location.reload(); } })
              .catch(function () {});
          }, 3000);
        })();
      </script>

    <?php elseif ($order['payment_status'] === 'expired' || $order['order_status'] === 'cancelled' || $order['payment_status'] === 'failed'): ?>
      <div class="py-8">
        <i class="fa-solid fa-circle-xmark text-red-500 text-5xl mb-4 animate-scale-in"></i>
        <p class="text-sm font-semibold text-navy-900">Pembayaran Tidak Dapat Dilanjutkan</p>
        <p class="text-xs text-gray-500 mt-1"><?= $order['payment_status'] === 'failed'
            ? 'Pembayaran ditolak admin atau gagal. Hubungi kami lewat Chat bila ini kesalahan, atau buat pesanan baru.'
            : 'Pesanan ini sudah dibatalkan atau kadaluarsa. Silakan buat pesanan baru.' ?></p>
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
        <p id="techzone-pay-hint" class="text-[11px] text-gray-400 mt-3">Pilih metode pembayaran (GoPay, BCA, dll) lalu tekan bayar. Pembayaran Anda akan dikonfirmasi oleh admin, dan halaman ini berpindah otomatis setelah dikonfirmasi.</p>

        <script src="<?= e(MIDTRANS_SNAP_JS) ?>" data-client-key="<?= e(MIDTRANS_CLIENT_KEY) ?>"></script>
        <script>
          (function () {
            var token = <?= json_encode($payment['snap_token']) ?>;
            var claimUrl = 'midtrans-claim.php';
            var selfUrl = 'midtrans.php?order=<?= e(urlencode($orderNumber)) ?>';
            var csrf = <?= json_encode(csrfToken()) ?>;
            var orderNumber = <?= json_encode($orderNumber) ?>;
            var btn = document.getElementById('techzone-pay-btn');
            var hint = document.getElementById('techzone-pay-hint');
            var busy = false;

            // Klaim pembayaran -> status "Menunggu Verifikasi" + notifikasi ke admin, lalu muat ulang halaman.
            function claim(source, result) {
              if (busy) return;
              busy = true;
              btn.disabled = true;
              hint.textContent = 'Mengirim pembayaran ke admin...';
              var body = new URLSearchParams();
              body.set('csrf_token', csrf);
              body.set('order', orderNumber);
              body.set('source', source);
              if (result && result.payment_type) body.set('payment_type', result.payment_type);
              if (result && result.va_numbers && result.va_numbers[0] && result.va_numbers[0].bank) body.set('bank', result.va_numbers[0].bank);
              fetch(claimUrl, { method: 'POST', credentials: 'same-origin', body: body })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                  if (d.status === 'unpaid') { // popup ditutup sebelum memilih metode
                    busy = false; btn.disabled = false;
                    hint.textContent = 'Pembayaran belum dipilih. Tekan "Bayar Sekarang" untuk melanjutkan.';
                    return;
                  }
                  location.replace(selfUrl);
                })
                .catch(function () {
                  busy = false; btn.disabled = false;
                  hint.textContent = 'Gagal mengirim pembayaran. Silakan coba lagi.';
                });
            }

            // Selama popup terbuka, cek ke server tiap 2 detik. Begitu VA/QR terbentuk (metode dipilih),
            // server otomatis memindahkan pembayaran ke admin -> popup ditutup & halaman menunggu konfirmasi.
            var statusUrl = 'midtrans-status.php?order=' + encodeURIComponent(orderNumber);

            // Tutup popup Snap sepenuhnya (tanpa perlu tombol kembali), lalu pindah ke halaman status.
            function closeSnapAndGo() {
              if (busy) return;
              busy = true;
              try { snap.hide(); } catch (e) {}
              ['snap-midtrans', 'snap-overlay'].forEach(function (id) {
                var el = document.getElementById(id);
                if (el && el.parentNode) el.parentNode.removeChild(el);
              });
              document.body.style.overflow = '';
              // replace: halaman Snap tidak menambah riwayat, jadi tombol kembali tidak diperlukan.
              location.replace(selfUrl);
            }

            setInterval(function () {
              if (busy) return;
              fetch(statusUrl, { credentials: 'same-origin', cache: 'no-store' })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                  if (d.status && d.status !== 'unpaid') closeSnapAndGo();
                }).catch(function () {});
            }, 1500);

            function openSnap() {
              snap.pay(token, {
                onSuccess: function (r) { claim('success', r); },
                onPending: function (r) { claim('pending', r); },
                onError:   function () { alert('Pembayaran gagal. Silakan coba lagi.'); },
                onClose:   function () { claim('close'); }
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
