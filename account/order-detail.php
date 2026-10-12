<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/payment.php';
require_once __DIR__ . '/../includes/automation_helpers.php';

requireUserLogin();
$userId = (int) $_SESSION['user_id'];
$pdo    = getDbConnection();

$orderNumber = trim($_GET['order'] ?? '');
if ($orderNumber === '') {
    redirectTo('account/orders.php');
}

$orderStmt = $pdo->prepare('SELECT * FROM orders WHERE order_number = :num AND user_id = :uid LIMIT 1');
$orderStmt->execute([':num' => $orderNumber, ':uid' => $userId]);
$order = $orderStmt->fetch();

if (!$order) {
    setFlashMessage('error', 'Pesanan tidak ditemukan.');
    redirectTo('account/orders.php');
}

$itemsStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = :id');
$itemsStmt->execute([':id' => $order['id']]);
$orderItems = $itemsStmt->fetchAll();

$paymentStmt = $pdo->prepare('SELECT * FROM payments WHERE order_id = :id ORDER BY id DESC LIMIT 1');
$paymentStmt->execute([':id' => $order['id']]);
$payment = $paymentStmt->fetch();

// Ambil review yang sudah pernah diberikan user untuk order ini (untuk sembunyikan form jika sudah review).
$reviewedProductIds = [];
if ($order['order_status'] === 'completed') {
    $reviewedStmt = $pdo->prepare('SELECT product_id FROM reviews WHERE order_id = :oid AND user_id = :uid');
    $reviewedStmt->execute([':oid' => $order['id'], ':uid' => $userId]);
    $reviewedProductIds = array_column($reviewedStmt->fetchAll(), 'product_id');
}

// Linimasa pelacakan, batas waktu bayar, dan tautan lacak paket.
$historyRows = getOrderHistory($pdo, (int) $order['id']);
$timeline    = buildOrderTimeline($order, $historyRows);
$deadline    = tzOrderPaymentDeadline($order);
$trackUrl    = tzTrackingUrl($order['courier'], $order['tracking_number']);

$pageTitle = 'Detail Pesanan ' . e($order['order_number']) . ' | TechZone';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
  <nav class="text-xs text-gray-500 mb-4">
    <a href="orders.php" class="hover:text-cyan-600">Pesanan Saya</a> <span class="mx-1">/</span>
    <span class="text-navy-900 font-medium"><?= e($order['order_number']) ?></span>
  </nav>

  <div class="reveal flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
      <h1 class="text-xl font-extrabold text-navy-900"><?= e($order['order_number']) ?></h1>
      <p class="text-xs text-gray-400 mt-1"><?= e(formatTanggalIndo($order['created_at'], true)) ?></p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
      <span class="text-xs font-semibold px-3 py-1.5 rounded-full <?= orderStatusBadgeClass($order['order_status']) ?>"><?= e(orderStatusLabel($order['order_status'])) ?></span>
      <span class="text-xs font-semibold px-3 py-1.5 rounded-full bg-amber-50 text-amber-700"><?= e(paymentStatusLabel($order['payment_status'], $order['payment_method'])) ?></span>
      <a href="invoice.php?order=<?= e($order['order_number']) ?>" target="_blank"
         class="text-xs font-semibold px-3 py-1.5 rounded-full border border-gray-200 text-gray-600 hover:border-cyan-300 hover:text-cyan-600 transition-colors">
        <i class="fa-solid fa-file-invoice mr-1"></i>Invoice
      </a>
    </div>
  </div>

  <?php if ($deadline): ?>
  <div class="tz-deadline reveal" data-countdown="<?= e($deadline->format('c')) ?>">
    <span class="tz-deadline__ico"><i class="fa-regular fa-clock"></i></span>
    <div>
      <b>Selesaikan pembayaran sebelum <?= e(formatTanggalIndo($deadline->format('Y-m-d H:i:s'), true)) ?></b>
      <small>Sisa waktu: <strong data-countdown-text>--</strong> &middot; setelah itu pesanan dibatalkan otomatis dan stok dikembalikan.</small>
    </div>
  </div>
  <?php endif; ?>

  <section class="tz-track reveal">
    <div class="tz-track__head">
      <h2>Lacak Pesanan</h2>
      <?php if ($trackUrl): ?>
        <a href="<?= e($trackUrl) ?>" target="_blank" rel="noopener" class="tz-track__btn"><i class="fa-solid fa-location-crosshairs"></i> Lacak Paket</a>
      <?php endif; ?>
    </div>

    <?php if ($timeline['special'] === null): ?>
      <ol class="tz-track__steps">
        <?php foreach ($timeline['steps'] as $st): ?>
          <li class="tz-track__step <?= $st['reached'] ? 'is-done' : '' ?> <?= $st['current'] ? 'is-current' : '' ?>">
            <span class="tz-track__dot"><i class="fa-solid <?= e($st['icon']) ?>"></i></span>
            <b><?= e($st['label']) ?></b>
            <small><?= $st['time'] ? e(formatTanggalIndo($st['time'], true)) : ($st['reached'] ? '&nbsp;' : 'Menunggu') ?></small>
          </li>
        <?php endforeach; ?>
      </ol>
    <?php else: ?>
      <div class="tz-track__special tz-track__special--<?= e($timeline['special']) ?>">
        <i class="fa-solid <?= $timeline['special'] === 'cancelled' ? 'fa-circle-xmark' : 'fa-rotate-left' ?>"></i>
        <div>
          <b><?= e(orderStatusLabel($order['order_status'])) ?></b>
          <?php if (!empty($order['cancel_reason']) && $order['order_status'] === 'cancelled'): ?><small><?= e($order['cancel_reason']) ?></small><?php endif; ?>
          <?php if (!empty($order['refund_reason']) && $order['order_status'] !== 'cancelled'): ?><small><?= e($order['refund_reason']) ?></small><?php endif; ?>
        </div>
      </div>
      <?php if (!empty($historyRows)): ?>
      <ul class="tz-track__log">
        <?php foreach ($historyRows as $h): ?>
          <li><span><?= e(orderStatusLabel($h['status'])) ?></span><time><?= e(formatTanggalIndo($h['created_at'], true)) ?></time></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    <?php endif; ?>

    <?php if (!empty($order['tracking_number'])): ?>
      <div class="tz-track__resi">
        <div><small>Kurir</small><b><?= e($order['courier'] ?: '-') ?></b></div>
        <div><small>Nomor Resi</small><b id="tz-resi"><?= e($order['tracking_number']) ?></b></div>
        <button type="button" class="tz-track__copy" data-copy="<?= e($order['tracking_number']) ?>"><i class="fa-regular fa-copy"></i> <span>Salin Resi</span></button>
      </div>
    <?php endif; ?>
  </section>

  <?php if (in_array($order['payment_status'], ['unpaid'], true) && $order['order_status'] === 'waiting_payment'): ?>
    <div class="reveal bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-lg p-4 mb-4 flex items-center justify-between flex-wrap gap-3">
      <span><i class="fa-solid fa-triangle-exclamation mr-2"></i>Pesanan ini belum dibayar.</span>
      <a href="../payment/index.php?order=<?= e($order['order_number']) ?>"
         class="ripple-wrap btn-glow bg-navy-900 text-white text-xs font-semibold px-4 py-2 rounded-lg hover:bg-navy-800 transition">
        Bayar Sekarang
      </a>
    </div>
    <div class="reveal bg-white border border-gray-100 rounded-lg p-4 mb-6 flex items-center justify-between flex-wrap gap-3">
      <span class="text-sm text-gray-500"><i class="fa-regular fa-circle-question mr-1.5 text-gray-400"></i>Berubah pikiran? Anda masih bisa membatalkan pesanan ini sebelum membayar.</span>
      <button type="button" onclick="document.getElementById('techzone-cancel-modal').classList.remove('hidden')"
              class="text-red-600 border border-red-200 text-xs font-semibold px-4 py-2 rounded-lg hover:bg-red-50 transition-colors">
        <i class="fa-solid fa-ban mr-1.5"></i>Batalkan Pesanan
      </button>
    </div>
  <?php elseif ($order['payment_status'] === 'waiting_verification'): ?>
    <div class="reveal bg-cyan-50 border border-cyan-200 text-cyan-800 text-sm rounded-lg p-4 mb-6 flex items-center justify-between flex-wrap gap-3">
      <span><i class="fa-solid fa-clock mr-2"></i>Menunggu verifikasi admin. Transaksi: <strong><?= e($payment['transaction_id'] ?? '-') ?></strong></span>
      <a href="../payment/index.php?order=<?= e($order['order_number']) ?>" class="text-cyan-700 text-xs font-semibold underline">Lihat Status</a>
    </div>
  <?php endif; ?>

  <?php if ($order['payment_status'] === 'paid' && !in_array($order['order_status'], ['cancelled', 'refund_requested', 'returned'], true)): ?>
    <div class="reveal bg-white border border-gray-100 rounded-lg p-4 mb-6 flex items-center justify-between flex-wrap gap-3">
      <span class="text-sm text-gray-500"><i class="fa-solid fa-rotate-left mr-1.5 text-gray-400"></i>Ada masalah dengan pesanan yang sudah dibayar ini?</span>
      <button type="button" onclick="document.getElementById('techzone-refund-modal').classList.remove('hidden')"
              class="text-orange-600 border border-orange-200 text-xs font-semibold px-4 py-2 rounded-lg hover:bg-orange-50 transition-colors">
        <i class="fa-solid fa-hand-holding-dollar mr-1.5"></i>Ajukan Pengembalian Dana
      </button>
    </div>
  <?php elseif ($order['order_status'] === 'refund_requested'): ?>
    <div class="reveal bg-orange-50 border border-orange-200 text-orange-800 text-sm rounded-lg p-4 mb-6">
      <p><i class="fa-solid fa-hourglass-half mr-2"></i>Pengajuan pengembalian dana sedang ditinjau tim kami (1-3 hari kerja).</p>
      <?php if (!empty($order['refund_reason'])): ?><p class="text-xs text-orange-700 mt-1">Alasan Anda: <?= e($order['refund_reason']) ?></p><?php endif; ?>
    </div>
  <?php elseif ($order['order_status'] === 'cancelled'): ?>
    <div class="reveal bg-gray-50 border border-gray-200 text-gray-600 text-sm rounded-lg p-4 mb-6">
      <p><i class="fa-solid fa-ban mr-2"></i>Pesanan ini telah dibatalkan.</p>
      <?php if (!empty($order['cancel_reason'])): ?><p class="text-xs text-gray-500 mt-1">Alasan: <?= e($order['cancel_reason']) ?></p><?php endif; ?>
    </div>
  <?php elseif ($order['order_status'] === 'returned'): ?>
    <div class="reveal bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-4 mb-6">
      <p><i class="fa-solid fa-circle-check mr-2"></i>Dana untuk pesanan ini telah dikembalikan.</p>
    </div>
  <?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
      <div class="bg-white border border-gray-100 rounded-xl p-5 shadow-card transition-shadow hover:shadow-lg">
        <h2 class="text-sm font-bold text-navy-900 mb-4">Produk Dipesan</h2>
        <div class="space-y-3">
          <?php foreach ($orderItems as $item): ?>
          <div class="border-b border-gray-50 pb-3 last:border-0 last:pb-0">
            <div class="flex items-center justify-between text-sm">
              <div>
                <p class="font-medium text-navy-900"><?= e($item['product_name']) ?></p>
                <p class="text-xs text-gray-400">SKU: <?= e($item['sku']) ?> &middot; <?= (int) $item['quantity'] ?> x <?= e(formatRupiah($item['price'])) ?></p>
              </div>
              <p class="font-semibold text-navy-900"><?= e(formatRupiah($item['subtotal'])) ?></p>
            </div>

            <?php if ($order['order_status'] === 'completed' && $item['product_id']): ?>
              <?php if (in_array((int) $item['product_id'], $reviewedProductIds, true)): ?>
                <p class="text-[11px] text-emerald-600 mt-2"><i class="fa-solid fa-check-circle mr-1"></i>Anda sudah memberikan review untuk produk ini.</p>
              <?php else: ?>
                <button type="button" onclick="document.getElementById('review-form-<?= (int) $item['product_id'] ?>').classList.toggle('hidden')"
                        class="text-[11px] text-cyan-600 font-medium hover:underline mt-2">
                  <i class="fa-solid fa-star mr-1"></i>Beri Review
                </button>
                <form id="review-form-<?= (int) $item['product_id'] ?>" action="<?= e(techzoneBaseUrl()) ?>/actions/review.php" method="POST" class="hidden mt-3 bg-gray-50 rounded-lg p-3 space-y-2">
                  <?= csrfField() ?>
                  <input type="hidden" name="order_number" value="<?= e($order['order_number']) ?>">
                  <input type="hidden" name="product_id" value="<?= (int) $item['product_id'] ?>">
                  <div class="star-rating flex items-center gap-1" data-rating-group="rating-<?= (int) $item['product_id'] ?>">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <label class="cursor-pointer text-lg star-rating-label">
                        <input type="radio" name="rating" value="<?= $i ?>" class="sr-only star-rating-input" <?= $i === 5 ? 'checked' : '' ?>>
                        <i class="fa-solid fa-star star-rating-icon text-amber-400"></i>
                      </label>
                    <?php endfor; ?>
                    <span class="text-[10px] text-gray-400 ml-1">(pilih rating)</span>
                  </div>
                  <textarea name="review" rows="2" required placeholder="Bagaimana pengalaman Anda dengan produk ini?"
                            class="w-full text-xs border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200"></textarea>
                  <button type="submit" class="text-xs bg-navy-900 text-white font-semibold px-4 py-1.5 rounded-lg hover:bg-navy-800">Kirim Review</button>
                </form>
              <?php endif; ?>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="bg-white border border-gray-100 rounded-xl p-5 shadow-card transition-shadow hover:shadow-lg">
        <h2 class="text-sm font-bold text-navy-900 mb-3">Alamat Pengiriman</h2>
        <p class="text-sm font-medium text-navy-900"><?= e($order['recipient_name']) ?> <span class="text-gray-400 font-normal">| <?= e($order['recipient_phone']) ?></span></p>
        <p class="text-sm text-gray-600 mt-1"><?= e($order['shipping_address']) ?>, <?= e($order['shipping_city']) ?>, <?= e($order['shipping_province']) ?> <?= e($order['shipping_postal_code']) ?></p>
        <p class="text-xs text-gray-400 mt-2">Metode: <?= e(ucfirst($order['shipping_method'])) ?></p>
        <?php if ($order['courier'] || $order['tracking_number']): ?>
          <p class="text-xs text-gray-400 mt-1">Kurir: <?= e($order['courier'] ?: '-') ?> &middot; Resi: <?= e($order['tracking_number'] ?: '-') ?></p>
        <?php endif; ?>
      </div>
    </div>

    <div>
      <div class="bg-white border border-gray-100 rounded-xl p-5 sticky top-20 shadow-card transition-shadow hover:shadow-lg">
        <h2 class="text-sm font-bold text-navy-900 mb-4">Ringkasan Pembayaran</h2>
        <div class="space-y-2 text-sm text-gray-600">
          <div class="flex justify-between"><span>Subtotal</span><span class="font-medium text-navy-900"><?= e(formatRupiah($order['subtotal'])) ?></span></div>
          <div class="flex justify-between"><span>Ongkos Kirim</span><span class="font-medium text-navy-900"><?= e(formatRupiah($order['shipping_cost'])) ?></span></div>
          <?php if ((float) $order['discount'] > 0): ?>
            <div class="flex justify-between text-emerald-600"><span>Diskon <?= $order['coupon_code'] ? '(' . e($order['coupon_code']) . ')' : '' ?></span><span>-<?= e(formatRupiah($order['discount'])) ?></span></div>
          <?php endif; ?>
        </div>
        <div class="flex justify-between items-baseline border-t border-gray-100 mt-3 pt-3">
          <span class="text-sm font-semibold text-navy-900">Total</span>
          <span class="text-lg font-extrabold text-navy-900"><?= e(formatRupiah($order['total'])) ?></span>
        </div>
        <p class="text-xs text-gray-400 mt-3">Metode Pembayaran: <span class="uppercase font-medium text-navy-900"><?= e($order['payment_method']) ?></span><?php if (!empty($order['payment_channel'])): ?> <span class="text-cyan-600 font-medium">(<?= e($order['payment_channel']) ?>)</span><?php endif; ?></p>
      </div>
    </div>
  </div>

  <!-- MODAL: BATALKAN PESANAN -->
  <div id="techzone-cancel-modal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-sm w-full p-6 animate-scale-in">
      <div class="w-12 h-12 rounded-full bg-red-50 text-red-500 flex items-center justify-center mb-3"><i class="fa-solid fa-triangle-exclamation text-lg"></i></div>
      <h3 class="text-base font-bold text-navy-900 mb-1">Batalkan Pesanan?</h3>
      <p class="text-sm text-gray-500 mb-4">Pesanan <strong><?= e($order['order_number']) ?></strong> akan dibatalkan dan tidak bisa dikembalikan lagi. Stok produk akan dikembalikan otomatis.</p>
      <form action="<?= e(techzoneBaseUrl()) ?>/actions/order-cancel.php" method="POST" class="space-y-3">
        <?= csrfField() ?>
        <input type="hidden" name="order_number" value="<?= e($order['order_number']) ?>">
        <textarea name="cancel_reason" rows="2" placeholder="Alasan pembatalan (opsional)"
                  class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-400 transition-all duration-200"></textarea>
        <div class="flex gap-2">
          <button type="button" onclick="document.getElementById('techzone-cancel-modal').classList.add('hidden')"
                  class="flex-1 text-sm font-medium text-gray-600 border border-gray-200 rounded-lg py-2.5 hover:bg-gray-50 transition-colors">Tidak Jadi</button>
          <button type="submit" class="ripple-wrap flex-1 text-sm font-semibold text-white bg-red-500 hover:bg-red-600 rounded-lg py-2.5 transition-colors">Ya, Batalkan</button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL: AJUKAN PENGEMBALIAN DANA -->
  <div id="techzone-refund-modal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-sm w-full p-6 animate-scale-in">
      <div class="w-12 h-12 rounded-full bg-orange-50 text-orange-500 flex items-center justify-center mb-3"><i class="fa-solid fa-hand-holding-dollar text-lg"></i></div>
      <h3 class="text-base font-bold text-navy-900 mb-1">Ajukan Pengembalian Dana</h3>
      <p class="text-sm text-gray-500 mb-4">Jelaskan kendala pada pesanan <strong><?= e($order['order_number']) ?></strong>. Tim kami akan meninjau dan memproses pengembalian dana dalam 1-3 hari kerja.</p>
      <form action="<?= e(techzoneBaseUrl()) ?>/actions/order-refund.php" method="POST" class="space-y-3">
        <?= csrfField() ?>
        <input type="hidden" name="order_number" value="<?= e($order['order_number']) ?>">
        <textarea name="refund_reason" rows="3" required placeholder="Contoh: barang tidak sesuai deskripsi, produk rusak, dll."
                  class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-400 transition-all duration-200"></textarea>
        <div class="flex gap-2">
          <button type="button" onclick="document.getElementById('techzone-refund-modal').classList.add('hidden')"
                  class="flex-1 text-sm font-medium text-gray-600 border border-gray-200 rounded-lg py-2.5 hover:bg-gray-50 transition-colors">Batal</button>
          <button type="submit" class="ripple-wrap flex-1 text-sm font-semibold text-white bg-orange-500 hover:bg-orange-600 rounded-lg py-2.5 transition-colors">Kirim Pengajuan</button>
        </div>
      </form>
    </div>
  </div>
</main>
<script>
  // Widget rating bintang: klik salah satu bintang untuk memilih rating 1-5.
  // Bintang <= rating terpilih ditampilkan solid amber, sisanya abu-abu.
  document.querySelectorAll('.star-rating').forEach(function (group) {
    var inputs = group.querySelectorAll('.star-rating-input');
    var icons  = group.querySelectorAll('.star-rating-icon');

    function render() {
      var selected = 0;
      inputs.forEach(function (input, idx) {
        if (input.checked) selected = idx + 1;
      });
      icons.forEach(function (icon, idx) {
        if (idx < selected) {
          icon.classList.remove('text-gray-300');
          icon.classList.add('text-amber-400');
        } else {
          icon.classList.remove('text-amber-400');
          icon.classList.add('text-gray-300');
        }
      });
    }

    inputs.forEach(function (input) {
      input.addEventListener('change', render);
    });

    render();
  });

  // Modal batalkan/refund: tutup saat klik backdrop atau tekan Escape.
  ['techzone-cancel-modal', 'techzone-refund-modal'].forEach(function (id) {
    var modal = document.getElementById(id);
    if (!modal) return;
    modal.addEventListener('click', function (e) {
      if (e.target === modal) modal.classList.add('hidden');
    });
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      document.getElementById('techzone-cancel-modal')?.classList.add('hidden');
      document.getElementById('techzone-refund-modal')?.classList.add('hidden');
    }
  });
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>