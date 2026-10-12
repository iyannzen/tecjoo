<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

requireUserLogin();
$user = currentUser();
$pdo  = getDbConnection();
$userId = (int) $user['id'];

$errors = [];
$editingAddress = null;

// ---------------------------------------------------------------------
// Proses POST: add, update, delete, set_default
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? null);
    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'delete') {
        $id = (int) ($_POST['address_id'] ?? 0);
        $pdo->prepare('DELETE FROM addresses WHERE id = :id AND user_id = :uid')->execute([':id' => $id, ':uid' => $userId]);
        setFlashMessage('success', 'Alamat berhasil dihapus.');
        redirectTo('account/addresses.php');
    }

    if ($formAction === 'set_default') {
        $id = (int) ($_POST['address_id'] ?? 0);
        $pdo->prepare('UPDATE addresses SET is_default = 0 WHERE user_id = :uid')->execute([':uid' => $userId]);
        $pdo->prepare('UPDATE addresses SET is_default = 1 WHERE id = :id AND user_id = :uid')->execute([':id' => $id, ':uid' => $userId]);
        setFlashMessage('success', 'Alamat utama berhasil diperbarui.');
        redirectTo('account/addresses.php');
    }

    if ($formAction === 'save') {
        $addressId     = (int) ($_POST['address_id'] ?? 0);
        $recipientName = trim($_POST['recipient_name'] ?? '');
        $phone         = trim($_POST['phone'] ?? '');
        $address       = trim($_POST['address'] ?? '');
        $city          = trim($_POST['city'] ?? '');
        $province      = trim($_POST['province'] ?? '');
        $postalCode    = trim($_POST['postal_code'] ?? '');
        $isDefault     = isset($_POST['is_default']) ? 1 : 0;

        if ($recipientName === '' || mb_strlen($recipientName) < 3) $errors[] = 'Nama penerima minimal 3 karakter.';
        if ($phone === '' || !preg_match('/^[0-9+\-\s]{8,20}$/', $phone)) $errors[] = 'Nomor HP tidak valid.';
        if ($address === '' || mb_strlen($address) < 10) $errors[] = 'Alamat lengkap minimal 10 karakter.';
        if ($city === '') $errors[] = 'Kota wajib diisi.';
        if ($province === '') $errors[] = 'Provinsi wajib diisi.';
        if ($postalCode === '' || !preg_match('/^[0-9]{5}$/', $postalCode)) $errors[] = 'Kode pos harus 5 digit angka.';

        if (empty($errors)) {
            // Cek apakah ini alamat pertama user -> otomatis jadi default.
            $countStmt = $pdo->prepare('SELECT COUNT(*) FROM addresses WHERE user_id = :uid');
            $countStmt->execute([':uid' => $userId]);
            $isFirstAddress = ((int) $countStmt->fetchColumn()) === 0;

            if ($isDefault || $isFirstAddress) {
                $pdo->prepare('UPDATE addresses SET is_default = 0 WHERE user_id = :uid')->execute([':uid' => $userId]);
                $isDefault = 1;
            }

            if ($addressId > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE addresses SET recipient_name = :name, phone = :phone, address = :address, city = :city,
                     province = :province, postal_code = :postal, is_default = :is_default, updated_at = NOW()
                     WHERE id = :id AND user_id = :uid'
                );
                $stmt->execute([
                    ':name' => $recipientName, ':phone' => $phone, ':address' => $address, ':city' => $city,
                    ':province' => $province, ':postal' => $postalCode, ':is_default' => $isDefault,
                    ':id' => $addressId, ':uid' => $userId,
                ]);
                setFlashMessage('success', 'Alamat berhasil diperbarui.');
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO addresses (user_id, recipient_name, phone, address, city, province, postal_code, is_default, created_at, updated_at)
                     VALUES (:uid, :name, :phone, :address, :city, :province, :postal, :is_default, NOW(), NOW())'
                );
                $stmt->execute([
                    ':uid' => $userId, ':name' => $recipientName, ':phone' => $phone, ':address' => $address,
                    ':city' => $city, ':province' => $province, ':postal' => $postalCode, ':is_default' => $isDefault,
                ]);
                setFlashMessage('success', 'Alamat baru berhasil ditambahkan.');
            }
            redirectTo('account/addresses.php');
        }

        // Jika error, siapkan data untuk ditampilkan kembali di form.
        $editingAddress = [
            'id' => $addressId, 'recipient_name' => $recipientName, 'phone' => $phone, 'address' => $address,
            'city' => $city, 'province' => $province, 'postal_code' => $postalCode, 'is_default' => $isDefault,
        ];
    }
}

// Mode edit via GET (?edit=ID)
if (isset($_GET['edit']) && $editingAddress === null) {
    $stmt = $pdo->prepare('SELECT * FROM addresses WHERE id = :id AND user_id = :uid LIMIT 1');
    $stmt->execute([':id' => (int) $_GET['edit'], ':uid' => $userId]);
    $editingAddress = $stmt->fetch() ?: null;
}

$showForm = isset($_GET['add']) || $editingAddress !== null;

$listStmt = $pdo->prepare('SELECT * FROM addresses WHERE user_id = :uid ORDER BY is_default DESC, created_at DESC');
$listStmt->execute([':uid' => $userId]);
$addresses = $listStmt->fetchAll();

$pageTitle = 'Alamat Saya | TechZone';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
  <div>
      <div class="flex items-center justify-between mb-6">
        <h1 class="reveal text-xl font-extrabold text-navy-900">Alamat Saya</h1>
        <?php if (!$showForm): ?>
          <a href="addresses.php?add=1" class="bg-cyan-500 hover:bg-cyan-400 text-navy-900 text-sm font-semibold px-4 py-2 rounded-lg transition">
            <i class="fa-solid fa-plus mr-1"></i>Tambah Alamat
          </a>
        <?php endif; ?>
      </div>

      <?php if ($showForm): ?>
        <div class="bg-white border border-gray-100 rounded-xl p-6 mb-6 shadow-card transition-shadow hover:shadow-lg">
          <h2 class="text-sm font-bold text-navy-900 mb-4"><?= $editingAddress['id'] ?? null ? 'Edit Alamat' : 'Tambah Alamat Baru' ?></h2>

          <?php if (!empty($errors)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3 mb-4">
              <ul class="list-disc list-inside space-y-1"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
            </div>
          <?php endif; ?>

          <form method="POST" class="space-y-4">
            <?= csrfField() ?>
            <input type="hidden" name="form_action" value="save">
            <input type="hidden" name="address_id" value="<?= (int) ($editingAddress['id'] ?? 0) ?>">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Nama Penerima</label>
                <input type="text" name="recipient_name" required value="<?= e($editingAddress['recipient_name'] ?? '') ?>"
                       class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Nomor HP</label>
                <input type="text" name="phone" required value="<?= e($editingAddress['phone'] ?? '') ?>"
                       class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
              </div>
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Alamat Lengkap</label>
              <textarea name="address" rows="3" required
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200"><?= e($editingAddress['address'] ?? '') ?></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Kota</label>
                <input type="text" name="city" required value="<?= e($editingAddress['city'] ?? '') ?>"
                       class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Provinsi</label>
                <input type="text" name="province" required value="<?= e($editingAddress['province'] ?? '') ?>"
                       class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Kode Pos</label>
                <input type="text" name="postal_code" required maxlength="5" value="<?= e($editingAddress['postal_code'] ?? '') ?>"
                       class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
              </div>
            </div>

            <label class="flex items-center gap-2 text-sm cursor-pointer">
              <input type="checkbox" name="is_default" <?= !empty($editingAddress['is_default']) ? 'checked' : '' ?>>
              Jadikan alamat utama
            </label>

            <div class="flex gap-3">
              <button type="submit" class="ripple-wrap btn-glow bg-cyan-500 hover:bg-cyan-400 text-navy-900 font-semibold text-sm px-6 py-2.5 rounded-lg transition">Simpan Alamat</button>
              <a href="addresses.php" class="text-gray-500 text-sm font-medium px-6 py-2.5 hover:text-navy-900">Batal</a>
            </div>
          </form>
        </div>
      <?php endif; ?>

      <?php if (empty($addresses) && !$showForm): ?>
        <div class="bg-white border border-gray-100 rounded-xl p-12 text-center shadow-card transition-shadow hover:shadow-lg">
          <i class="fa-solid fa-location-dot text-4xl text-gray-200 mb-3 animate-float"></i>
          <p class="text-gray-500 text-sm mb-4">Anda belum memiliki alamat tersimpan.</p>
          <a href="addresses.php?add=1" class="inline-block ripple-wrap btn-glow bg-cyan-500 hover:bg-cyan-400 text-navy-900 font-semibold px-6 py-2.5 rounded-full transition">Tambah Alamat</a>
        </div>
      <?php else: ?>
        <div class="space-y-3">
          <?php foreach ($addresses as $addr): ?>
          <div class="bg-white border border-gray-100 rounded-xl p-5 shadow-card transition-shadow hover:shadow-lg">
            <div class="flex items-start justify-between gap-4">
              <div>
                <div class="flex items-center gap-2 mb-1">
                  <p class="text-sm font-bold text-navy-900"><?= e($addr['recipient_name']) ?></p>
                  <?php if ($addr['is_default']): ?>
                    <span class="text-[10px] bg-cyan-50 text-cyan-600 font-bold px-2 py-0.5 rounded-full">Utama</span>
                  <?php endif; ?>
                </div>
                <p class="text-xs text-gray-500 mb-1"><?= e($addr['phone']) ?></p>
                <p class="text-sm text-gray-600"><?= e($addr['address']) ?>, <?= e($addr['city']) ?>, <?= e($addr['province']) ?> <?= e($addr['postal_code']) ?></p>
              </div>
              <div class="flex flex-col gap-2 text-xs shrink-0 text-right">
                <a href="addresses.php?edit=<?= (int) $addr['id'] ?>" class="text-cyan-600 hover:underline">Edit</a>
                <?php if (!$addr['is_default']): ?>
                  <form method="POST"><?= csrfField() ?><input type="hidden" name="form_action" value="set_default">
                    <input type="hidden" name="address_id" value="<?= (int) $addr['id'] ?>">
                    <button type="submit" class="text-gray-500 hover:underline">Jadikan Utama</button>
                  </form>
                <?php endif; ?>
                <form method="POST" onsubmit="return confirm('Hapus alamat ini?');">
                  <?= csrfField() ?><input type="hidden" name="form_action" value="delete">
                  <input type="hidden" name="address_id" value="<?= (int) $addr['id'] ?>">
                  <button type="submit" class="text-red-500 hover:underline">Hapus</button>
                </form>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>