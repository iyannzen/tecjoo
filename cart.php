<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/cart_helpers.php';
require_once __DIR__ . '/includes/product_helpers.php';

// Keranjang hanya untuk user yang sudah login.
requireUserLogin();

$pdo    = getDbConnection();
$cartId = getOrCreateCartId($pdo);
$items  = getCartItems($pdo, $cartId);

$subtotal = 0.0;
foreach ($items as $item) {
    $activePrice = (!empty($item['discount_price']) && (float) $item['discount_price'] > 0) ? $item['discount_price'] : $item['price'];
    $subtotal += $activePrice * (int) $item['quantity'];
}

$appliedCoupon   = $_SESSION['applied_coupon'] ?? null;
$couponDiscount  = 0.0;

if ($appliedCoupon) {
    // Validasi ulang minimum pembelian terhadap subtotal terkini (bisa berubah jika item dihapus).
    $couponStmt = $pdo->prepare('SELECT * FROM coupons WHERE id = :id LIMIT 1');
    $couponStmt->execute([':id' => $appliedCoupon['id']]);
    $couponRow = $couponStmt->fetch();

    if (!$couponRow || $subtotal < (float) $couponRow['minimum_purchase']) {
        unset($_SESSION['applied_coupon']);
        $appliedCoupon = null;
    } else {
        $couponDiscount = min((float) $appliedCoupon['amount'], $subtotal);
    }
}

$total = max(0, $subtotal - $couponDiscount);

$pageTitle = 'Keranjang Belanja | TechZone';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

  <!-- STEP INDICATOR -->
  <div class="reveal flex items-center justify-center gap-2 sm:gap-4 mb-8 text-xs sm:text-sm">
    <div class="flex items-center gap-2 text-cyan-600 font-semibold">
      <span class="w-7 h-7 rounded-full bg-cyan-500 text-navy-900 flex items-center justify-center shadow-glow">1</span> Keranjang
    </div>
    <div class="w-8 sm:w-16 h-0.5 bg-gray-200"></div>
    <div class="flex items-center gap-2 text-gray-400">
      <span class="w-7 h-7 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center">2</span> Checkout
    </div>
    <div class="w-8 sm:w-16 h-0.5 bg-gray-200"></div>
    <div class="flex items-center gap-2 text-gray-400">
      <span class="w-7 h-7 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center">3</span> Pembayaran
    </div>
  </div>

  <h1 class="reveal text-2xl font-extrabold text-navy-900 mb-6 flex items-center gap-2">
    <i class="fa-solid fa-cart-shopping text-cyan-500"></i> Keranjang Belanja
    <span class="text-sm font-medium text-gray-400">(<?= count($items) ?> item)</span>
  </h1>

  <?php if (empty($items)): ?>
    <div class="reveal bg-white border border-gray-100 rounded-2xl p-16 text-center shadow-card">
      <i class="fa-solid fa-cart-shopping text-5xl text-gray-200 mb-4 animate-float"></i>
      <p class="text-gray-500 mb-5">Keranjang Anda masih kosong.</p>
      <a href="products.php" class="ripple-wrap btn-glow inline-block bg-cyan-500 hover:bg-cyan-400 text-navy-900 font-semibold px-6 py-2.5 rounded-full transition">
        Mulai Belanja
      </a>
    </div>
  <?php else: ?>
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

    <!-- DAFTAR ITEM (satu form: tombol "Update Keranjang" memperbarui semua jumlah sekaligus) -->
    <div class="lg:col-span-2">
    <form id="techzone-cart-update" action="actions/cart.php" method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="update_all">
      <div class="space-y-3">
      <?php foreach ($items as $item):
        $activePrice = (!empty($item['discount_price']) && (float) $item['discount_price'] > 0) ? $item['discount_price'] : $item['price'];
        $isInactive  = $item['status'] !== 'active';
        $overStock   = (int) $item['quantity'] > (int) $item['stock'];
      ?>
      <div class="reveal bg-white border border-gray-100 rounded-xl p-4 flex gap-4 card-hover">
        <a href="product-detail.php?slug=<?= e($item['slug']) ?>" class="w-20 h-20 rounded-lg overflow-hidden bg-gray-50 shrink-0 group">
          <img src="<?= e(productImageUrl($item)) ?>" alt="<?= e($item['name']) ?>" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110">
        </a>
        <div class="flex-1 min-w-0">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <a href="product-detail.php?slug=<?= e($item['slug']) ?>" class="text-sm font-semibold text-navy-900 hover:text-cyan-600 line-clamp-2"><?= e($item['name']) ?></a>
              <p class="text-xs text-gray-400 mt-0.5"><?= e($item['brand']) ?></p>
            </div>
            <button type="submit" form="cart-remove-<?= (int) $item['cart_item_id'] ?>" class="text-gray-300 hover:text-red-500 transition" aria-label="Hapus dari keranjang"><i class="fa-solid fa-trash"></i></button>
          </div>

          <?php if ($isInactive): ?>
            <p class="text-xs text-red-500 mt-1.5"><i class="fa-solid fa-triangle-exclamation mr-1"></i>Produk ini sudah tidak tersedia.</p>
          <?php elseif ($overStock): ?>
            <p class="text-xs text-amber-600 mt-1.5"><i class="fa-solid fa-triangle-exclamation mr-1"></i>Jumlah melebihi stok tersedia (<?= (int)$item['stock'] ?>). Silakan sesuaikan.</p>
          <?php endif; ?>

          <div class="flex items-center justify-between mt-3">
            <div class="flex items-center gap-2">
              <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden">
                <button type="button" onclick="techzoneStep(this, -1)" class="w-7 h-8 text-gray-500 hover:text-navy-900 hover:bg-gray-100 text-sm transition-colors">-</button>
                <input type="number" name="quantities[<?= (int) $item['cart_item_id'] ?>]" value="<?= (int) $item['quantity'] ?>" min="0" max="<?= (int) $item['stock'] ?>"
                       class="w-10 text-center text-xs border-x border-gray-300 h-8 focus:outline-none">
                <button type="button" onclick="techzoneStep(this, 1)" class="w-7 h-8 text-gray-500 hover:text-navy-900 hover:bg-gray-100 text-sm transition-colors">+</button>
              </div>
            </div>
            <p class="text-sm font-bold text-navy-900"><?= e(formatRupiah($activePrice * (int) $item['quantity'])) ?></p>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      </div>

      <div class="tz-cartbar">
        <a href="products.php" class="tz-cartbar__shop"><i class="fa-solid fa-arrow-left"></i> Lanjut Belanja</a>
        <button type="submit" class="tz-cartbar__update ripple-wrap btn-glow"><i class="fa-solid fa-rotate"></i> Update Keranjang</button>
      </div>
    </form>

    <?php foreach ($items as $item): ?>
      <form id="cart-remove-<?= (int) $item['cart_item_id'] ?>" action="actions/cart.php" method="POST" class="hidden">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="remove">
        <input type="hidden" name="cart_item_id" value="<?= (int) $item['cart_item_id'] ?>">
      </form>
    <?php endforeach; ?>
    </div>

    <!-- RINGKASAN -->
    <div>
      <div class="reveal bg-white border border-gray-100 rounded-xl p-5 sticky top-20 shadow-card">
        <h2 class="text-sm font-bold text-navy-900 mb-4">Ringkasan Belanja</h2>

        <?php if ($appliedCoupon): ?>
          <div class="flex items-center justify-between bg-emerald-50 text-emerald-700 text-xs font-medium px-3 py-2 rounded-lg mb-4">
            <span><i class="fa-solid fa-ticket mr-1.5"></i><?= e($appliedCoupon['code']) ?> diterapkan</span>
            <form action="actions/cart.php" method="POST">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="remove_coupon">
              <button type="submit" class="text-emerald-800 hover:underline">Batalkan</button>
            </form>
          </div>
        <?php else: ?>
          <form action="actions/cart.php" method="POST" class="flex gap-2 mb-4">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="apply_coupon">
            <input type="text" name="coupon_code" placeholder="Kode kupon"
                   class="flex-1 text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
            <button type="submit" class="bg-navy-900 text-white text-xs font-semibold px-4 rounded-lg hover:bg-navy-800 transition-all hover:shadow-glow">Pakai</button>
          </form>
        <?php endif; ?>

        <div class="space-y-2 text-sm text-gray-600 border-t border-gray-100 pt-4">
          <div class="flex justify-between"><span>Subtotal</span><span class="font-medium text-navy-900"><?= e(formatRupiah($subtotal)) ?></span></div>
          <?php if ($couponDiscount > 0): ?>
            <div class="flex justify-between text-emerald-600"><span>Diskon Kupon</span><span>-<?= e(formatRupiah($couponDiscount)) ?></span></div>
          <?php endif; ?>
          <p class="text-xs text-gray-400">Ongkos kirim dihitung pada halaman checkout.</p>
        </div>

        <div class="flex justify-between items-baseline border-t border-gray-100 mt-4 pt-4">
          <span class="text-sm font-semibold text-navy-900">Total</span>
          <span class="text-lg font-extrabold text-navy-900"><?= e(formatRupiah($total)) ?></span>
        </div>

        <a href="checkout.php"
           class="ripple-wrap btn-glow mt-5 flex items-center justify-center gap-2 text-center bg-cyan-500 hover:bg-cyan-400 text-navy-900 font-semibold py-3 rounded-lg transition">
          Lanjut ke Checkout <i class="fa-solid fa-arrow-right text-xs"></i>
        </a>

        <div class="flex items-center justify-center gap-4 mt-4 pt-4 border-t border-gray-100 text-gray-400">
          <span class="text-[10px] flex items-center gap-1"><i class="fa-solid fa-lock"></i> Aman</span>
          <span class="text-[10px] flex items-center gap-1"><i class="fa-solid fa-truck"></i> Cepat</span>
          <span class="text-[10px] flex items-center gap-1"><i class="fa-solid fa-rotate-left"></i> Bisa Retur</span>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>
</main>
<script>
  function techzoneStep(btn, delta) {
    var input = btn.parentElement.querySelector('input[type=number]');
    var max = parseInt(input.max || '999', 10);
    var value = parseInt(input.value || '1', 10) + delta;
    if (value < 0) value = 0;
    if (value > max) value = max;
    input.value = value;
  }
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
