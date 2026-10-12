<?php
/**
 * TechZone Admin - Kelola Stok
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/product_helpers.php';
require_once __DIR__ . '/../includes/automation_helpers.php';

$admin = currentAdmin();
$pdo   = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'update_stock') {
    verifyCsrfToken($_POST['csrf_token'] ?? null);
    $productId = (int) ($_POST['product_id'] ?? 0);
    $newStock  = (int) ($_POST['stock'] ?? 0);

    if ($newStock < 0) {
        setFlashMessage('error', 'Stok tidak boleh bernilai negatif.');
    } else {
        $pdo->prepare('UPDATE products SET stock = :stock, updated_at = NOW() WHERE id = :id')
            ->execute([':stock' => $newStock, ':id' => $productId]);
        logActivity($admin['id'], 'update_stock', 'Mengubah stok produk #' . $productId . ' menjadi ' . $newStock);
        alertLowStock($pdo, $productId);
        setFlashMessage('success', 'Stok berhasil diperbarui.');
    }
    redirectTo('admin/inventory.php' . (!empty($_GET['filter']) ? '?filter=' . $_GET['filter'] : ''));
}

$filter = $_GET['filter'] ?? '';
$where  = [];
if ($filter === 'available') $where[] = 'stock > 10';
elseif ($filter === 'low') $where[] = 'stock BETWEEN 1 AND 10';
elseif ($filter === 'out') $where[] = 'stock = 0';
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$products = $pdo->query("SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON c.id=p.category_id $whereSql ORDER BY stock ASC, p.name ASC")->fetchAll();

$countAvailable = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE stock > 10')->fetchColumn();
$countLow       = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE stock BETWEEN 1 AND 10')->fetchColumn();
$countOut       = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE stock = 0')->fetchColumn();

$pageTitle = 'Kelola Stok | TechZone Admin';
require __DIR__ . '/../includes/header.php';
?>
<div class="min-h-screen flex bg-gray-50">
  <?php require __DIR__ . '/_sidebar.php'; ?>
  <div id="techzone-admin-content" class="flex-1">
    <?php require __DIR__ . '/_topbar.php'; ?>
    <main class="p-6">
      <h1 class="reveal text-2xl font-extrabold text-navy-900 mb-6">Kelola Stok</h1>

      <div class="grid grid-cols-3 gap-4 mb-6 max-w-xl">
        <a href="inventory.php?filter=available" class="bg-white border <?= $filter==='available'?'border-cyan-400':'border-gray-100' ?> rounded-xl p-4 text-center">
          <p class="text-lg font-extrabold text-emerald-600"><?= $countAvailable ?></p>
          <p class="text-xs text-gray-500">Tersedia (&gt;10)</p>
        </a>
        <a href="inventory.php?filter=low" class="bg-white border <?= $filter==='low'?'border-cyan-400':'border-gray-100' ?> rounded-xl p-4 text-center">
          <p class="text-lg font-extrabold text-amber-600"><?= $countLow ?></p>
          <p class="text-xs text-gray-500">Stok Menipis (1-10)</p>
        </a>
        <a href="inventory.php?filter=out" class="bg-white border <?= $filter==='out'?'border-cyan-400':'border-gray-100' ?> rounded-xl p-4 text-center">
          <p class="text-lg font-extrabold text-red-600"><?= $countOut ?></p>
          <p class="text-xs text-gray-500">Habis (0)</p>
        </a>
      </div>
      <?php if ($filter !== ''): ?>
        <a href="inventory.php" class="text-xs text-cyan-600 hover:underline mb-4 inline-block">&larr; Tampilkan semua produk</a>
      <?php endif; ?>

      <div class="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
            <tr>
              <th class="text-left px-4 py-3">Produk</th>
              <th class="text-left px-4 py-3">Kategori</th>
              <th class="text-center px-4 py-3">Stok Saat Ini</th>
              <th class="text-center px-4 py-3">Update Stok</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <?php foreach ($products as $p): [$stockText, $stockClass] = stockLabel((int) $p['stock']); ?>
            <tr>
              <td class="px-4 py-3">
                <p class="font-medium text-navy-900"><?= e($p['name']) ?></p>
                <p class="text-xs text-gray-400">SKU: <?= e($p['sku']) ?></p>
              </td>
              <td class="px-4 py-3 text-gray-600"><?= e($p['category_name']) ?></td>
              <td class="px-4 py-3 text-center">
                <span class="text-[11px] font-semibold px-2 py-1 rounded-full <?= $stockClass ?>"><?= (int) $p['stock'] ?> &middot; <?= $stockText ?></span>
              </td>
              <td class="px-4 py-3">
                <form method="POST" class="flex items-center justify-center gap-2">
                  <?= csrfField() ?>
                  <input type="hidden" name="form_action" value="update_stock">
                  <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                  <input type="number" name="stock" min="0" value="<?= (int) $p['stock'] ?>" class="w-20 text-center text-sm border border-gray-300 rounded-lg px-2 py-1.5">
                  <button type="submit" class="text-xs bg-navy-900 text-white font-semibold px-3 py-1.5 rounded-lg hover:bg-navy-800">Update</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</div>
</body>
</html>