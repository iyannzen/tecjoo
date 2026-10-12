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
$orderStmt   = $pdo->prepare("SELECT * FROM orders WHERE order_number = :num AND user_id = :uid AND payment_method = 'transfer' LIMIT 1");
$orderStmt->execute([':num' => $orderNumber, ':uid' => $userId]);
$order = $orderStmt->fetch();

if (!$order) {
    setFlashMessage('error', 'Pesanan Transfer Bank tidak ditemukan.');
    redirectTo('account/orders.php');
}

$paymentStmt = $pdo->prepare('SELECT * FROM payments WHERE order_id = :id ORDER BY id DESC LIMIT 1');
$paymentStmt->execute([':id' => $order['id']]);
$payment = $paymentStmt->fetch();

$paymentConfig = getPaymentConfig();
$selectedBank  = $order['bank_account_id'] ? getBankAccountById($pdo, (int) $order['bank_account_id']) : null;
// Fallback ke rekening tunggal di pengaturan (untuk order lama sebelum fitur multi-bank ada).
$displayBankName   = $selectedBank['bank_name'] ?? $paymentConfig['bank_name'];
$displayAccNumber  = $selectedBank['account_number'] ?? $paymentConfig['bank_account_number'];
$displayAccHolder  = $selectedBank['account_holder'] ?? $paymentConfig['bank_account_holder'];

$pageTitle = 'Pembayaran Transfer Bank | TechZone';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<main class="max-w-lg mx-auto px-4 py-10">
  <nav class="reveal text-xs text-gray-500 mb-4">
    <a href="../account/order-detail.php?order=<?= e($orderNumber) ?>" class="hover:text-cyan-600 transition-colors"><i class="fa-solid fa-arrow-left mr-1"></i>Kembali ke Detail Pesanan</a>
  </nav>

  <div class="reveal bg-white border border-gray-100 rounded-2xl p-6 shadow-card text-center">
    <span class="inline-flex w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 text-white items-center justify-center mb-3 shadow-sm">
      <i class="fa-solid fa-building-columns text-lg"></i>
    </span>
    <h1 class="text-base font-extrabold text-navy-900 mb-1">Pembayaran Transfer Bank</h1>
    <?php if ($displayBankName): ?>
      <p class="text-xs text-cyan-600 font-semibold mb-1">Transfer ke rekening <?= e($displayBankName) ?></p>
    <?php endif; ?>
    <p class="text-xs text-gray-400 mb-5">Order: <span class="font-semibold text-navy-900"><?= e($order['order_number']) ?></span></p>

    <?php if ($payment['status'] === 'paid'): ?>
      <div class="py-10 text-center">
        <i class="fa-solid fa-circle-check text-emerald-500 text-5xl mb-4 animate-scale-in"></i>
        <p class="text-sm font-semibold text-navy-900">Pembayaran Berhasil Diverifikasi</p>
        <p class="text-xs text-gray-500 mt-1">Pesanan Anda sedang diproses.</p>
        <a href="../account/order-detail.php?order=<?= e($orderNumber) ?>" class="inline-block mt-5 ripple-wrap btn-glow bg-cyan-500 hover:bg-cyan-400 text-navy-900 font-semibold text-sm px-6 py-2.5 rounded-full transition">Lihat Detail Pesanan</a>
      </div>

    <?php elseif ($payment['status'] === 'waiting_verification'): ?>
      <div class="py-10 text-center">
        <i class="fa-solid fa-clock text-amber-500 text-5xl mb-4 animate-bounce-slow"></i>
        <p class="text-sm font-semibold text-navy-900">Menunggu Verifikasi Admin</p>
        <p class="text-xs text-gray-500 mt-1">Bukti transfer Anda sedang diperiksa oleh tim TechZone.</p>
        <?php if ($payment['payment_proof']): ?>
          <img src="<?= e(techzoneBaseUrl() . '/uploads/payments/' . $payment['payment_proof']) ?>"
               class="max-w-full h-40 object-contain mx-auto mt-4 rounded-lg border border-gray-100" alt="Bukti transfer">
        <?php endif; ?>
      </div>

    <?php else: // unpaid ?>
      <div class="relative overflow-hidden bg-navy-900 rounded-xl p-5 text-center mb-5 animate-pulse-glow">
        <div class="absolute inset-0 grid-pattern opacity-20"></div>
        <p class="relative text-[10px] text-gray-400 uppercase tracking-wide">Total Pembayaran</p>
        <p class="relative text-2xl font-extrabold text-white"><?= e(formatRupiah($payment['amount'])) ?></p>
      </div>

      <div class="border border-gray-100 rounded-xl p-4 mb-6 space-y-3 text-sm bg-gradient-to-br from-blue-50/40 to-transparent">
        <div class="flex justify-between items-center">
          <span class="text-gray-500">Bank Tujuan</span>
          <span class="flex items-center gap-3 font-bold text-navy-900 text-base">
            <?= renderPaymentLogoBox(($selectedBank['logo_image'] ?? null) ?: ('assets/images/payments/banks/' . strtolower(str_replace(' ', '-', $displayBankName)) . '.png'), mb_strtoupper(mb_substr($displayBankName, 0, 4)), $selectedBank['logo_bg'] ?? '#005AAB', $selectedBank['logo_fg'] ?? '#ffffff', 'w-12 h-12', $displayBankName) ?>
            <?= e($displayBankName) ?>
          </span>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-gray-500">No. Rekening</span>
          <span class="flex items-center gap-2">
            <span class="font-bold text-navy-900 font-mono tracking-wide" id="techzone-rek-number"><?= e($displayAccNumber) ?></span>
            <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('techzone-rek-number').textContent.trim())" class="text-cyan-600 hover:text-cyan-700 hover:scale-110 transition-all" title="Salin nomor rekening"><i class="fa-regular fa-copy text-xs"></i></button>
          </span>
        </div>
        <div class="flex justify-between"><span class="text-gray-500">Atas Nama</span><span class="font-bold text-navy-900"><?= e($displayAccHolder) ?></span></div>
      </div>

      <ol class="text-xs text-gray-500 space-y-2 mb-6 text-left">
        <li class="flex items-start gap-2"><span class="w-5 h-5 rounded-full bg-cyan-100 text-cyan-700 text-[10px] font-bold flex items-center justify-center shrink-0 mt-0.5">1</span>Transfer sesuai nominal yang tertera di atas.</li>
        <li class="flex items-start gap-2"><span class="w-5 h-5 rounded-full bg-cyan-100 text-cyan-700 text-[10px] font-bold flex items-center justify-center shrink-0 mt-0.5">2</span>Simpan bukti transfer Anda.</li>
        <li class="flex items-start gap-2"><span class="w-5 h-5 rounded-full bg-cyan-100 text-cyan-700 text-[10px] font-bold flex items-center justify-center shrink-0 mt-0.5">3</span>Unggah bukti transfer &amp; klik "Saya Sudah Transfer".</li>
        <li class="flex items-start gap-2"><span class="w-5 h-5 rounded-full bg-cyan-100 text-cyan-700 text-[10px] font-bold flex items-center justify-center shrink-0 mt-0.5">4</span>Pesanan akan menunggu verifikasi admin.</li>
      </ol>

      <form action="../actions/upload-proof.php" method="POST" enctype="multipart/form-data" class="space-y-3">
        <?= csrfField() ?>
        <input type="hidden" name="order_number" value="<?= e($order['order_number']) ?>">

        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Unggah Bukti Transfer (JPG/PNG/PDF, maks. 2MB)</label>
          <input type="file" name="payment_proof" accept=".jpg,.jpeg,.png,.pdf" required
                 class="w-full text-xs border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
        </div>

        <button type="submit" class="relative w-full ripple-wrap btn-glow bg-cyan-500 hover:bg-cyan-400 text-navy-900 font-semibold py-3 rounded-lg transition">
          Saya Sudah Transfer
        </button>
      </form>
      <p class="text-[11px] text-gray-400 mt-3">Setelah menekan tombol ini, status akan berubah menjadi "Menunggu Verifikasi" hingga admin mengonfirmasi.</p>
    <?php endif; ?>
  </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
