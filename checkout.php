<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/cart_helpers.php';
require_once __DIR__ . '/includes/product_helpers.php';
require_once __DIR__ . '/config/payment.php';
require_once __DIR__ . '/config/midtrans.php';

requireUserLogin();

$pdo    = getDbConnection();
$userId = (int) $_SESSION['user_id'];
$cartId = getOrCreateCartId($pdo);
$items  = getCartItems($pdo, $cartId);

if (empty($items)) {
    setFlashMessage('error', 'Keranjang Anda masih kosong. Silakan pilih produk terlebih dahulu.');
    redirectTo('cart.php');
}

// Validasi: tidak boleh checkout jika ada item nonaktif / melebihi stok.
$hasBlockingIssue = false;
foreach ($items as $item) {
    if ($item['status'] !== 'active' || (int) $item['quantity'] > (int) $item['stock']) {
        $hasBlockingIssue = true;
        break;
    }
}

$subtotal = 0.0;
foreach ($items as $item) {
    $activePrice = (!empty($item['discount_price']) && (float) $item['discount_price'] > 0) ? $item['discount_price'] : $item['price'];
    $subtotal += $activePrice * (int) $item['quantity'];
}

$appliedCoupon  = $_SESSION['applied_coupon'] ?? null;
$couponDiscount = $appliedCoupon ? min((float) $appliedCoupon['amount'], $subtotal) : 0.0;

$paymentConfig = getPaymentConfig();
$midtransOn    = ($paymentConfig['payment_midtrans_active'] === '1') && midtransIsConfigured();
$bankAccounts  = getActiveBankAccounts($pdo);
$shippingReguler = (float) $paymentConfig['shipping_cost_reguler'];
$shippingExpress = (float) $paymentConfig['shipping_cost_express'];

$addressStmt = $pdo->prepare('SELECT * FROM addresses WHERE user_id = :uid ORDER BY is_default DESC, created_at DESC');
$addressStmt->execute([':uid' => $userId]);
$addresses = $addressStmt->fetchAll();

// Buat order token sekali per sesi checkout untuk mencegah double-submit.
if (empty($_SESSION['checkout_order_token'])) {
    $_SESSION['checkout_order_token'] = bin2hex(random_bytes(24));
}
$orderToken = $_SESSION['checkout_order_token'];

$pageTitle = 'Checkout | TechZone';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

  <!-- STEP INDICATOR -->
  <div class="reveal flex items-center justify-center gap-2 sm:gap-4 mb-8 text-xs sm:text-sm">
    <div class="flex items-center gap-2 text-emerald-600 font-semibold">
      <span class="w-7 h-7 rounded-full bg-emerald-500 text-white flex items-center justify-center"><i class="fa-solid fa-check text-xs"></i></span> Keranjang
    </div>
    <div class="w-8 sm:w-16 h-0.5 bg-cyan-300"></div>
    <div class="flex items-center gap-2 text-cyan-600 font-semibold">
      <span class="w-7 h-7 rounded-full bg-cyan-500 text-navy-900 flex items-center justify-center shadow-glow">2</span> Checkout
    </div>
    <div class="w-8 sm:w-16 h-0.5 bg-gray-200"></div>
    <div class="flex items-center gap-2 text-gray-400">
      <span class="w-7 h-7 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center">3</span> Pembayaran
    </div>
  </div>

  <h1 class="reveal text-2xl font-extrabold text-navy-900 mb-6 flex items-center gap-2"><i class="fa-solid fa-file-invoice text-cyan-500"></i> Checkout</h1>

  <?php if ($hasBlockingIssue): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-4 mb-6">
      <i class="fa-solid fa-triangle-exclamation mr-2"></i>
      Ada produk di keranjang Anda yang sudah tidak aktif atau melebihi stok tersedia.
      <a href="cart.php" class="font-semibold underline">Perbaiki di halaman keranjang</a> sebelum melanjutkan checkout.
    </div>
  <?php else: ?>

  <form action="actions/checkout.php" method="POST" id="techzone-checkout-form">
    <?= csrfField() ?>
    <input type="hidden" name="order_token" value="<?= e($orderToken) ?>">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <div class="lg:col-span-2 space-y-6">

        <!-- STEP 1: ALAMAT -->
        <div class="reveal bg-white border border-gray-100 rounded-xl p-6 transition-shadow hover:shadow-card">
          <h2 class="text-sm font-bold text-navy-900 mb-4"><span class="inline-flex w-6 h-6 rounded-full bg-gradient-to-br from-cyan-400 to-blue-500 text-navy-900 items-center justify-center text-xs mr-2 shadow-glow">1</span>Alamat Pengiriman</h2>

          <?php if (!empty($addresses)): ?>
            <div class="space-y-2 mb-3">
              <?php foreach ($addresses as $i => $addr): ?>
              <label class="flex items-start gap-3 border border-gray-200 rounded-lg p-3 cursor-pointer has-[:checked]:border-cyan-400 has-[:checked]:bg-cyan-50/50 transition-all duration-200 hover:border-cyan-300">
                <input type="radio" name="address_id" value="<?= (int) $addr['id'] ?>" class="mt-1" <?= ($addr['is_default'] || $i === 0) ? 'checked' : '' ?>
                       onchange="document.getElementById('techzone-new-address').classList.add('hidden')">
                <div class="text-sm">
                  <p class="font-semibold text-navy-900"><?= e($addr['recipient_name']) ?> <span class="text-gray-400 font-normal">| <?= e($addr['phone']) ?></span></p>
                  <p class="text-gray-500 text-xs mt-0.5"><?= e($addr['address']) ?>, <?= e($addr['city']) ?>, <?= e($addr['province']) ?> <?= e($addr['postal_code']) ?></p>
                </div>
              </label>
              <?php endforeach; ?>
            </div>
            <label class="flex items-center gap-2 text-sm text-cyan-600 cursor-pointer mb-2">
              <input type="radio" name="address_id" value="new" onchange="document.getElementById('techzone-new-address').classList.remove('hidden')">
              Gunakan alamat baru
            </label>
          <?php endif; ?>

          <div id="techzone-new-address" class="<?= empty($addresses) ? '' : 'hidden' ?> space-y-3 border-t border-gray-100 pt-4 mt-2">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <input type="text" name="new_recipient_name" placeholder="Nama Penerima"
                     class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
              <input type="text" name="new_phone" placeholder="Nomor HP"
                     class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
            </div>
            <textarea name="new_address" rows="2" placeholder="Alamat Lengkap"
                      class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200"></textarea>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <input type="text" name="new_city" placeholder="Kota" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
              <input type="text" name="new_province" placeholder="Provinsi" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
              <input type="text" name="new_postal_code" placeholder="Kode Pos" maxlength="5" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
            </div>
            <label class="flex items-center gap-2 text-xs text-gray-500 cursor-pointer">
              <input type="checkbox" name="save_new_address" value="1" checked> Simpan alamat ini untuk pesanan berikutnya
            </label>
          </div>
        </div>

        <!-- STEP 2: PENGIRIMAN -->
        <div class="reveal bg-white border border-gray-100 rounded-xl p-6 transition-shadow hover:shadow-card">
          <h2 class="text-sm font-bold text-navy-900 mb-4"><span class="inline-flex w-6 h-6 rounded-full bg-gradient-to-br from-cyan-400 to-blue-500 text-navy-900 items-center justify-center text-xs mr-2 shadow-glow">2</span>Metode Pengiriman</h2>
          <div class="space-y-2">
            <label class="flex items-center justify-between border border-gray-200 rounded-lg p-3 cursor-pointer has-[:checked]:border-cyan-400 has-[:checked]:bg-cyan-50/50 transition-all duration-200 hover:border-cyan-300">
              <span class="flex items-center gap-3 text-sm">
                <input type="radio" name="shipping_method" value="reguler" checked onchange="techzoneUpdateSummary()">
                <span><span class="font-medium text-navy-900">Reguler</span><br><span class="text-xs text-gray-400">Estimasi 3-5 hari kerja</span></span>
              </span>
              <span class="text-sm font-semibold text-navy-900"><?= e(formatRupiah($shippingReguler)) ?></span>
            </label>
            <label class="flex items-center justify-between border border-gray-200 rounded-lg p-3 cursor-pointer has-[:checked]:border-cyan-400 has-[:checked]:bg-cyan-50/50 transition-all duration-200 hover:border-cyan-300">
              <span class="flex items-center gap-3 text-sm">
                <input type="radio" name="shipping_method" value="express" onchange="techzoneUpdateSummary()">
                <span><span class="font-medium text-navy-900">Express</span><br><span class="text-xs text-gray-400">Estimasi 1-2 hari kerja</span></span>
              </span>
              <span class="text-sm font-semibold text-navy-900"><?= e(formatRupiah($shippingExpress)) ?></span>
            </label>
          </div>
        </div>

        <!-- STEP 3: PEMBAYARAN -->
        <div class="reveal bg-white border border-gray-100 rounded-xl overflow-hidden shadow-card">
          <div class="p-6 pb-4">
            <h2 class="text-sm font-bold text-navy-900 flex items-center"><span class="inline-flex w-6 h-6 rounded-full bg-gradient-to-br from-cyan-400 to-blue-500 text-navy-900 items-center justify-center text-xs mr-2 shadow-glow">3</span>Metode Pembayaran</h2>
            <p class="text-[11px] text-emerald-600 font-medium mt-1.5 ml-8 flex items-center gap-1"><i class="fa-solid fa-shield-halved"></i> Informasi Anda aman dan terenkripsi</p>
          </div>

          <div class="divide-y divide-gray-100 border-t border-gray-100">
            <?php if ($midtransOn): ?>
            <label class="group flex items-center gap-3 px-5 sm:px-6 py-4 cursor-pointer hover:bg-gray-50 has-[:checked]:bg-cyan-50/60 transition-colors duration-150">
              <?= renderPaymentLogoBox('assets/images/payments/midtrans.png', 'PAY', 'linear-gradient(135deg,#06b6d4,#1d4ed8)', '#ffffff', 'w-11 h-11') ?>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-navy-900">Pembayaran Online <span class="ml-1 bg-emerald-50 text-emerald-600 text-[9px] font-bold px-1.5 py-0.5 rounded-full align-middle">Otomatis</span></p>
                <p class="text-[11px] text-gray-400 mt-0.5">Virtual Account (BCA, BNI, BRI, Mandiri, Permata), QRIS, GoPay, ShopeePay, kartu &amp; minimarket &mdash; diverifikasi otomatis</p>
              </div>
              <input type="radio" id="techzone-pm-midtrans" name="payment_method" value="midtrans" checked class="accent-cyan-500 w-5 h-5 shrink-0">
            </label>
            <?php endif; ?>

            <?php if ($paymentConfig['payment_qris_active'] === '1'):
              $ewallets = paymentEwalletBrands();
            ?>
            <label class="group flex items-center gap-3 px-5 sm:px-6 py-4 cursor-pointer hover:bg-gray-50 has-[:checked]:bg-cyan-50/60 transition-colors duration-150">
              <?= renderPaymentLogoBox('assets/images/payments/qris.png', 'QRIS', 'linear-gradient(135deg,#7c3aed,#4c1d95)', '#ffffff', 'w-11 h-11') ?>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-navy-900">QRIS / E-Wallet <span class="ml-1 bg-emerald-50 text-emerald-600 text-[9px] font-bold px-1.5 py-0.5 rounded-full align-middle">Instan</span></p>
                <p class="text-[11px] text-gray-400 mt-0.5">Pilih aplikasi e-wallet Anda di bawah, atau scan QRIS umum</p>
              </div>
              <input type="radio" id="techzone-pm-qris" name="payment_method" value="qris" <?= $midtransOn ? '' : 'checked' ?> class="accent-cyan-500 w-5 h-5 shrink-0">
            </label>

            <?php if (!empty($ewallets)): ?>
            <div id="techzone-wallet-panel" class="bg-gray-50/60 px-5 sm:px-6 py-4">
              <p class="text-[11px] text-gray-500 font-medium mb-2">Pilih e-wallet favorit Anda:</p>
              <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
                <?php foreach ($ewallets as $i => [$bn, $bg, $fg, $mark, $img]): ?>
                <label class="techzone-wallet-option relative flex flex-col items-center gap-1.5 border-2 <?= $i === 0 ? 'border-cyan-400 bg-cyan-50/60' : 'border-gray-200 bg-white' ?> rounded-xl p-2 cursor-pointer hover:border-cyan-300 hover:-translate-y-0.5 transition-all duration-200">
                  <input type="radio" name="wallet_choice" value="<?= e($bn) ?>" <?= $i === 0 ? 'checked' : '' ?> class="sr-only techzone-wallet-radio">
                  <?= renderPaymentLogoBox($img, $mark, $bg, $fg, 'w-9 h-9', $bn) ?>
                  <span class="text-[10px] font-medium text-navy-900 text-center leading-tight"><?= e($bn) ?></span>
                  <i class="fa-solid fa-circle-check text-cyan-500 text-xs absolute top-1 right-1 <?= $i === 0 ? '' : 'hidden' ?> techzone-wallet-check"></i>
                </label>
                <?php endforeach; ?>
              </div>
              <p class="text-[11px] text-gray-400 mt-3"><i class="fa-solid fa-circle-info mr-1"></i>QR yang tampil sama untuk semua e-wallet (standar QRIS nasional) — cukup buka aplikasi pilihan Anda lalu scan.</p>
            </div>
            <?php endif; /* ada e-wallet aktif */ ?>
            <?php endif; /* QRIS aktif */ ?>

            <?php if ($paymentConfig['payment_cod_active'] === '1'): ?>
            <label class="group flex items-center gap-3 px-5 sm:px-6 py-4 cursor-pointer hover:bg-gray-50 has-[:checked]:bg-cyan-50/60 transition-colors duration-150">
              <?= renderPaymentLogoBox('assets/images/payments/cod.png', 'COD', '#059669', '#ffffff', 'w-11 h-11') ?>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-navy-900">Bayar di Tempat (COD)</p>
                <p class="text-[11px] text-gray-400 mt-0.5">Bayar tunai saat pesanan sampai di tujuan</p>
              </div>
              <input type="radio" id="techzone-pm-cod" name="payment_method" value="cod" <?= (!$midtransOn && $paymentConfig['payment_qris_active'] !== '1') ? 'checked' : '' ?> class="accent-cyan-500 w-5 h-5 shrink-0">
            </label>
            <?php endif; ?>

            <?php if ($paymentConfig['payment_transfer_active'] === '1' && !empty($bankAccounts)): ?>
            <label class="group flex items-center gap-3 px-5 sm:px-6 py-4 cursor-pointer hover:bg-gray-50 has-[:checked]:bg-cyan-50/60 transition-colors duration-150">
              <?= renderPaymentLogoBox('assets/images/payments/transfer-bank.png', 'VA', 'linear-gradient(135deg,#2563eb,#1e3a8a)', '#ffffff', 'w-11 h-11') ?>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-navy-900">Transfer Bank</p>
                <p class="text-[11px] text-gray-400 mt-0.5">Pilih salah satu bank tujuan transfer di bawah ini</p>
              </div>
              <input type="radio" id="techzone-pm-transfer" name="payment_method" value="transfer" <?= (!$midtransOn && $paymentConfig['payment_qris_active'] !== '1' && $paymentConfig['payment_cod_active'] !== '1') ? 'checked' : '' ?> class="accent-cyan-500 w-5 h-5 shrink-0">
            </label>

            <div id="techzone-bank-panel" class="hidden bg-gray-50/60 px-5 sm:px-6 py-4">
              <?php if (count($bankAccounts) > 5): ?>
              <div class="relative mb-3">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" id="techzone-bank-search" placeholder="Cari bank..."
                       class="w-full text-sm bg-white border border-gray-200 rounded-lg pl-9 pr-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-all duration-200">
              </div>
              <?php endif; ?>
              <p class="text-[11px] text-gray-500 font-medium mb-2">Pilih rekening tujuan transfer:</p>
              <div id="techzone-bank-list" class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-72 overflow-y-auto pr-1">
                <?php foreach ($bankAccounts as $i => $bankRow):
                  $mark = mb_strtoupper(mb_substr($bankRow['bank_name'], 0, 4));
                ?>
                  <label data-bank-name="<?= e(mb_strtolower($bankRow['bank_name'])) ?>"
                         class="techzone-bank-option relative flex items-center gap-3 bg-white border-2 <?= $i === 0 ? 'border-cyan-400 bg-cyan-50/60' : 'border-gray-100' ?> rounded-lg px-3 py-2.5 hover:border-cyan-300 hover:shadow-sm transition-all duration-150 cursor-pointer">
                    <input type="radio" name="bank_account_id" value="<?= (int) $bankRow['id'] ?>" <?= $i === 0 ? 'checked' : '' ?> class="sr-only techzone-bank-radio">
                    <?= renderPaymentLogoBox($bankRow['logo_image'] ?: ('assets/images/payments/banks/' . strtolower(str_replace(' ', '-', $bankRow['bank_name'])) . '.png'), $mark, $bankRow['logo_bg'], $bankRow['logo_fg'], 'w-10 h-10', $bankRow['bank_name']) ?>
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-semibold text-navy-900 truncate"><?= e($bankRow['bank_name']) ?></p>
                      <p class="text-[11px] text-gray-400 truncate">a.n. <?= e($bankRow['account_holder']) ?></p>
                    </div>
                    <i class="fa-solid fa-circle-check text-cyan-500 text-sm shrink-0 techzone-bank-check <?= $i === 0 ? '' : 'hidden' ?>"></i>
                  </label>
                <?php endforeach; ?>
              </div>
              <p class="text-[11px] text-gray-400 mt-3"><i class="fa-solid fa-circle-info mr-1"></i>Nomor rekening lengkap akan ditampilkan di halaman instruksi pembayaran setelah pesanan dibuat.</p>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Catatan -->
        <div class="reveal bg-white border border-gray-100 rounded-xl p-6 transition-shadow hover:shadow-card">
          <label class="block text-sm font-bold text-navy-900 mb-2">Catatan Pesanan (opsional)</label>
          <textarea name="notes" rows="2" placeholder="Contoh: titip di satpam, hubungi sebelum kirim, dll."
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200"></textarea>
        </div>
      </div>

      <!-- RINGKASAN -->
      <div>
        <div class="reveal bg-white border border-gray-100 rounded-xl p-5 sticky top-20 shadow-card">
          <h2 class="text-sm font-bold text-navy-900 mb-4"><span class="inline-flex w-6 h-6 rounded-full bg-gradient-to-br from-cyan-400 to-blue-500 text-navy-900 items-center justify-center text-xs mr-2 shadow-glow">4</span>Ringkasan Pesanan</h2>

          <div class="space-y-3 max-h-56 overflow-y-auto mb-4 pr-1">
            <?php foreach ($items as $item):
              $activePrice = (!empty($item['discount_price']) && (float) $item['discount_price'] > 0) ? $item['discount_price'] : $item['price']; ?>
            <div class="flex gap-3 text-xs">
              <img src="<?= e(productImageUrl($item)) ?>" class="w-10 h-10 rounded-lg object-cover bg-gray-50 shrink-0 transition-transform hover:scale-110" alt="">
              <div class="flex-1 min-w-0">
                <p class="text-navy-900 font-medium line-clamp-1"><?= e($item['name']) ?></p>
                <p class="text-gray-400"><?= (int) $item['quantity'] ?> x <?= e(formatRupiah($activePrice)) ?></p>
              </div>
              <p class="text-navy-900 font-semibold shrink-0"><?= e(formatRupiah($activePrice * (int) $item['quantity'])) ?></p>
            </div>
            <?php endforeach; ?>
          </div>

          <div class="space-y-2 text-sm text-gray-600 border-t border-gray-100 pt-4">
            <div class="flex justify-between"><span>Subtotal</span><span id="techzone-sum-subtotal" class="font-medium text-navy-900"><?= e(formatRupiah($subtotal)) ?></span></div>
            <div class="flex justify-between"><span>Ongkos Kirim</span><span id="techzone-sum-shipping" class="font-medium text-navy-900"><?= e(formatRupiah($shippingReguler)) ?></span></div>
            <?php if ($couponDiscount > 0): ?>
              <div class="flex justify-between text-emerald-600"><span>Diskon Kupon</span><span>-<?= e(formatRupiah($couponDiscount)) ?></span></div>
            <?php endif; ?>
          </div>

          <div class="flex justify-between items-baseline bg-gradient-to-r from-cyan-50 to-blue-50 rounded-lg px-3 py-3 mt-4">
            <span class="text-sm font-semibold text-navy-900">Total</span>
            <span id="techzone-sum-total" class="text-lg font-extrabold text-navy-900"><?= e(formatRupiah($subtotal + $shippingReguler - $couponDiscount)) ?></span>
          </div>

          <button type="submit" id="techzone-submit-order"
                  class="ripple-wrap btn-glow mt-5 w-full bg-cyan-500 hover:bg-cyan-400 text-navy-900 font-semibold py-3 rounded-lg transition flex items-center justify-center gap-2">
            <i class="fa-solid fa-lock text-xs"></i> Buat Pesanan
          </button>
          <p class="text-center text-[10px] text-gray-400 mt-3"><i class="fa-solid fa-shield-halved mr-1"></i>Transaksi Anda diproses dengan aman</p>
        </div>
      </div>
    </div>
  </form>

  <script>
    var techzoneSubtotal   = <?= (float) $subtotal ?>;
    var techzoneDiscount   = <?= (float) $couponDiscount ?>;
    var techzoneShipReguler = <?= (float) $shippingReguler ?>;
    var techzoneShipExpress = <?= (float) $shippingExpress ?>;

    function techzoneFormatRupiah(n) {
      return 'Rp ' + Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function techzoneUpdateSummary() {
      var method = document.querySelector('input[name=shipping_method]:checked').value;
      var shipCost = method === 'express' ? techzoneShipExpress : techzoneShipReguler;
      var total = techzoneSubtotal + shipCost - techzoneDiscount;
      document.getElementById('techzone-sum-shipping').textContent = techzoneFormatRupiah(shipCost);
      document.getElementById('techzone-sum-total').textContent = techzoneFormatRupiah(total);
    }

    document.getElementById('techzone-checkout-form').addEventListener('submit', function () {
      document.getElementById('techzone-submit-order').disabled = true;
      document.getElementById('techzone-submit-order').textContent = 'Memproses Pesanan...';
    });

    // ---------- Toggle panel bank VA & wallet e-wallet ----------
    var bankPanel      = document.getElementById('techzone-bank-panel');
    var walletPanel     = document.getElementById('techzone-wallet-panel');
    var transferRadio  = document.getElementById('techzone-pm-transfer');
    var qrisRadio       = document.getElementById('techzone-pm-qris');
    function techzoneTogglePaymentPanels() {
      if (bankPanel && transferRadio) {
        bankPanel.classList.toggle('hidden', !transferRadio.checked);
        if (transferRadio.checked) bankPanel.classList.add('animate-fade-in');
      }
      if (walletPanel && qrisRadio) {
        walletPanel.classList.toggle('hidden', !qrisRadio.checked);
      }
    }
    document.querySelectorAll('input[name="payment_method"]').forEach(function (radio) {
      radio.addEventListener('change', techzoneTogglePaymentPanels);
    });
    techzoneTogglePaymentPanels();

    // ---------- Pilih e-wallet ----------
    document.querySelectorAll('.techzone-wallet-radio').forEach(function (radio) {
      radio.addEventListener('change', function () {
        document.querySelectorAll('.techzone-wallet-option').forEach(function (opt) {
          opt.classList.remove('border-cyan-400', 'bg-cyan-50/60');
          opt.classList.add('border-gray-200', 'bg-white');
          opt.querySelector('.techzone-wallet-check')?.classList.add('hidden');
        });
        var opt = this.closest('.techzone-wallet-option');
        opt.classList.remove('border-gray-200', 'bg-white');
        opt.classList.add('border-cyan-400', 'bg-cyan-50/60');
        opt.querySelector('.techzone-wallet-check')?.classList.remove('hidden');
      });
    });

    // ---------- Pilih bank ----------
    document.querySelectorAll('.techzone-bank-radio').forEach(function (radio) {
      radio.addEventListener('change', function () {
        document.querySelectorAll('.techzone-bank-option').forEach(function (opt) {
          opt.classList.remove('border-cyan-400', 'bg-cyan-50/60');
          opt.classList.add('border-gray-100');
          opt.querySelector('.techzone-bank-check')?.classList.add('hidden');
        });
        var opt = this.closest('.techzone-bank-option');
        opt.classList.remove('border-gray-100');
        opt.classList.add('border-cyan-400', 'bg-cyan-50/60');
        opt.querySelector('.techzone-bank-check')?.classList.remove('hidden');
      });
    });

    // ---------- Cari bank ----------
    var bankSearch = document.getElementById('techzone-bank-search');
    if (bankSearch) {
      bankSearch.addEventListener('input', function () {
        var q = this.value.trim().toLowerCase();
        document.querySelectorAll('#techzone-bank-list [data-bank-name]').forEach(function (row) {
          row.style.display = row.dataset.bankName.indexOf(q) !== -1 ? '' : 'none';
        });
      });
    }
  </script>
  <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
