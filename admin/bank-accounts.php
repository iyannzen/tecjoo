<?php
/**
 * TechZone Admin - Kelola Rekening Bank Tujuan Transfer
 *
 * Admin bisa menambah lebih dari satu rekening bank (BCA, BRI, BNI, dst)
 * sehingga pembeli benar-benar bisa memilih bank tujuan transfer saat
 * checkout (lihat checkout.php & payment/transfer.php).
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/upload_helpers.php';

$admin  = currentAdmin();
$pdo    = getDbConnection();
$errors = [];
$editingBank = null;

// Palet warna siap pakai agar admin tidak perlu tahu kode hex bank.
$colorPresets = [
    'BCA'         => ['#005AAB', '#ffffff'],
    'BRI'         => ['#00529C', '#ffffff'],
    'BNI'         => ['#F37021', '#ffffff'],
    'Mandiri'     => ['#003D79', '#FFC600'],
    'BSI'         => ['#00A651', '#ffffff'],
    'BTN'         => ['#1E4B9B', '#ffffff'],
    'CIMB Niaga'  => ['#7A1315', '#ffffff'],
    'Permata'     => ['#00A99D', '#ffffff'],
    'Danamon'     => ['#F7941D', '#ffffff'],
    'SeaBank'     => ['#F26A21', '#ffffff'],
    'Maybank'     => ['#FFC72C', '#0a1128'],
];

// Bank tambahan yang disiapkan otomatis SEKALI (nonaktif + nomor rekening kosong).
// Admin tinggal mengisi nomor rekening lalu mengaktifkannya.
$extraBanks = [
    ['SeaBank', '#F26A21', '#ffffff', 7],
    ['BTN',     '#1E4B9B', '#ffffff', 8],
    ['Permata', '#00A99D', '#ffffff', 9],
    ['Danamon', '#F7941D', '#ffffff', 10],
];
try {
    $seedFlag = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'seed_extra_banks_v1' LIMIT 1")->fetchColumn();
    if ($seedFlag === false || $seedFlag === null || $seedFlag === '') {
        $exists = $pdo->prepare('SELECT COUNT(*) FROM bank_accounts WHERE bank_name = :n');
        $insert = $pdo->prepare("INSERT INTO bank_accounts (bank_name, account_number, account_holder, logo_bg, logo_fg, sort_order, is_active, created_at, updated_at)
                                 VALUES (:n, '0000000000', 'PT TechZone Indonesia', :bg, :fg, :s, 0, NOW(), NOW())");
        $added = [];
        foreach ($extraBanks as [$n, $bg, $fg, $sort]) {
            $exists->execute([':n' => $n]);
            if ((int) $exists->fetchColumn() === 0) {
                $insert->execute([':n' => $n, ':bg' => $bg, ':fg' => $fg, ':s' => $sort]);
                $added[] = $n;
            }
        }
        $pdo->exec("INSERT INTO settings (setting_key, setting_value) VALUES ('seed_extra_banks_v1', '1') ON DUPLICATE KEY UPDATE setting_value = '1'");
        if ($added) {
            setFlashMessage('success', 'Bank ' . implode(', ', $added) . ' ditambahkan (nonaktif). Edit untuk mengisi nomor rekening, lalu aktifkan.');
        }
    }
} catch (Throwable $e) {
    error_log('[TechZone][Seed Banks] ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? null);
    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'toggle_status') {
        $id = (int) ($_POST['bank_id'] ?? 0);
        $cur = $pdo->prepare('SELECT is_active, account_number FROM bank_accounts WHERE id = :id');
        $cur->execute([':id' => $id]);
        $curRow = $cur->fetch();
        if ($curRow && (int) $curRow['is_active'] === 0 && preg_match('/^0+$/', (string) $curRow['account_number'])) {
            setFlashMessage('error', 'Nomor rekening belum diisi. Klik Edit, isi nomor rekening yang benar, lalu aktifkan.');
            redirectTo('admin/bank-accounts.php');
        }
        $pdo->prepare('UPDATE bank_accounts SET is_active = IF(is_active=1,0,1), updated_at = NOW() WHERE id = :id')->execute([':id' => $id]);
        setFlashMessage('success', 'Status rekening berhasil diubah.');
        redirectTo('admin/bank-accounts.php');
    }

    if ($formAction === 'delete') {
        $id = (int) ($_POST['bank_id'] ?? 0);
        // Rekening yang sudah pernah dipakai order (bank_account_id) tidak dihapus,
        // cukup dinonaktifkan, agar histori pesanan lama tidak kehilangan datanya.
        $usedCheck = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE bank_account_id = :id');
        $usedCheck->execute([':id' => $id]);
        if ((int) $usedCheck->fetchColumn() > 0) {
            $pdo->prepare('UPDATE bank_accounts SET is_active = 0, updated_at = NOW() WHERE id = :id')->execute([':id' => $id]);
            setFlashMessage('success', 'Rekening sudah pernah dipakai transaksi, jadi hanya dinonaktifkan (tidak dihapus).');
        } else {
            $oldStmt = $pdo->prepare('SELECT logo_image FROM bank_accounts WHERE id = :id');
            $oldStmt->execute([':id' => $id]);
            $oldImg = $oldStmt->fetchColumn();
            $pdo->prepare('DELETE FROM bank_accounts WHERE id = :id')->execute([':id' => $id]);
            if ($oldImg && file_exists(__DIR__ . '/../' . $oldImg)) {
                @unlink(__DIR__ . '/../' . $oldImg);
            }
            logActivity($admin['id'], 'delete_bank_account', 'Menghapus rekening bank #' . $id);
            setFlashMessage('success', 'Rekening bank berhasil dihapus.');
        }
        redirectTo('admin/bank-accounts.php');
    }

    if ($formAction === 'save') {
        $bankId        = (int) ($_POST['bank_id'] ?? 0);
        $bankName      = trim($_POST['bank_name'] ?? '');
        $accountNumber = trim($_POST['account_number'] ?? '');
        $accountHolder = trim($_POST['account_holder'] ?? '');
        $logoBg        = trim($_POST['logo_bg'] ?? '#005AAB');
        $logoFg        = trim($_POST['logo_fg'] ?? '#ffffff');
        $sortOrder     = (int) ($_POST['sort_order'] ?? 0);
        $isActive      = isset($_POST['is_active']) ? 1 : 0;
        $removeLogo    = isset($_POST['remove_logo']);

        if ($bankName === '') $errors[] = 'Nama bank wajib diisi.';
        if ($accountNumber === '' || !preg_match('/^[0-9\-]{4,50}$/', $accountNumber)) $errors[] = 'Nomor rekening wajib diisi (angka, 4-50 digit).';
        if ($accountHolder === '') $errors[] = 'Nama pemilik rekening wajib diisi.';
        if ($isActive === 1 && preg_match('/^0+$/', $accountNumber)) $errors[] = 'Nomor rekening belum diisi dengan benar, jadi rekening tidak bisa diaktifkan.';
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $logoBg)) $errors[] = 'Warna badge tidak valid (gunakan format #RRGGBB).';
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $logoFg)) $errors[] = 'Warna teks badge tidak valid (gunakan format #RRGGBB).';

        $logoImagePath = null; // null = tidak diubah
        if (!empty($_FILES['logo_image']['name'])) {
            try {
                $filename      = handleImageUpload($_FILES['logo_image'], 'banks', 1048576);
                $logoImagePath = 'uploads/banks/' . $filename;
            } catch (Throwable $e) {
                $errors[] = 'Logo: ' . $e->getMessage();
            }
        }

        if (empty($errors)) {
            $oldImg = null;
            if ($bankId > 0) {
                $oldStmt = $pdo->prepare('SELECT logo_image FROM bank_accounts WHERE id = :id');
                $oldStmt->execute([':id' => $bankId]);
                $oldImg = $oldStmt->fetchColumn();

                $sql = 'UPDATE bank_accounts SET bank_name=:name, account_number=:num, account_holder=:holder,
                        logo_bg=:bg, logo_fg=:fg, sort_order=:sort, is_active=:active, updated_at=NOW()';
                $params = [':name'=>$bankName, ':num'=>$accountNumber, ':holder'=>$accountHolder, ':bg'=>$logoBg, ':fg'=>$logoFg, ':sort'=>$sortOrder, ':active'=>$isActive, ':id'=>$bankId];
                if ($logoImagePath !== null) {
                    $sql .= ', logo_image=:img';
                    $params[':img'] = $logoImagePath;
                } elseif ($removeLogo) {
                    $sql .= ', logo_image=NULL';
                }
                $sql .= ' WHERE id=:id';
                $pdo->prepare($sql)->execute($params);

                if (($logoImagePath !== null || $removeLogo) && $oldImg && file_exists(__DIR__ . '/../' . $oldImg)) {
                    @unlink(__DIR__ . '/../' . $oldImg);
                }
                setFlashMessage('success', 'Rekening bank berhasil diperbarui.');
                logActivity($admin['id'], 'update_bank_account', 'Memperbarui rekening: ' . $bankName);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO bank_accounts (bank_name, account_number, account_holder, logo_image, logo_bg, logo_fg, sort_order, is_active, created_at, updated_at)
                     VALUES (:name, :num, :holder, :img, :bg, :fg, :sort, :active, NOW(), NOW())'
                );
                $stmt->execute([':name'=>$bankName, ':num'=>$accountNumber, ':holder'=>$accountHolder, ':img'=>$logoImagePath, ':bg'=>$logoBg, ':fg'=>$logoFg, ':sort'=>$sortOrder, ':active'=>$isActive]);
                setFlashMessage('success', 'Rekening bank baru berhasil ditambahkan.');
                logActivity($admin['id'], 'create_bank_account', 'Menambahkan rekening: ' . $bankName);
            }
            redirectTo('admin/bank-accounts.php');
        }

        $editingBank = compact('bankId', 'bankName', 'accountNumber', 'accountHolder', 'logoBg', 'logoFg', 'sortOrder', 'isActive');
    }
}

if (isset($_GET['edit']) && $editingBank === null) {
    $stmt = $pdo->prepare('SELECT * FROM bank_accounts WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => (int) $_GET['edit']]);
    $row = $stmt->fetch();
    if ($row) {
        $editingBank = [
            'bankId' => $row['id'], 'bankName' => $row['bank_name'], 'accountNumber' => $row['account_number'],
            'accountHolder' => $row['account_holder'], 'logoImage' => $row['logo_image'],
            'logoBg' => $row['logo_bg'], 'logoFg' => $row['logo_fg'],
            'sortOrder' => $row['sort_order'], 'isActive' => (int) $row['is_active'],
        ];
    }
}
$showForm = isset($_GET['add']) || $editingBank !== null;

$bankAccounts = $pdo->query('SELECT * FROM bank_accounts ORDER BY sort_order ASC, id ASC')->fetchAll();

$tzTab = 'bank';
$pageTitle = 'Rekening Bank & E-Wallet | TechZone Admin';
require __DIR__ . '/../includes/header.php';
?>
<div class="min-h-screen flex bg-gray-50">
  <?php require __DIR__ . '/_sidebar.php'; ?>
  <div id="techzone-admin-content" class="flex-1">
    <?php require __DIR__ . '/_topbar.php'; ?>
    <main class="p-6">
      <?php require __DIR__ . '/_payment_tabs.php'; ?>
      <div class="flex items-center justify-between mb-2 flex-wrap gap-3">
        <h1 class="reveal text-2xl font-extrabold text-navy-900">Rekening Bank Tujuan</h1>
        <?php if (!$showForm): ?><a href="bank-accounts.php?add=1" class="ripple-wrap btn-glow bg-cyan-500 hover:bg-cyan-400 text-navy-900 text-sm font-semibold px-4 py-2 rounded-lg transition"><i class="fa-solid fa-plus mr-1"></i>Tambah Rekening</a><?php endif; ?>
      </div>
      <p class="text-sm text-gray-500 mb-6">Rekening di sini akan muncul sebagai pilihan bank tujuan transfer saat pembeli checkout. Tambahkan lebih dari satu bank (BCA, BRI, BNI, dst) agar pembeli punya pilihan.</p>

      <?php if ($showForm): ?>
      <div class="reveal bg-white border border-gray-100 rounded-xl p-6 mb-6 max-w-2xl shadow-card">
        <h2 class="text-sm font-bold text-navy-900 mb-4"><?= ($editingBank['bankId'] ?? 0) ? 'Edit Rekening' : 'Tambah Rekening Bank' ?></h2>
        <?php if (!empty($errors)): ?>
          <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3 mb-4"><ul class="list-disc list-inside space-y-1"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
          <?= csrfField() ?>
          <input type="hidden" name="form_action" value="save">
          <input type="hidden" name="bank_id" value="<?= (int) ($editingBank['bankId'] ?? 0) ?>">

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Logo Bank (opsional — kotak persegi, PNG transparan disarankan)</label>
            <div class="flex items-center gap-4">
              <span id="techzone-bank-preview" class="inline-flex items-center justify-center w-16 h-16 rounded-xl font-extrabold text-xs shadow-sm border border-gray-200 overflow-hidden shrink-0"
                    style="background:<?= e($editingBank['logoBg'] ?? '#005AAB') ?>;color:<?= e($editingBank['logoFg'] ?? '#ffffff') ?>;">
                <?php if (!empty($editingBank['logoImage'])): ?>
                  <img id="techzone-bank-preview-img" src="<?= e(techzoneBaseUrl() . '/' . ltrim($editingBank['logoImage'], '/')) ?>" class="w-full h-full object-contain p-1.5" alt="">
                <?php else: ?>
                  <span id="techzone-bank-preview-text"><?= e(mb_strtoupper(mb_substr($editingBank['bankName'] ?? 'BCA', 0, 4))) ?></span>
                <?php endif; ?>
              </span>
              <div class="flex-1">
                <input type="file" name="logo_image" accept=".jpg,.jpeg,.png,.webp" onchange="techzonePreviewBankLogo(this)"
                       class="w-full text-xs text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-cyan-50 file:text-cyan-700 file:text-xs file:font-semibold hover:file:bg-cyan-100 transition-colors">
                <p class="text-[10px] text-gray-400 mt-1">JPG/PNG/WEBP, maks 1MB. Jika kosong, logo tetap pakai badge warna di bawah sebagai cadangan.</p>
                <?php if (!empty($editingBank['logoImage'])): ?>
                  <label class="inline-flex items-center gap-1.5 text-xs text-red-500 mt-1.5 cursor-pointer">
                    <input type="checkbox" name="remove_logo" value="1"> Hapus logo saat ini
                  </label>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Pilih Bank Cepat (opsional — otomatis isi warna badge)</label>
            <select id="techzone-bank-preset" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
              <option value="">-- Pilih dari daftar bank umum --</option>
              <?php foreach ($colorPresets as $name => $colors): ?>
                <option value="<?= e($name) ?>" data-bg="<?= e($colors[0]) ?>" data-fg="<?= e($colors[1]) ?>"><?= e($name) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Nama Bank</label>
              <input type="text" id="techzone-bank-name" name="bank_name" required value="<?= e($editingBank['bankName'] ?? '') ?>" placeholder="mis. BCA, BRI, BNI"
                     class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Nomor Rekening</label>
              <input type="text" name="account_number" required value="<?= e($editingBank['accountNumber'] ?? '') ?>" placeholder="1234567890"
                     class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
            </div>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Atas Nama</label>
            <input type="text" name="account_holder" required value="<?= e($editingBank['accountHolder'] ?? 'PT TechZone Indonesia') ?>"
                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 items-end">
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Warna Badge</label>
              <input type="color" id="techzone-bank-bg" name="logo_bg" value="<?= e($editingBank['logoBg'] ?? '#005AAB') ?>" class="w-full h-10 rounded-lg border border-gray-300 cursor-pointer">
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Warna Teks Badge</label>
              <input type="color" id="techzone-bank-fg" name="logo_fg" value="<?= e($editingBank['logoFg'] ?? '#ffffff') ?>" class="w-full h-10 rounded-lg border border-gray-300 cursor-pointer">
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Urutan</label>
              <input type="number" name="sort_order" value="<?= e((string) ($editingBank['sortOrder'] ?? 0)) ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm">
            </div>
            <div class="flex items-center h-10">
              <label class="flex items-center gap-2 text-sm cursor-pointer">
                <input type="checkbox" name="is_active" value="1" <?= ($editingBank['isActive'] ?? 1) ? 'checked' : '' ?>> Aktif
              </label>
            </div>
          </div>

          <div class="flex gap-3">
            <button type="submit" class="ripple-wrap btn-glow bg-cyan-500 hover:bg-cyan-400 text-navy-900 font-semibold text-sm px-6 py-2.5 rounded-lg transition">Simpan</button>
            <a href="bank-accounts.php" class="text-gray-500 text-sm font-medium px-6 py-2.5 hover:text-navy-900">Batal</a>
          </div>
        </form>
      </div>
      <?php endif; ?>

      <div class="bg-white border border-gray-100 rounded-xl overflow-hidden shadow-card">
        <div class="tz-tablewrap">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
            <tr>
              <th class="text-left px-4 py-3">Bank</th>
              <th class="text-left px-4 py-3">No. Rekening</th>
              <th class="text-left px-4 py-3">Atas Nama</th>
              <th class="text-center px-4 py-3">Urutan</th>
              <th class="text-center px-4 py-3">Status</th>
              <th class="text-center px-4 py-3">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <?php if (empty($bankAccounts)): ?>
              <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">Belum ada rekening bank. Klik "Tambah Rekening" untuk menambahkan.</td></tr>
            <?php endif; ?>
            <?php foreach ($bankAccounts as $b): ?>
            <tr class="hover:bg-cyan-50/40 transition-colors duration-150">
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <?php $hasLogo = !empty($b['logo_image']); ?>
                    <span class="w-12 h-12 rounded-xl flex items-center justify-center font-extrabold text-[10px] shrink-0 overflow-hidden border border-gray-200" style="background:<?= $hasLogo ? '#ffffff' : e($b['logo_bg']) ?>;color:<?= e($b['logo_fg']) ?>;">
                      <?php if ($hasLogo): ?>
                        <img src="<?= e(techzoneBaseUrl() . '/' . ltrim($b['logo_image'], '/')) ?>" class="w-full h-full object-contain p-1.5" alt="<?= e($b['bank_name']) ?>">
                      <?php else: ?>
                        <?= e(mb_strtoupper(mb_substr($b['bank_name'], 0, 4))) ?>
                      <?php endif; ?>
                    </span>
                  <span class="font-semibold text-navy-900"><?= e($b['bank_name']) ?></span>
                </div>
              </td>
              <td class="px-4 py-3 font-mono text-gray-600"><?= e($b['account_number']) ?></td>
              <td class="px-4 py-3 text-gray-600"><?= e($b['account_holder']) ?></td>
              <td class="px-4 py-3 text-center text-gray-500"><?= (int) $b['sort_order'] ?></td>
              <td class="px-4 py-3 text-center"><span class="text-[11px] font-semibold px-2 py-1 rounded-full <?= $b['is_active'] ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' ?>"><?= $b['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
              <td class="px-4 py-3">
                <div class="flex items-center justify-center gap-2 text-xs">
                  <a href="bank-accounts.php?edit=<?= (int) $b['id'] ?>" class="text-cyan-600 hover:underline">Edit</a>
                  <form method="POST"><?= csrfField() ?><input type="hidden" name="form_action" value="toggle_status"><input type="hidden" name="bank_id" value="<?= (int) $b['id'] ?>"><button type="submit" class="text-gray-500 hover:underline"><?= $b['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?></button></form>
                  <form method="POST" onsubmit="return confirm('Hapus rekening ini? Jika sudah pernah dipakai transaksi, rekening hanya akan dinonaktifkan.');"><?= csrfField() ?><input type="hidden" name="form_action" value="delete"><input type="hidden" name="bank_id" value="<?= (int) $b['id'] ?>"><button type="submit" class="text-red-500 hover:underline">Hapus</button></form>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        </div>
      </div>
    </main>
  </div>
</div>
<script>
  var presetSelect = document.getElementById('techzone-bank-preset');
  var nameInput    = document.getElementById('techzone-bank-name');
  var bgInput      = document.getElementById('techzone-bank-bg');
  var fgInput      = document.getElementById('techzone-bank-fg');
  var preview      = document.getElementById('techzone-bank-preview');

  function updatePreview() {
    if (!preview) return;
    // Hanya update warna/inisial jika saat ini TIDAK sedang menampilkan gambar upload.
    var img = document.getElementById('techzone-bank-preview-img');
    if (img) return;
    preview.style.background = bgInput.value;
    preview.style.color = fgInput.value;
    var textEl = document.getElementById('techzone-bank-preview-text');
    var mark = (nameInput.value || 'BCA').toUpperCase().slice(0, 4);
    if (textEl) {
      textEl.textContent = mark;
    } else {
      preview.textContent = mark;
    }
  }

  // Preview file logo yang baru dipilih, menggantikan badge warna sementara.
  function techzonePreviewBankLogo(input) {
    if (!preview || !input.files || !input.files[0]) return;
    var reader = new FileReader();
    reader.onload = function (e) {
      preview.style.background = 'transparent';
      preview.innerHTML = '<img id="techzone-bank-preview-img" src="' + e.target.result + '" class="w-full h-full object-contain p-1.5" alt="">';
    };
    reader.readAsDataURL(input.files[0]);
  }

  if (presetSelect) {
    presetSelect.addEventListener('change', function () {
      var opt = this.options[this.selectedIndex];
      if (!opt.value) return;
      nameInput.value = opt.value;
      bgInput.value = opt.dataset.bg;
      fgInput.value = opt.dataset.fg;
      updatePreview();
    });
  }
  [nameInput, bgInput, fgInput].forEach(function (el) {
    if (el) el.addEventListener('input', updatePreview);
  });
</script>
</body>
</html>
