<?php
/**
 * TechZone Admin - Kelola E-Wallet
 *
 * E-wallet di sini tampil sebagai pilihan "QRIS / E-Wallet" saat checkout dan
 * sebagai deretan logo di halaman pembayaran (lihat checkout.php, payment/qris.php,
 * payment/transfer.php). Admin bisa menambah, mengubah logo/warna, mengurutkan,
 * menonaktifkan, dan menghapus e-wallet.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/upload_helpers.php';

$admin  = currentAdmin();
$pdo    = getDbConnection();
$errors = [];
$editing = null;

tzEnsureEwalletTable($pdo); // DDL di luar transaksi

// Palet siap pakai agar admin tidak perlu tahu kode hex tiap e-wallet.
$colorPresets = [
    'DANA'      => ['#1CA1E9', '#ffffff', 'DANA'],
    'GoPay'     => ['#00AA5B', '#ffffff', 'Go|Pay'],
    'OVO'       => ['#4C2A86', '#ffffff', 'OVO'],
    'ShopeePay' => ['#EE4D2D', '#ffffff', 'SPay'],
    'LinkAja'   => ['#E4032E', '#ffffff', 'Link|Aja'],
    'Jenius'    => ['#EEF200', '#0a1128', 'Jenius'],
    'Sakuku'    => ['#0066AE', '#ffffff', 'Saku'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? null);
    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'toggle_status') {
        $id = (int) ($_POST['wallet_id'] ?? 0);
        $pdo->prepare('UPDATE ewallet_channels SET is_active = IF(is_active=1,0,1), updated_at = NOW() WHERE id = :id')->execute([':id' => $id]);
        setFlashMessage('success', 'Status e-wallet berhasil diubah.');
        redirectTo('admin/ewallets.php');
    }

    if ($formAction === 'delete') {
        $id = (int) ($_POST['wallet_id'] ?? 0);
        $oldStmt = $pdo->prepare('SELECT name, logo_image FROM ewallet_channels WHERE id = :id');
        $oldStmt->execute([':id' => $id]);
        $old = $oldStmt->fetch();
        if ($old) {
            // Pesanan lama menyimpan nama channel sebagai teks, jadi aman dihapus.
            $pdo->prepare('DELETE FROM ewallet_channels WHERE id = :id')->execute([':id' => $id]);
            if (!empty($old['logo_image']) && strpos($old['logo_image'], 'uploads/') === 0 && file_exists(__DIR__ . '/../' . $old['logo_image'])) {
                @unlink(__DIR__ . '/../' . $old['logo_image']);
            }
            logActivity($admin['id'], 'delete_ewallet', 'Menghapus e-wallet: ' . $old['name']);
            setFlashMessage('success', 'E-wallet "' . $old['name'] . '" berhasil dihapus.');
        }
        redirectTo('admin/ewallets.php');
    }

    if ($formAction === 'save') {
        $walletId   = (int) ($_POST['wallet_id'] ?? 0);
        $name       = trim($_POST['name'] ?? '');
        $mark       = trim($_POST['mark'] ?? '');
        $logoBg     = trim($_POST['logo_bg'] ?? '#1CA1E9');
        $logoFg     = trim($_POST['logo_fg'] ?? '#ffffff');
        $sortOrder  = max(0, (int) ($_POST['sort_order'] ?? 0));
        $isActive   = isset($_POST['is_active']) ? 1 : 0;
        $removeLogo = isset($_POST['remove_logo']);

        if ($name === '' || mb_strlen($name) > 50) $errors[] = 'Nama e-wallet wajib diisi (maksimal 50 karakter).';
        if (mb_strlen($mark) > 20) $errors[] = 'Inisial maksimal 20 karakter.';
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $logoBg)) $errors[] = 'Warna badge tidak valid (gunakan format #RRGGBB).';
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $logoFg)) $errors[] = 'Warna teks badge tidak valid (gunakan format #RRGGBB).';

        if ($name !== '') {
            $dup = $pdo->prepare('SELECT COUNT(*) FROM ewallet_channels WHERE name = :n AND id <> :id');
            $dup->execute([':n' => $name, ':id' => $walletId]);
            if ((int) $dup->fetchColumn() > 0) $errors[] = 'Nama e-wallet "' . $name . '" sudah ada.';
        }
        if ($mark === '' && $name !== '') {
            $mark = mb_strtoupper(mb_substr($name, 0, 4));
        }

        $logoImagePath = null; // null = tidak diubah
        if (!empty($_FILES['logo_image']['name'])) {
            try {
                $filename      = handleImageUpload($_FILES['logo_image'], 'ewallets', 1048576);
                $logoImagePath = 'uploads/ewallets/' . $filename;
            } catch (Throwable $e) {
                $errors[] = 'Logo: ' . $e->getMessage();
            }
        }

        if (empty($errors)) {
            if ($walletId > 0) {
                $oldStmt = $pdo->prepare('SELECT logo_image FROM ewallet_channels WHERE id = :id');
                $oldStmt->execute([':id' => $walletId]);
                $oldImg = $oldStmt->fetchColumn();

                $sql    = 'UPDATE ewallet_channels SET name=:name, mark=:mark, logo_bg=:bg, logo_fg=:fg, sort_order=:sort, is_active=:active, updated_at=NOW()';
                $params = [':name' => $name, ':mark' => $mark, ':bg' => $logoBg, ':fg' => $logoFg, ':sort' => $sortOrder, ':active' => $isActive, ':id' => $walletId];
                if ($logoImagePath !== null) {
                    $sql .= ', logo_image=:img';
                    $params[':img'] = $logoImagePath;
                } elseif ($removeLogo) {
                    $sql .= ', logo_image=NULL';
                }
                $pdo->prepare($sql . ' WHERE id=:id')->execute($params);

                if (($logoImagePath !== null || $removeLogo) && $oldImg && strpos($oldImg, 'uploads/') === 0 && file_exists(__DIR__ . '/../' . $oldImg)) {
                    @unlink(__DIR__ . '/../' . $oldImg);
                }
                setFlashMessage('success', 'E-wallet berhasil diperbarui.');
                logActivity($admin['id'], 'update_ewallet', 'Memperbarui e-wallet: ' . $name);
            } else {
                $pdo->prepare(
                    'INSERT INTO ewallet_channels (name, mark, logo_image, logo_bg, logo_fg, sort_order, is_active, created_at, updated_at)
                     VALUES (:name, :mark, :img, :bg, :fg, :sort, :active, NOW(), NOW())'
                )->execute([':name' => $name, ':mark' => $mark, ':img' => $logoImagePath, ':bg' => $logoBg, ':fg' => $logoFg, ':sort' => $sortOrder, ':active' => $isActive]);
                setFlashMessage('success', 'E-wallet baru berhasil ditambahkan.');
                logActivity($admin['id'], 'create_ewallet', 'Menambahkan e-wallet: ' . $name);
            }
            redirectTo('admin/ewallets.php');
        }

        // validasi gagal: tampilkan kembali form dengan isian pengguna
        $editing = ['id' => $walletId, 'name' => $name, 'mark' => $mark, 'logoBg' => $logoBg, 'logoFg' => $logoFg,
                    'sortOrder' => $sortOrder, 'isActive' => $isActive, 'logoImage' => null];
    }
}

if (isset($_GET['edit']) && $editing === null) {
    $stmt = $pdo->prepare('SELECT * FROM ewallet_channels WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => (int) $_GET['edit']]);
    $row = $stmt->fetch();
    if ($row) {
        $editing = ['id' => (int) $row['id'], 'name' => $row['name'], 'mark' => $row['mark'], 'logoImage' => $row['logo_image'],
                    'logoBg' => $row['logo_bg'], 'logoFg' => $row['logo_fg'], 'sortOrder' => (int) $row['sort_order'], 'isActive' => (int) $row['is_active']];
    }
}
$showForm = isset($_GET['add']) || $editing !== null;

$wallets = $pdo->query('SELECT * FROM ewallet_channels ORDER BY sort_order ASC, id ASC')->fetchAll();

$tzTab     = 'ewallet';
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
        <h1 class="reveal text-2xl font-extrabold text-navy-900">E-Wallet</h1>
        <?php if (!$showForm): ?><a href="ewallets.php?add=1" class="ripple-wrap btn-glow bg-cyan-500 hover:bg-cyan-400 text-navy-900 text-sm font-semibold px-4 py-2 rounded-lg transition"><i class="fa-solid fa-plus mr-1"></i>Tambah E-Wallet</a><?php endif; ?>
      </div>
      <p class="text-sm text-gray-500 mb-6">E-wallet yang <b>aktif</b> di sini muncul sebagai pilihan pada metode &quot;QRIS / E-Wallet&quot; saat checkout dan sebagai deretan logo di halaman pembayaran. Anda bisa mengganti logo, warna, urutan, atau menambah e-wallet baru.</p>

      <?php if ($showForm): ?>
      <div class="reveal bg-white border border-gray-100 rounded-xl p-6 mb-6 max-w-2xl shadow-card">
        <h2 class="text-sm font-bold text-navy-900 mb-4"><?= ($editing['id'] ?? 0) ? 'Edit E-Wallet' : 'Tambah E-Wallet' ?></h2>
        <?php if (!empty($errors)): ?>
          <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3 mb-4"><ul class="list-disc list-inside space-y-1"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
          <?= csrfField() ?>
          <input type="hidden" name="form_action" value="save">
          <input type="hidden" name="wallet_id" value="<?= (int) ($editing['id'] ?? 0) ?>">

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Logo E-Wallet (opsional &mdash; kotak persegi, PNG transparan disarankan)</label>
            <div class="flex items-center gap-4">
              <span id="techzone-ew-preview" class="inline-flex items-center justify-center w-16 h-16 rounded-xl font-extrabold text-xs shadow-sm border border-gray-200 overflow-hidden shrink-0 text-center leading-tight"
                    style="background:<?= !empty($editing['logoImage']) ? '#ffffff' : e($editing['logoBg'] ?? '#1CA1E9') ?>;color:<?= e($editing['logoFg'] ?? '#ffffff') ?>;">
                <?php if (!empty($editing['logoImage'])): ?>
                  <img id="techzone-ew-preview-img" src="<?= e(techzoneBaseUrl() . '/' . ltrim($editing['logoImage'], '/')) ?>" class="w-full h-full object-contain p-1.5" alt="">
                <?php else: ?>
                  <span id="techzone-ew-preview-text"><?= e(str_replace('|', ' ', $editing['mark'] ?? 'DANA')) ?></span>
                <?php endif; ?>
              </span>
              <div class="flex-1">
                <input type="file" name="logo_image" accept=".jpg,.jpeg,.png,.webp" onchange="techzonePreviewEwLogo(this)"
                       class="w-full text-xs text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-cyan-50 file:text-cyan-700 file:text-xs file:font-semibold hover:file:bg-cyan-100 transition-colors">
                <p class="text-[10px] text-gray-400 mt-1">JPG/PNG/WEBP, maks 1MB. Jika kosong, ditampilkan badge warna berisi inisial di bawah.</p>
                <?php if (!empty($editing['logoImage'])): ?>
                  <label class="inline-flex items-center gap-1.5 text-xs text-red-500 mt-1.5 cursor-pointer">
                    <input type="checkbox" name="remove_logo" value="1"> Hapus logo saat ini
                  </label>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Pilih E-Wallet Cepat (opsional &mdash; otomatis isi nama, inisial &amp; warna)</label>
            <select id="techzone-ew-preset" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
              <option value="">-- Pilih dari daftar e-wallet umum --</option>
              <?php foreach ($colorPresets as $pname => [$pbg, $pfg, $pmark]): ?>
                <option value="<?= e($pname) ?>" data-bg="<?= e($pbg) ?>" data-fg="<?= e($pfg) ?>" data-mark="<?= e($pmark) ?>"><?= e($pname) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Nama E-Wallet</label>
              <input type="text" id="techzone-ew-name" name="name" required maxlength="50" value="<?= e($editing['name'] ?? '') ?>" placeholder="mis. DANA, GoPay, OVO"
                     class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Inisial Badge</label>
              <input type="text" id="techzone-ew-mark" name="mark" maxlength="20" value="<?= e($editing['mark'] ?? '') ?>" placeholder="mis. DANA atau Go|Pay"
                     class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
              <p class="text-[10px] text-gray-400 mt-1">Gunakan <code>|</code> untuk pindah baris (mis. <code>Go|Pay</code>). Kosongkan untuk otomatis dari nama.</p>
            </div>
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 items-end">
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Warna Badge</label>
              <input type="color" id="techzone-ew-bg" name="logo_bg" value="<?= e($editing['logoBg'] ?? '#1CA1E9') ?>" class="w-full h-10 rounded-lg border border-gray-300 cursor-pointer">
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Warna Teks Badge</label>
              <input type="color" id="techzone-ew-fg" name="logo_fg" value="<?= e($editing['logoFg'] ?? '#ffffff') ?>" class="w-full h-10 rounded-lg border border-gray-300 cursor-pointer">
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Urutan</label>
              <input type="number" min="0" name="sort_order" value="<?= e((string) ($editing['sortOrder'] ?? (count($wallets) + 1))) ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm">
            </div>
            <div class="flex items-center h-10">
              <label class="flex items-center gap-2 text-sm cursor-pointer">
                <input type="checkbox" name="is_active" value="1" <?= ($editing['isActive'] ?? 1) ? 'checked' : '' ?>> Aktif
              </label>
            </div>
          </div>

          <div class="flex gap-3">
            <button type="submit" class="ripple-wrap btn-glow bg-cyan-500 hover:bg-cyan-400 text-navy-900 font-semibold text-sm px-6 py-2.5 rounded-lg transition">Simpan</button>
            <a href="ewallets.php" class="text-gray-500 text-sm font-medium px-6 py-2.5 hover:text-navy-900">Batal</a>
          </div>
        </form>
      </div>
      <?php endif; ?>

      <div class="bg-white border border-gray-100 rounded-xl overflow-hidden shadow-card">
        <div class="tz-tablewrap">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
            <tr>
              <th class="text-left px-4 py-3">E-Wallet</th>
              <th class="text-left px-4 py-3">Inisial</th>
              <th class="text-center px-4 py-3">Urutan</th>
              <th class="text-center px-4 py-3">Status</th>
              <th class="text-center px-4 py-3">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <?php if (empty($wallets)): ?>
              <tr><td colspan="5" class="px-4 py-10 text-center text-gray-400">Belum ada e-wallet. Klik &quot;Tambah E-Wallet&quot; untuk menambahkan.</td></tr>
            <?php endif; ?>
            <?php foreach ($wallets as $w): $hasLogo = !empty($w['logo_image']); ?>
            <tr class="hover:bg-cyan-50/40 transition-colors duration-150">
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <span class="w-12 h-12 rounded-xl flex items-center justify-center font-extrabold text-[10px] text-center leading-tight shrink-0 overflow-hidden border border-gray-200" style="background:<?= $hasLogo ? '#ffffff' : e($w['logo_bg']) ?>;color:<?= e($w['logo_fg']) ?>;">
                    <?php if ($hasLogo): ?>
                      <img src="<?= e(techzoneBaseUrl() . '/' . ltrim($w['logo_image'], '/')) ?>" class="w-full h-full object-contain p-1.5" alt="<?= e($w['name']) ?>">
                    <?php else: ?>
                      <?= e(str_replace('|', ' ', $w['mark'] !== '' ? $w['mark'] : mb_strtoupper(mb_substr($w['name'], 0, 4)))) ?>
                    <?php endif; ?>
                  </span>
                  <span class="font-semibold text-navy-900"><?= e($w['name']) ?></span>
                </div>
              </td>
              <td class="px-4 py-3 text-gray-600 font-mono text-xs"><?= e($w['mark']) ?></td>
              <td class="px-4 py-3 text-center text-gray-500"><?= (int) $w['sort_order'] ?></td>
              <td class="px-4 py-3 text-center"><span class="text-[11px] font-semibold px-2 py-1 rounded-full <?= $w['is_active'] ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' ?>"><?= $w['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
              <td class="px-4 py-3">
                <div class="flex items-center justify-center gap-3 text-xs">
                  <a href="ewallets.php?edit=<?= (int) $w['id'] ?>" class="text-cyan-600 hover:underline">Edit</a>
                  <form method="POST"><?= csrfField() ?><input type="hidden" name="form_action" value="toggle_status"><input type="hidden" name="wallet_id" value="<?= (int) $w['id'] ?>"><button type="submit" class="text-gray-500 hover:underline"><?= $w['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?></button></form>
                  <form method="POST" onsubmit="return confirm('Hapus e-wallet ini dari daftar pembayaran?');"><?= csrfField() ?><input type="hidden" name="form_action" value="delete"><input type="hidden" name="wallet_id" value="<?= (int) $w['id'] ?>"><button type="submit" class="text-red-500 hover:underline">Hapus</button></form>
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
  var ewPreset  = document.getElementById('techzone-ew-preset');
  var ewName    = document.getElementById('techzone-ew-name');
  var ewMark    = document.getElementById('techzone-ew-mark');
  var ewBg      = document.getElementById('techzone-ew-bg');
  var ewFg      = document.getElementById('techzone-ew-fg');
  var ewPreview = document.getElementById('techzone-ew-preview');

  function ewUpdatePreview() {
    if (!ewPreview || document.getElementById('techzone-ew-preview-img')) return; // sedang menampilkan logo
    ewPreview.style.background = ewBg.value;
    ewPreview.style.color = ewFg.value;
    var txt = (ewMark.value || ewName.value || 'DANA').replace('|', ' ').slice(0, 10);
    var el = document.getElementById('techzone-ew-preview-text');
    if (el) { el.textContent = txt; } else { ewPreview.textContent = txt; }
  }

  // Preview file logo yang baru dipilih.
  function techzonePreviewEwLogo(input) {
    if (!ewPreview || !input.files || !input.files[0]) return;
    var reader = new FileReader();
    reader.onload = function (e) {
      ewPreview.style.background = '#ffffff';
      ewPreview.innerHTML = '<img id="techzone-ew-preview-img" src="' + e.target.result + '" class="w-full h-full object-contain p-1.5" alt="">';
    };
    reader.readAsDataURL(input.files[0]);
  }

  if (ewPreset) {
    ewPreset.addEventListener('change', function () {
      var opt = this.options[this.selectedIndex];
      if (!opt.value) return;
      ewName.value = opt.value;
      ewMark.value = opt.dataset.mark;
      ewBg.value = opt.dataset.bg;
      ewFg.value = opt.dataset.fg;
      ewUpdatePreview();
    });
  }
  [ewName, ewMark, ewBg, ewFg].forEach(function (el) { if (el) el.addEventListener('input', ewUpdatePreview); });
</script>
</body>
</html>
