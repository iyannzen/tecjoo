<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/upload_helpers.php';
require_once __DIR__ . '/../includes/helpers.php';

$admin = currentAdmin();
$pdo   = getDbConnection();
$errors = [];
$product = ['status' => 'active'];
$specsText = $featuresText = $applicationsText = $compatText = $packageText = '';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? null);

    $product = [
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

    if ($product['name'] === '' || mb_strlen($product['name']) < 3) $errors[] = 'Nama produk minimal 3 karakter.';
    if ($product['sku'] === '') $errors[] = 'SKU wajib diisi.';
    if ($product['category_id'] <= 0) $errors[] = 'Kategori wajib dipilih.';
    if ($product['price'] <= 0) $errors[] = 'Harga harus lebih dari 0.';
    if ($product['discount_price'] !== null && $product['discount_price'] >= $product['price']) $errors[] = 'Harga diskon harus lebih kecil dari harga normal.';

    if (empty($errors)) {
        $skuCheck = $pdo->prepare('SELECT id FROM products WHERE sku = :sku LIMIT 1');
        $skuCheck->execute([':sku' => $product['sku']]);
        if ($skuCheck->fetch()) $errors[] = 'SKU sudah digunakan produk lain.';
    }

    $mainImageFilename = null;
    if (empty($errors)) {
        try {
            $mainImageFilename = handleImageUpload($_FILES['main_image'] ?? [], 'products');
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (empty($errors)) {
        $slug = makeSlug($product['name']);
        $slugCheck = $pdo->prepare('SELECT id FROM products WHERE slug = :slug LIMIT 1');
        $slugCheck->execute([':slug' => $slug]);
        if ($slugCheck->fetch()) $slug .= '-' . substr(md5(uniqid('', true)), 0, 5);

        $stmt = $pdo->prepare(
            'INSERT INTO products (category_id, sku, name, slug, brand, short_description, description, specifications,
                features, applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
                main_image, status, featured, rating, review_count, created_at, updated_at)
             VALUES (:cat, :sku, :name, :slug, :brand, :short, :desc, :specs, :features, :apps, :compat, :package,
                :price, :discount, :stock, :weight, :dims, :image, :status, :featured, 0, 0, NOW(), NOW())'
        );
        $stmt->execute([
            ':cat' => $product['category_id'], ':sku' => $product['sku'], ':name' => $product['name'], ':slug' => $slug,
            ':brand' => $product['brand'], ':short' => $product['short_description'], ':desc' => $product['description'],
            ':specs' => json_encode(specsTextToArray($specsText), JSON_UNESCAPED_UNICODE),
            ':features' => json_encode(linesToArray($featuresText), JSON_UNESCAPED_UNICODE),
            ':apps' => json_encode(linesToArray($applicationsText), JSON_UNESCAPED_UNICODE),
            ':compat' => json_encode(linesToArray($compatText), JSON_UNESCAPED_UNICODE),
            ':package' => json_encode(linesToArray($packageText), JSON_UNESCAPED_UNICODE),
            ':price' => $product['price'], ':discount' => $product['discount_price'], ':stock' => $product['stock'],
            ':weight' => $product['weight'], ':dims' => $product['dimensions'], ':image' => 'uploads/products/' . $mainImageFilename,
            ':status' => $product['status'], ':featured' => $product['featured'],
        ]);

        $newId = (int) $pdo->lastInsertId();
        logActivity($admin['id'], 'create_product', 'Menambahkan produk: ' . $product['name'] . ' (SKU: ' . $product['sku'] . ')');
        setFlashMessage('success', 'Produk berhasil ditambahkan.');
        redirectTo('admin/product-edit.php?id=' . $newId);
    }
}

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();
$formTitle  = 'Tambah Produk Baru';

$pageTitle = 'Tambah Produk | TechZone Admin';
require __DIR__ . '/../includes/header.php';
?>
<div class="min-h-screen flex bg-gray-50">
  <?php require __DIR__ . '/_sidebar.php'; ?>
  <div id="techzone-admin-content" class="flex-1">
    <?php require __DIR__ . '/_topbar.php'; ?>
    <main class="p-6">
      <h1 class="reveal text-2xl font-extrabold text-navy-900 mb-6">Tambah Produk</h1>
      <?php require __DIR__ . '/_product_form.php'; ?>
    </main>
  </div>
</div>
</body>
</html>