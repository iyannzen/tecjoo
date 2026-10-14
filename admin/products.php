<?php
/**
 * TechZone Admin - Kelola Produk (Daftar)
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/product_helpers.php';

$admin = currentAdmin();
$pdo   = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? null);
    $formAction = $_POST['form_action'] ?? '';
    $productId  = (int) ($_POST['product_id'] ?? 0);

    if ($formAction === 'toggle_status') {
        $pdo->prepare("UPDATE products SET status = IF(status='active','inactive','active'), updated_at = NOW() WHERE id = :id")
            ->execute([':id' => $productId]);
        logActivity($admin['id'], 'toggle_product_status', 'Mengubah status produk #' . $productId);
        setFlashMessage('success', 'Status produk berhasil diubah.');
    } elseif ($formAction === 'toggle_featured') {
        $pdo->prepare("UPDATE products SET featured = IF(featured=1,0,1), updated_at = NOW() WHERE id = :id")
            ->execute([':id' => $productId]);
        setFlashMessage('success', 'Status unggulan produk berhasil diubah.');
    }
    redirectTo('admin/products.php' . (!empty($_GET) ? '?' . http_build_query($_GET) : ''));
}

$q            = trim($_GET['q'] ?? '');
$categoryId   = (int) ($_GET['category_id'] ?? 0);
$statusFilter = $_GET['status'] ?? '';
$page         = max(1, (int) ($_GET['page'] ?? 1));
$perPage      = 15;

$where  = [];
$params = [];
if ($q !== '') {
    $where[] = '(p.name LIKE :q OR p.sku LIKE :q2)';
    $params[':q'] = '%' . $q . '%';
    $params[':q2'] = '%' . $q . '%';
}
if ($categoryId > 0) {
    $where[] = 'p.category_id = :cat';
    $params[':cat'] = $categoryId;
}
if (in_array($statusFilter, ['active', 'inactive'], true)) {
    $where[] = 'p.status = :status';
    $params[':status'] = $statusFilter;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p $whereSql");
$countStmt->execute($params);
$total      = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$stmt = $pdo->prepare(
    "SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON c.id = p.category_id
     $whereSql ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset"
);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();

function adminProductsUrl(array $override = []): string
{
    $current = $_GET;
    foreach ($override as $k => $v) {
        if ($v === null) unset($current[$k]); else $current[$k] = $v;
    }
    return 'products.php?' . http_build_query($current);
}

$pageTitle = 'Kelola Produk | TechZone Admin';
require __DIR__ . '/../includes/header.php';
?>
<div class="min-h-screen flex bg-gray-50">
  <?php require __DIR__ . '/_sidebar.php'; ?>
  <div id="techzone-admin-content" class="flex-1">
    <?php require __DIR__ . '/_topbar.php'; ?>
    <main class="p-6">
      <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
          <h1 class="reveal text-2xl font-extrabold text-navy-900">Kelola Produk</h1>
          <p class="text-sm text-gray-500"><?= $total ?> produk total</p>
        </div>
        <a href="product-add.php" class="ripple-wrap btn-glow bg-cyan-500 hover:bg-cyan-400 text-navy-900 text-sm font-semibold px-4 py-2 rounded-lg transition"><i class="fa-solid fa-plus mr-1"></i>Tambah Produk</a>
      </div>

      <form method="GET" class="flex flex-wrap gap-2 mb-5">
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cari nama/SKU..." class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 flex-1 min-w-[180px]">
        <select name="category_id" class="text-sm border border-gray-300 rounded-lg px-3 py-2">
          <option value="0">Semua Kategori</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= $categoryId === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="status" class="text-sm border border-gray-300 rounded-lg px-3 py-2">
          <option value="">Semua Status</option>
          <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Aktif</option>
          <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
        </select>
        <button type="submit" class="bg-navy-900 text-white text-sm font-semibold px-4 py-2 rounded-lg hover:bg-navy-800">Cari</button>
      </form>

      <div class="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div class="overflow-x-auto tz-tablewrap">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
            <tr>
              <th class="text-left px-4 py-3">Produk</th>
              <th class="text-left px-4 py-3">Kategori</th>
              <th class="text-right px-4 py-3">Harga</th>
              <th class="text-center px-4 py-3">Stok</th>
              <th class="text-center px-4 py-3">Status</th>
              <th class="text-center px-4 py-3">Unggulan</th>
              <th class="text-center px-4 py-3">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <?php foreach ($products as $p): [$stockText, $stockClass] = stockLabel((int)$p['stock']); ?>
            <tr>
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <img src="<?= e(productImageUrl($p)) ?>" class="w-10 h-10 rounded-lg object-cover bg-gray-50" alt="">
                  <div>
                    <p class="font-medium text-navy-900 line-clamp-1 max-w-[220px]"><?= e($p['name']) ?></p>
                    <p class="text-xs text-gray-400">SKU: <?= e($p['sku']) ?></p>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3 text-gray-600"><?= e($p['category_name']) ?></td>
              <td class="px-4 py-3 text-right font-medium text-navy-900"><?= e(formatRupiah(effectivePrice($p))) ?></td>
              <td class="px-4 py-3 text-center">
                <span class="text-[11px] font-semibold px-2 py-1 rounded-full <?= $stockClass ?>"><?= (int) $p['stock'] ?></span>
              </td>
              <td class="px-4 py-3 text-center">
                <form method="POST"><?= csrfField() ?><input type="hidden" name="form_action" value="toggle_status"><input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                  <button type="submit" class="text-[11px] font-semibold px-2 py-1 rounded-full <?= $p['status'] === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' ?>">
                    <?= $p['status'] === 'active' ? 'Aktif' : 'Nonaktif' ?>
                  </button>
                </form>
              </td>
              <td class="px-4 py-3 text-center">
                <form method="POST"><?= csrfField() ?><input type="hidden" name="form_action" value="toggle_featured"><input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                  <button type="submit" class="text-base <?= $p['featured'] ? 'text-amber-400' : 'text-gray-300' ?>"><i class="fa-solid fa-star"></i></button>
                </form>
              </td>
              <td class="px-4 py-3 text-center">
                <a href="product-edit.php?id=<?= (int) $p['id'] ?>" class="text-cyan-600 hover:underline text-xs font-medium">Edit</a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        </div>
      </div>

      <?php if ($totalPages > 1): ?>
      <div class="flex items-center justify-center gap-1.5 mt-6">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <a href="<?= e(adminProductsUrl(['page' => $i])) ?>"
             class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium <?= $i === $page ? 'bg-cyan-500 text-navy-900' : 'border border-gray-200 text-gray-600 hover:bg-gray-50' ?>"><?= $i ?></a>
        <?php endfor; ?>
      </div>
      <?php endif; ?>
    </main>
  </div>
</div>
</body>
</html>