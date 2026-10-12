<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/upload_helpers.php';

$admin = currentAdmin();
$pdo   = getDbConnection();
$errors = [];

$productId = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $productId]);
$product = $stmt->fetch();

if (!$product) {
    setFlashMessage('error', 'Produk tidak ditemukan.');
    redirectTo('admin/products.php');
}

function linesToArray(string $text): array
{
    $lines = array_filter(array_map('trim', explode("\n", $text)), fn($l) => $l !== '');
    return array_values($lines);
}
function specsTextToArray(string $text): array
{
    $result = [];
    foreach (explode("\n", $text) as $line) {
        $line = trim($line);
        if ($line === '' || !str_contains($line, ':')) continue;
        [$key, $value] = array_map('trim', explode(':', $line, 2));
        if ($key !== '') $result[$key] = $value;
    }
    return $result;
}
function arrayToLines($jsonText): string
{
    $arr = json_decode($jsonText ?: '[]', true) ?: [];
    return implode("\n", $arr);
}
function specsArrayToLines($jsonText): string
{
    $arr = json_decode($jsonText ?: '{}', true) ?: [];
    $lines = [];
    foreach ($arr as $k => $v) $lines[] = $k . ': ' . $v;
    return implode("\n", $lines);
}

// ---------------------------------------------------------------------
// Proses hapus gambar galeri
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'delete_gallery_image') {
    verifyCsrfToken($_POST['csrf_token'] ?? null);
    $imgId = (int) ($_POST['image_id'] ?? 0);
    $imgStmt = $pdo->prepare('SELECT image FROM product_images WHERE id = :id AND product_id = :pid LIMIT 1');
    $imgStmt->execute([':id' => $imgId, ':pid' => $productId]);
    $img = $imgStmt->fetch();
    if ($img) {
        @unlink(__DIR__ . '/../uploads/products/' . $img['image']);
        $pdo->prepare('DELETE FROM product_images WHERE id = :id')->execute([':id' => $imgId]);
        setFlashMessage('success', 'Gambar galeri dihapus.');
    }
    redirectTo('admin/product-edit.php?id=' . $productId);
}

// ---------------------------------------------------------------------
// Proses tambah gambar galeri
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'add_gallery_image') {
    verifyCsrfToken($_POST['csrf_token'] ?? null);
    try {
        $filename = handleImageUpload($_FILES['gallery_image'], 'products');
        $sortStmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order),0)+1 FROM product_images WHERE product_id = :pid');
        $sortStmt->execute([':pid' => $productId]);
        $nextSort = (int) $sortStmt->fetchColumn();
        $pdo->prepare('INSERT INTO product_images (product_id, image, sort_order) VALUES (:pid, :img, :sort)')
            ->execute([':pid' => $productId, ':img' => 'uploads/products/' . $filename, ':sort' => $nextSort]);
        setFlashMessage('success', 'Gambar galeri berhasil ditambahkan.');
    } catch (RuntimeException $e) {
        setFlashMessage('error', $e->getMessage());
    }
    redirectTo('admin/product-edit.php?id=' . $productId);
}

// ---------------------------------------------------------------------
// Proses simpan (update) data utama produk
// ---------------------------------------------------------------------
$specsText = specsArrayToLines($product['specifications']);
$featuresText = arrayToLines($product['features']);
$applicationsText = arrayToLines($product['applications']);
$compatText = arrayToLines($product['compatibility']);
$packageText = arrayToLines($product['package_contents']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'save') {
    verifyCsrfToken($_POST['csrf_token'] ?? null);

    $input = [
        'name'              => trim($_POST['name'] ?? ''),
        'sku'               => trim($_POST['sku'] ?? ''),
        'category_id'       => (int) ($_POST['category_id'] ?? 0),
        'brand'             => trim($_POST['brand'] ?? ''),
        'short_description' => trim($_POST['short_description'] ?? ''),
        'description'       => trim($_POST['description'] ?? ''),
        'price'             => (float) ($_POST['price'] ?? 0),
        'discount_price'    => $_POST['discount_price'] !== '' ? (float) $_POST['discount_price'] : null,
        'stock'             => max(0, (int) ($_POST['stock'] ?? 0)),
        'weight'            => max(0, (int) ($_POST['weight'] ?? 0)),
        'dimensions'        => trim($_POST['dimensions'] ?? ''),
        'status'            => isset($_POST['status']) ? 'active' : 'inactive',
        'featured'          => isset($_POST['featured']) ? 1 : 0,
    ];
    $specsText        = $_POST['specifications_text'] ?? '';
    $featuresText     = $_POST['features_text'] ?? '';
    $applicationsText = $_POST['applications_text'] ?? '';
    $compatText       = $_POST['compatibility_text'] ?? '';
    $packageText      = $_POST['package_text'] ?? '';

    if ($input['name'] === '' || mb_strlen($input['name']) < 3) $errors[] = 'Nama produk minimal 3 karakter.';
    if ($input['sku'] === '') $errors[] = 'SKU wajib diisi.';
    if ($input['category_id'] <= 0) $errors[] = 'Kategori wajib dipilih.';
    if ($input['price'] <= 0) $errors[] = 'Harga harus lebih dari 0.';
    if ($input['discount_price'] !== null && $input['discount_price'] >= $input['price']) $errors[] = 'Harga diskon harus lebih kecil dari harga normal.';

    if (empty($errors)) {
        $skuCheck = $pdo->prepare('SELECT id FROM products WHERE sku = :sku AND id <> :id LIMIT 1');
        $skuCheck->execute([':sku' => $input['sku'], ':id' => $productId]);
        if ($skuCheck->fetch()) $errors[] = 'SKU sudah digunakan produk lain.';
    }

    $newMainImage = null;
    if (empty($errors) && !empty($_FILES['main_image']['name'])) {
        try {
            $newMainImage = handleImageUpload($_FILES['main_image'], 'products');
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (empty($errors)) {
        $params = [
            ':cat' => $input['category_id'], ':sku' => $input['sku'], ':name' => $input['name'],
            ':brand' => $input['brand'], ':short' => $input['short_description'], ':desc' => $input['description'],
            ':specs' => json_encode(specsTextToArray($specsText), JSON_UNESCAPED_UNICODE),
            ':features' => json_encode(linesToArray($featuresText), JSON_UNESCAPED_UNICODE),
            ':apps' => json_encode(linesToArray($applicationsText), JSON_UNESCAPED_UNICODE),
            ':compat' => json_encode(linesToArray($compatText), JSON_UNESCAPED_UNICODE),
            ':package' => json_encode(linesToArray($packageText), JSON_UNESCAPED_UNICODE),
            ':price' => $input['price'], ':discount' => $input['discount_price'], ':stock' => $input['stock'],
            ':weight' => $input['weight'], ':dims' => $input['dimensions'],
            ':status' => $input['status'], ':featured' => $input['featured'], ':id' => $productId,
        ];

        $imageSql = '';
        if ($newMainImage) {
            $imageSql = ', main_image = :image';
            $params[':image'] = 'uploads/products/' . $newMainImage;
        }

        $stmt = $pdo->prepare(
            "UPDATE products SET category_id=:cat, sku=:sku, name=:name, brand=:brand, short_description=:short,
                description=:desc, specifications=:specs, features=:features, applications=:apps,
                compatibility=:compat, package_contents=:package, price=:price, discount_price=:discount,
                stock=:stock, weight=:weight, dimensions=:dims, status=:status, featured=:featured,
                updated_at = NOW() $imageSql
             WHERE id = :id"
        );
        $stmt->execute($params);

        logActivity($admin['id'], 'update_product', 'Memperbarui produk: ' . $input['name'] . ' (ID: ' . $productId . ')');
        setFlashMessage('success', 'Produk berhasil diperbarui.');
        redirectTo('admin/product-edit.php?id=' . $productId);
    }

    $product = array_merge($product, $input);
}

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();

$galleryStmt = $pdo->prepare('SELECT * FROM product_images WHERE product_id = :id ORDER BY sort_order ASC');
$galleryStmt->execute([':id' => $productId]);
$gallery = $galleryStmt->fetchAll();

$formTitle = 'Edit Produk: ' . $product['name'];

$pageTitle = 'Edit Produk | TechZone Admin';
require __DIR__ . '/../includes/header.php';
?>
<div class="min-h-screen flex bg-gray-50">
  <?php require __DIR__ . '/_sidebar.php'; ?>
  <div id="techzone-admin-content" class="flex-1">
    <?php require __DIR__ . '/_topbar.php'; ?>
    <main class="p-6">
      <div class="flex items-center justify-between mb-6">
        <h1 class="reveal text-2xl font-extrabold text-navy-900">Edit Produk</h1>
        <a href="<?= e(techzoneBaseUrl()) ?>/product-detail.php?slug=<?= e($product['slug']) ?>" target="_blank" class="text-xs text-cyan-600 hover:underline">Lihat di Toko &rarr;</a>
      </div>

      <?php require __DIR__ . '/_product_form.php'; ?>

      <!-- GALERI GAMBAR TAMBAHAN -->
      <div class="bg-white border border-gray-100 rounded-xl p-6 max-w-4xl mt-6 shadow-card transition-shadow hover:shadow-lg">
        <h2 class="text-sm font-bold text-navy-900 mb-4">Galeri Gambar Tambahan</h2>
        <div class="grid grid-cols-4 sm:grid-cols-6 gap-3 mb-4">
          <?php foreach ($gallery as $img): ?>
            <div class="relative group">
              <img src="<?= e(techzoneBaseUrl() . '/' . ltrim($img['image'], '/')) ?>" alt="Galeri produk" class="w-full aspect-square object-cover rounded-lg border border-gray-100">
              <form method="POST" onsubmit="return confirm('Hapus gambar ini?');" class="absolute top-1 right-1">
                <?= csrfField() ?>
                <input type="hidden" name="form_action" value="delete_gallery_image">
                <input type="hidden" name="image_id" value="<?= (int) $img['id'] ?>">
                <button type="submit" class="w-6 h-6 bg-white/90 rounded-full text-red-500 text-xs shadow"><i class="fa-solid fa-xmark"></i></button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
        <form method="POST" enctype="multipart/form-data" class="flex items-center gap-2">
          <?= csrfField() ?>
          <input type="hidden" name="form_action" value="add_gallery_image">
          <input type="file" name="gallery_image" accept=".jpg,.jpeg,.png,.webp" required class="text-xs border border-gray-300 rounded-lg px-3 py-2 flex-1">
          <button type="submit" class="bg-navy-900 text-white text-xs font-semibold px-4 py-2 rounded-lg hover:bg-navy-800">Tambah Gambar</button>
        </form>
      </div>
    </main>
  </div>
</div>
</body>
</html>