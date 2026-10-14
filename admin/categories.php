<?php
/**
 * TechZone Admin - Kelola Kategori
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/upload_helpers.php';

$admin = currentAdmin();
$pdo   = getDbConnection();
$errors = [];
$editingCategory = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? null);
    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'toggle_status') {
        $id = (int) ($_POST['category_id'] ?? 0);
        $pdo->prepare("UPDATE categories SET status = IF(status='active','inactive','active'), updated_at = NOW() WHERE id = :id")
            ->execute([':id' => $id]);
        logActivity($admin['id'], 'toggle_category_status', 'Mengubah status kategori #' . $id);
        setFlashMessage('success', 'Status kategori berhasil diubah.');
        redirectTo('admin/categories.php');
    }

    if ($formAction === 'delete') {
        $id = (int) ($_POST['category_id'] ?? 0);
        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = :id');
        $countStmt->execute([':id' => $id]);
        if ((int) $countStmt->fetchColumn() > 0) {
            setFlashMessage('error', 'Kategori tidak dapat dihapus karena masih memiliki produk. Nonaktifkan saja jika perlu.');
        } else {
            $pdo->prepare('DELETE FROM categories WHERE id = :id')->execute([':id' => $id]);
            logActivity($admin['id'], 'delete_category', 'Menghapus kategori #' . $id);
            setFlashMessage('success', 'Kategori berhasil dihapus.');
        }
        redirectTo('admin/categories.php');
    }

    if ($formAction === 'save') {
        $categoryId  = (int) ($_POST['category_id'] ?? 0);
        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status      = ($_POST['status'] ?? 'active') === 'active' ? 'active' : 'inactive';

        if ($name === '' || mb_strlen($name) < 2) {
            $errors[] = 'Nama kategori minimal 2 karakter.';
        }

        $imageFilename = null;
        if (!empty($_FILES['image']['name'])) {
            try {
                $imageFilename = handleImageUpload($_FILES['image'], 'categories');
            } catch (RuntimeException $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (empty($errors)) {
            $slug = makeSlug($name);

            // Pastikan slug unik (kecuali untuk kategori yang sedang diedit).
            $slugCheck = $pdo->prepare('SELECT id FROM categories WHERE slug = :slug AND id <> :id LIMIT 1');
            $slugCheck->execute([':slug' => $slug, ':id' => $categoryId]);
            if ($slugCheck->fetch()) {
                $slug .= '-' . substr(md5(uniqid('', true)), 0, 4);
            }

            if ($categoryId > 0) {
                if ($imageFilename) {
                    $stmt = $pdo->prepare('UPDATE categories SET name=:name, slug=:slug, description=:desc, image=:image, status=:status, updated_at=NOW() WHERE id=:id');
                    $stmt->execute([':name' => $name, ':slug' => $slug, ':desc' => $description, ':image' => 'uploads/categories/' . $imageFilename, ':status' => $status, ':id' => $categoryId]);
                } else {
                    $stmt = $pdo->prepare('UPDATE categories SET name=:name, slug=:slug, description=:desc, status=:status, updated_at=NOW() WHERE id=:id');
                    $stmt->execute([':name' => $name, ':slug' => $slug, ':desc' => $description, ':status' => $status, ':id' => $categoryId]);
                }
                setFlashMessage('success', 'Kategori berhasil diperbarui.');
                logActivity($admin['id'], 'update_category', 'Memperbarui kategori: ' . $name);
            } else {
                $stmt = $pdo->prepare('INSERT INTO categories (name, slug, description, image, status, created_at, updated_at) VALUES (:name, :slug, :desc, :image, :status, NOW(), NOW())');
                $stmt->execute([':name' => $name, ':slug' => $slug, ':desc' => $description, ':image' => $imageFilename ? 'uploads/categories/' . $imageFilename : null, ':status' => $status]);
                setFlashMessage('success', 'Kategori baru berhasil ditambahkan.');
                logActivity($admin['id'], 'create_category', 'Menambahkan kategori: ' . $name);
            }
            redirectTo('admin/categories.php');
        }

        $editingCategory = ['id' => $categoryId, 'name' => $name, 'description' => $description, 'status' => $status];
    }
}

if (isset($_GET['edit']) && $editingCategory === null) {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => (int) $_GET['edit']]);
    $editingCategory = $stmt->fetch() ?: null;
}
$showForm = isset($_GET['add']) || $editingCategory !== null;

$categories = $pdo->query(
    "SELECT c.*, COUNT(p.id) AS total_produk FROM categories c
     LEFT JOIN products p ON p.category_id = c.id
     GROUP BY c.id ORDER BY c.name ASC"
)->fetchAll();

$pageTitle = 'Kelola Kategori | TechZone Admin';
require __DIR__ . '/../includes/header.php';
?>
<div class="min-h-screen flex bg-gray-50">
  <?php require __DIR__ . '/_sidebar.php'; ?>
  <div id="techzone-admin-content" class="flex-1">
    <?php require __DIR__ . '/_topbar.php'; ?>
    <main class="p-6">
      <div class="flex items-center justify-between mb-6">
        <h1 class="reveal text-2xl font-extrabold text-navy-900">Kelola Kategori</h1>
        <?php if (!$showForm): ?>
          <a href="categories.php?add=1" class="ripple-wrap btn-glow bg-cyan-500 hover:bg-cyan-400 text-navy-900 text-sm font-semibold px-4 py-2 rounded-lg transition"><i class="fa-solid fa-plus mr-1"></i>Tambah Kategori</a>
        <?php endif; ?>
      </div>

      <?php if ($showForm): ?>
      <div class="bg-white border border-gray-100 rounded-xl p-6 mb-6 max-w-xl shadow-card transition-shadow hover:shadow-lg">
        <h2 class="text-sm font-bold text-navy-900 mb-4"><?= $editingCategory['id'] ?? null ? 'Edit Kategori' : 'Tambah Kategori' ?></h2>
        <?php if (!empty($errors)): ?>
          <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3 mb-4">
            <ul class="list-disc list-inside space-y-1"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
          </div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
          <?= csrfField() ?>
          <input type="hidden" name="form_action" value="save">
          <input type="hidden" name="category_id" value="<?= (int) ($editingCategory['id'] ?? 0) ?>">
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Nama Kategori</label>
            <input type="text" name="name" required value="<?= e($editingCategory['name'] ?? '') ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Deskripsi</label>
            <textarea name="description" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200"><?= e($editingCategory['description'] ?? '') ?></textarea>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Gambar Kategori (opsional)</label>
            <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" class="w-full text-xs border border-gray-300 rounded-lg px-3 py-2">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
            <select name="status" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm">
              <option value="active" <?= ($editingCategory['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Aktif</option>
              <option value="inactive" <?= ($editingCategory['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
            </select>
          </div>
          <div class="flex gap-3">
            <button type="submit" class="ripple-wrap btn-glow bg-cyan-500 hover:bg-cyan-400 text-navy-900 font-semibold text-sm px-6 py-2.5 rounded-lg transition">Simpan</button>
            <a href="categories.php" class="text-gray-500 text-sm font-medium px-6 py-2.5 hover:text-navy-900">Batal</a>
          </div>
        </form>
      </div>
      <?php endif; ?>

      <div class="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div class="tz-tablewrap">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
            <tr>
              <th class="text-left px-4 py-3">Kategori</th>
              <th class="text-center px-4 py-3">Produk</th>
              <th class="text-center px-4 py-3">Status</th>
              <th class="text-center px-4 py-3">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <?php foreach ($categories as $cat): ?>
            <tr>
              <td class="px-4 py-3">
                <p class="font-medium text-navy-900"><?= e($cat['name']) ?></p>
                <p class="text-xs text-gray-400"><?= e($cat['slug']) ?></p>
              </td>
              <td class="px-4 py-3 text-center"><?= (int) $cat['total_produk'] ?></td>
              <td class="px-4 py-3 text-center">
                <span class="text-[11px] font-semibold px-2 py-1 rounded-full <?= $cat['status'] === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' ?>">
                  <?= $cat['status'] === 'active' ? 'Aktif' : 'Nonaktif' ?>
                </span>
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center justify-center gap-3 text-xs">
                  <a href="categories.php?edit=<?= (int) $cat['id'] ?>" class="text-cyan-600 hover:underline">Edit</a>
                  <form method="POST"><?= csrfField() ?><input type="hidden" name="form_action" value="toggle_status"><input type="hidden" name="category_id" value="<?= (int) $cat['id'] ?>">
                    <button type="submit" class="text-gray-500 hover:underline"><?= $cat['status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                  </form>
                  <form method="POST" onsubmit="return confirm('Hapus kategori ini?');"><?= csrfField() ?><input type="hidden" name="form_action" value="delete"><input type="hidden" name="category_id" value="<?= (int) $cat['id'] ?>">
                    <button type="submit" class="text-red-500 hover:underline">Hapus</button>
                  </form>
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
</body>
</html>