<?php
/**
 * TechZone Admin - Pengaturan Toko
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/upload_helpers.php';

$admin = currentAdmin();
$pdo   = getDbConnection();

function upsertSetting(PDO $pdo, string $key, string $value): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (:key, :value, NOW())
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()'
    );
    $stmt->execute([':key' => $key, ':value' => $value]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? null);

    $fields = [
        'store_name', 'store_tagline', 'store_email', 'store_whatsapp', 'store_address', 'store_hours',
        'bank_name', 'bank_account_number', 'bank_account_holder',
        'shipping_cost_reguler', 'shipping_cost_express', 'qris_payment_duration_minutes',
        'payment_expiry_hours', 'low_stock_threshold', 'cart_reminder_hours',
    ];
    foreach ($fields as $field) {
        upsertSetting($pdo, $field, trim($_POST[$field] ?? ''));
    }

    upsertSetting($pdo, 'payment_qris_active', isset($_POST['payment_qris_active']) ? '1' : '0');
    upsertSetting($pdo, 'payment_transfer_active', isset($_POST['payment_transfer_active']) ? '1' : '0');
    upsertSetting($pdo, 'payment_cod_active', isset($_POST['payment_cod_active']) ? '1' : '0');
    upsertSetting($pdo, 'payment_midtrans_active', isset($_POST['payment_midtrans_active']) ? '1' : '0');

    if (!empty($_FILES['qris_image']['name'])) {
        try {
            $filename = handleImageUpload($_FILES['qris_image'], 'banners');
            upsertSetting($pdo, 'qris_image', 'uploads/banners/' . $filename);
        } catch (RuntimeException $e) {
            setFlashMessage('error', $e->getMessage());
            redirectTo('admin/settings.php');
        }
    }

    logActivity($admin['id'], 'update_settings', 'Memperbarui pengaturan toko.');
    setFlashMessage('success', 'Pengaturan toko berhasil disimpan.');
    redirectTo('admin/settings.php');
}

$settings = getAllSettings();
function s(array $settings, string $key, string $default = ''): string { return $settings[$key] ?? $default; }

$pageTitle = 'Pengaturan Toko | TechZone Admin';
require __DIR__ . '/../includes/header.php';
?>
<div class="min-h-screen flex bg-gray-50">
  <?php require __DIR__ . '/_sidebar.php'; ?>
  <div id="techzone-admin-content" class="flex-1">
    <?php require __DIR__ . '/_topbar.php'; ?>
    <main class="p-6">
      <h1 class="reveal text-2xl font-extrabold text-navy-900 mb-6">Pengaturan Toko</h1>

      <form method="POST" enctype="multipart/form-data" class="space-y-6 max-w-3xl">
        <?= csrfField() ?>

        <div class="bg-white border border-gray-100 rounded-xl p-6 shadow-card transition-shadow hover:shadow-lg">
          <h2 class="text-sm font-bold text-navy-900 mb-4">Informasi Toko</h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Nama Toko</label><input type="text" name="store_name" value="<?= e(s($settings,'store_name','TechZone')) ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Tagline</label><input type="text" name="store_tagline" value="<?= e(s($settings,'store_tagline')) ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Email</label><input type="email" name="store_email" value="<?= e(s($settings,'store_email')) ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Nomor WhatsApp</label><input type="text" name="store_whatsapp" value="<?= e(s($settings,'store_whatsapp')) ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
            <div class="sm:col-span-2"><label class="block text-xs font-medium text-gray-700 mb-1">Alamat</label><input type="text" name="store_address" value="<?= e(s($settings,'store_address')) ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Jam Operasional</label><input type="text" name="store_hours" value="<?= e(s($settings,'store_hours')) ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
          </div>
        </div>

        <div class="bg-white border border-gray-100 rounded-xl p-6 shadow-card transition-shadow hover:shadow-lg">
          <h2 class="text-sm font-bold text-navy-900 mb-1">Rekening Transfer Bank (Cadangan)</h2>
          <p class="text-xs text-gray-400 mb-4">
            <i class="fa-solid fa-circle-info mr-1"></i>Ini hanya rekening cadangan/lama. Untuk mengelola banyak pilihan bank
            (BCA, BRI, BNI, dll) yang bisa dipilih pembeli saat checkout, gunakan menu
            <a href="bank-accounts.php" class="text-cyan-600 font-semibold hover:underline">Rekening &amp; E-Wallet</a> di sidebar.
          </p>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Nama Bank</label><input type="text" name="bank_name" value="<?= e(s($settings,'bank_name')) ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Nomor Rekening</label><input type="text" name="bank_account_number" value="<?= e(s($settings,'bank_account_number')) ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Atas Nama</label><input type="text" name="bank_account_holder" value="<?= e(s($settings,'bank_account_holder')) ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
          </div>
        </div>

        <div class="bg-white border border-gray-100 rounded-xl p-6 shadow-card transition-shadow hover:shadow-lg">
          <h2 class="text-sm font-bold text-navy-900 mb-4">Pengiriman &amp; QRIS</h2>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Ongkir Reguler (Rp)</label><input type="number" name="shipping_cost_reguler" value="<?= e(s($settings,'shipping_cost_reguler','15000')) ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Ongkir Express (Rp)</label><input type="number" name="shipping_cost_express" value="<?= e(s($settings,'shipping_cost_express','30000')) ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Durasi QRIS (menit)</label><input type="number" name="qris_payment_duration_minutes" value="<?= e(s($settings,'qris_payment_duration_minutes','15')) ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Batas Waktu Bayar (jam) &mdash; QRIS/Transfer</label><input type="number" min="1" max="720" name="payment_expiry_hours" value="<?= e(s($settings,'payment_expiry_hours','24')) ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"><p class="text-[11px] text-gray-400 mt-1">Pesanan belum dibayar melewati batas ini dibatalkan otomatis &amp; stok dikembalikan.</p></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Ambang Stok Menipis (unit)</label><input type="number" min="1" max="1000" name="low_stock_threshold" value="<?= e(s($settings,'low_stock_threshold','10')) ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"><p class="text-[11px] text-gray-400 mt-1">Admin diberi notifikasi saat stok produk &le; angka ini.</p></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Pengingat Keranjang (jam)</label><input type="number" min="1" max="720" name="cart_reminder_hours" value="<?= e(s($settings,'cart_reminder_hours','24')) ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"><p class="text-[11px] text-gray-400 mt-1">Pembeli diingatkan bila barang di keranjang tidak disentuh selama ini.</p></div>
          </div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Ganti Gambar QRIS (opsional)</label>
          <input type="file" name="qris_image" accept=".jpg,.jpeg,.png,.webp" class="w-full text-xs border border-gray-300 rounded-lg px-3 py-2">
          <p class="text-[11px] text-gray-400 mt-1">Catatan: QR code yang tampil di halaman pembayaran saat ini adalah pola dekoratif yang dibuat otomatis oleh sistem (bukan gambar statis ini). Gambar ini disediakan untuk kebutuhan branding tambahan di masa depan.</p>
        </div>

        <div class="bg-white border border-gray-100 rounded-xl p-6 shadow-card transition-shadow hover:shadow-lg">
          <h2 class="text-sm font-bold text-navy-900 mb-4">Metode Pembayaran Aktif</h2>
          <div class="space-y-2">
            <label class="flex items-center gap-2 text-sm cursor-pointer"><input type="checkbox" name="payment_qris_active" value="1" <?= s($settings,'payment_qris_active','1')==='1'?'checked':'' ?>> QRIS</label>
            <label class="flex items-center gap-2 text-sm cursor-pointer"><input type="checkbox" name="payment_transfer_active" value="1" <?= s($settings,'payment_transfer_active','1')==='1'?'checked':'' ?>> Transfer Bank</label>
            <label class="flex items-center gap-2 text-sm cursor-pointer"><input type="checkbox" name="payment_cod_active" value="1" <?= s($settings,'payment_cod_active','1')==='1'?'checked':'' ?>> COD</label>
            <label class="flex items-center gap-2 text-sm cursor-pointer"><input type="checkbox" name="payment_midtrans_active" value="1" <?= s($settings,'payment_midtrans_active','1')==='1'?'checked':'' ?>> Midtrans (VA, QRIS, e-wallet, kartu)</label>
          </div>
        </div>

        <button type="submit" class="ripple-wrap btn-glow bg-cyan-500 hover:bg-cyan-400 text-navy-900 font-semibold text-sm px-8 py-2.5 rounded-lg transition">Simpan Pengaturan</button>
      </form>
    </main>
  </div>
</div>
</body>
</html>