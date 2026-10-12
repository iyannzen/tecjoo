<?php
/**
 * TechZone Admin - Kelola Banner
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/upload_helpers.php';

$admin = currentAdmin();
$pdo   = getDbConnection();
$errors = [];
$editingBanner = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? null);
    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'toggle_status') {
        $id = (int) ($_POST['banner_id'] ?? 0);
        $pdo->prepare("UPDATE banners SET status = IF(status='active','inactive','active') WHERE id = :id")->execute([':id' => $id]);
        setFlashMessage('success', 'Status banner berhasil diubah.');
        redirectTo('admin/banners.php');
    }

    if ($formAction === 'delete') {
        $id = (int) ($_POST['banner_id'] ?? 0);
        $pdo->prepare('DELETE FROM banners WHERE id = :id')->execute([':id' => $id]);
        logActivity($admin['id'], 'delete_banner', 'Menghapus banner #' . $id);
        setFlashMessage('success', 'Banner berhasil dihapus.');
        redirectTo('admin/banners.php');
    }

    if ($formAction === 'save') {
        $bannerId   = (int) ($_POST['banner_id'] ?? 0);
        $title      = trim($_POST['title'] ?? '');
        $subtitle   = trim($_POST['subtitle'] ?? '');
        $buttonText = trim($_POST['button_text'] ?? '');
        $buttonUrl  = trim($_POST['button_url'] ?? '');
        $sortOrder  = (int) ($_POST['sort_order'] ?? 0);
        $status     = ($_POST['status'] ?? 'active') === 'active' ? 'active' : 'inactive';

        if ($title === '') $errors[] = 'Judul banner wajib diisi.';

        $imageFilename = null;
        if (!empty($_FILES['image']['name'])) {
            try { $imageFilename = handleImageUpload($_FILES['image'], 'banners'); }
            catch (RuntimeException $e) { $errors[] = $e->getMessage(); }
        } elseif ($bannerId === 0) {
            $errors[] = 'Gambar banner wajib diunggah untuk banner baru.';
        }

        if (empty($errors)) {
            if ($bannerId > 0) {
                if ($imageFilename) {
                    $pdo->prepare('UPDATE banners SET title=:t, subtitle=:s, image=:img, button_text=:bt, button_url=:bu, sort_order=:so, status=:st WHERE id=:id')
                        ->execute([':t'=>$title, ':s'=>$subtitle, ':img'=>'uploads/banners/'.$imageFilename, ':bt'=>$buttonText, ':bu'=>$buttonUrl, ':so'=>$sortOrder, ':st'=>$status, ':id'=>$bannerId]);
                } else {
                    $pdo->prepare('UPDATE banners SET title=:t, subtitle=:s, button_text=:bt, button_url=:bu, sort_order=:so, status=:st WHERE id=:id')
                        ->execute([':t'=>$title, ':s'=>$subtitle, ':bt'=>$buttonText, ':bu'=>$buttonUrl, ':so'=>$sortOrder, ':st'=>$status, ':id'=>$bannerId]);
                }
                setFlashMessage('success', 'Banner berhasil diperbarui.');
            } else {
                $pdo->prepare('INSERT INTO banners (title, subtitle, image, button_text, button_url, status, sort_order, created_at) VALUES (:t,:s,:img,:bt,:bu,:st,:so,NOW())')
                    ->execute([':t'=>$title, ':s'=>$subtitle, ':img'=>'uploads/banners/'.$imageFilename, ':bt'=>$buttonText, ':bu'=>$buttonUrl, ':st'=>$status, ':so'=>$sortOrder]);
                setFlashMessage('success', 'Banner baru berhasil ditambahkan.');
            }
            logActivity($admin['id'], 'save_banner', 'Menyimpan banner: ' . $title);
            redirectTo('admin/banners.php');
        }
        $editingBanner = compact('bannerId', 'title', 'subtitle', 'buttonText', 'buttonUrl', 'sortOrder', 'status');
    }
}

if (isset($_GET['edit']) && $editingBanner === null) {
    $stmt = $pdo->prepare('SELECT * FROM banners WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => (int) $_GET['edit']]);
    $row = $stmt->fetch();
    if ($row) {
        $editingBanner = ['bannerId'=>$row['id'],'title'=>$row['title'],'subtitle'=>$row['subtitle'],'buttonText'=>$row['button_text'],'buttonUrl'=>$row['button_url'],'sortOrder'=>$row['sort_order'],'status'=>$row['status'],'image'=>$row['image']];
    }
}
$showForm = isset($_GET['add']) || $editingBanner !== null;

$banners = $pdo->query('SELECT * FROM banners ORDER BY sort_order ASC, created_at DESC')->fetchAll();

$pageTitle = 'Kelola Banner | TechZone Admin';
require __DIR__ . '/../includes/header.php';
?>
<div class="min-h-screen flex bg-gray-50">
  <?php require __DIR__ . '/_sidebar.php'; ?>
  <div id="techzone-admin-content" class="flex-1">
    <?php require __DIR__ . '/_topbar.php'; ?>
    <main class="p-6">
      <div class="flex items-center justify-between mb-6">
        <h1 class="reveal text-2xl font-extrabold text-navy-900">Kelola Banner</h1>
        <?php if (!$showForm): ?><a href="banners.php?add=1" class="ripple-wrap btn-glow bg-cyan-500 hover:bg-cyan-400 text-navy-900 text-sm font-semibold px-4 py-2 rounded-lg transition"><i class="fa-solid fa-plus mr-1"></i>Tambah Banner</a><?php endif; ?>
      </div>
      <p class="text-xs text-gray-400 mb-5 max-w-xl">Banner aktif akan tampil sebagai strip promosi tambahan di bawah hero homepage, diurutkan berdasarkan Urutan.</p>

      <?php if ($showForm): ?>
      <div class="bg-white border border-gray-100 rounded-xl p-6 mb-6 max-w-xl shadow-card transition-shadow hover:shadow-lg">
        <h2 class="text-sm font-bold text-navy-900 mb-4"><?= ($editingBanner['bannerId'] ?? 0) ? 'Edit Banner' : 'Tambah Banner' ?></h2>
        <?php if (!empty($errors)): ?><div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3 mb-4"><ul class="list-disc list-inside space-y-1"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
          <?= csrfField() ?>
          <input type="hidden" name="form_action" value="save">
          <input type="hidden" name="banner_id" value="<?= (int) ($editingBanner['bannerId'] ?? 0) ?>">
          <div><label class="block text-xs font-medium text-gray-700 mb-1">Judul</label><input type="text" name="title" required value="<?= e($editingBanner['title'] ?? '') ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
          <div><label class="block text-xs font-medium text-gray-700 mb-1">Subjudul</label><input type="text" name="subtitle" value="<?= e($editingBanner['subtitle'] ?? '') ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
          <div class="grid grid-cols-2 gap-4">
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Teks Tombol</label><input type="text" name="button_text" value="<?= e($editingBanner['buttonText'] ?? '') ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">URL Tombol</label><input type="text" name="button_url" value="<?= e($editingBanner['buttonUrl'] ?? '') ?>" placeholder="products.php" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
          </div>
          <div><label class="block text-xs font-medium text-gray-700 mb-1">Gambar Banner <?= !empty($editingBanner['image']) ? '(kosongkan jika tidak diganti)' : '' ?></label><input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" class="w-full text-xs border border-gray-300 rounded-lg px-3 py-2"></div>
          <div class="grid grid-cols-2 gap-4">
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Urutan</label><input type="number" name="sort_order" value="<?= e((string) ($editingBanner['sortOrder'] ?? 0)) ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
            <div class="flex items-end"><label class="flex items-center gap-2 text-sm cursor-pointer"><input type="checkbox" name="status" value="active" <?= ($editingBanner['status'] ?? 'active') === 'active' ? 'checked' : '' ?>> Aktif</label></div>
          </div>
          <div class="flex gap-3"><button type="submit" class="ripple-wrap btn-glow bg-cyan-500 hover:bg-cyan-400 text-navy-900 font-semibold text-sm px-6 py-2.5 rounded-lg transition">Simpan</button><a href="banners.php" class="text-gray-500 text-sm font-medium px-6 py-2.5 hover:text-navy-900">Batal</a></div>
        </form>
      </div>
      <?php endif; ?>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php foreach ($banners as $b): ?>
        <div class="bg-white border border-gray-100 rounded-xl overflow-hidden">
          <img src="<?= e(techzoneBaseUrl() . '/' . ltrim($b['image'], '/')) ?>" alt="<?= e($b['title']) ?>" class="w-full h-32 object-cover">
          <div class="p-4">
            <p class="text-sm font-bold text-navy-900"><?= e($b['title']) ?></p>
            <p class="text-xs text-gray-500 mb-3"><?= e($b['subtitle']) ?></p>
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-semibold px-2 py-1 rounded-full <?= $b['status'] === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' ?>"><?= $b['status'] === 'active' ? 'Aktif' : 'Nonaktif' ?></span>
              <div class="flex gap-2 text-xs">
                <a href="banners.php?edit=<?= (int) $b['id'] ?>" class="text-cyan-600 hover:underline">Edit</a>
                <form method="POST"><?= csrfField() ?><input type="hidden" name="form_action" value="toggle_status"><input type="hidden" name="banner_id" value="<?= (int) $b['id'] ?>"><button type="submit" class="text-gray-500 hover:underline"><?= $b['status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?></button></form>
                <form method="POST" onsubmit="return confirm('Hapus banner ini?');"><?= csrfField() ?><input type="hidden" name="form_action" value="delete"><input type="hidden" name="banner_id" value="<?= (int) $b['id'] ?>"><button type="submit" class="text-red-500 hover:underline">Hapus</button></form>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($banners)): ?><p class="text-sm text-gray-400 col-span-2 text-center py-10">Belum ada banner.</p><?php endif; ?>
      </div>
    </main>
  </div>
</div>
</body>
</html>