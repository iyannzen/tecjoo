<?php
/**
 * TechZone Admin - Kelola Review
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
    $reviewId   = (int) ($_POST['review_id'] ?? 0);

    $reviewStmt = $pdo->prepare('SELECT * FROM reviews WHERE id = :id LIMIT 1');
    $reviewStmt->execute([':id' => $reviewId]);
    $review = $reviewStmt->fetch();

    if ($review) {
        if ($formAction === 'toggle_status') {
            $newStatus = $review['status'] === 'visible' ? 'hidden' : 'visible';
            $pdo->prepare('UPDATE reviews SET status = :status, updated_at = NOW() WHERE id = :id')
                ->execute([':status' => $newStatus, ':id' => $reviewId]);
            recalcProductRating($pdo, (int) $review['product_id']);
            logActivity($admin['id'], 'toggle_review_status', 'Mengubah status review #' . $reviewId . ' menjadi ' . $newStatus);
            setFlashMessage('success', 'Status review berhasil diubah.');
        } elseif ($formAction === 'delete') {
            $pdo->prepare('DELETE FROM reviews WHERE id = :id')->execute([':id' => $reviewId]);
            recalcProductRating($pdo, (int) $review['product_id']);
            logActivity($admin['id'], 'delete_review', 'Menghapus review #' . $reviewId);
            setFlashMessage('success', 'Review berhasil dihapus.');
        }
    }
    redirectTo('admin/reviews.php');
}

$statusFilter = $_GET['status'] ?? '';
$where = [];
$params = [];
if (in_array($statusFilter, ['visible', 'hidden'], true)) {
    $where[] = 'r.status = :status';
    $params[':status'] = $statusFilter;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare(
    "SELECT r.*, p.name AS product_name, p.slug AS product_slug, u.name AS user_name
     FROM reviews r JOIN products p ON p.id = r.product_id JOIN users u ON u.id = r.user_id
     $whereSql ORDER BY r.created_at DESC LIMIT 100"
);
$stmt->execute($params);
$reviews = $stmt->fetchAll();

$pageTitle = 'Kelola Review | TechZone Admin';
require __DIR__ . '/../includes/header.php';
?>
<div class="min-h-screen flex bg-gray-50">
  <?php require __DIR__ . '/_sidebar.php'; ?>
  <div id="techzone-admin-content" class="flex-1">
    <?php require __DIR__ . '/_topbar.php'; ?>
    <main class="p-6">
      <h1 class="reveal text-2xl font-extrabold text-navy-900 mb-6">Kelola Review</h1>

      <div class="flex gap-2 mb-5">
        <a href="reviews.php" class="text-xs font-medium px-3 py-1.5 rounded-full <?= $statusFilter === '' ? 'bg-navy-900 text-white' : 'bg-white border border-gray-200 text-gray-600' ?>">Semua</a>
        <a href="reviews.php?status=visible" class="text-xs font-medium px-3 py-1.5 rounded-full <?= $statusFilter === 'visible' ? 'bg-navy-900 text-white' : 'bg-white border border-gray-200 text-gray-600' ?>">Tampil</a>
        <a href="reviews.php?status=hidden" class="text-xs font-medium px-3 py-1.5 rounded-full <?= $statusFilter === 'hidden' ? 'bg-navy-900 text-white' : 'bg-white border border-gray-200 text-gray-600' ?>">Disembunyikan</a>
      </div>

      <?php if (empty($reviews)): ?>
        <div class="bg-white border border-gray-100 rounded-xl p-12 text-center text-gray-500 text-sm shadow-card transition-shadow hover:shadow-lg">Belum ada review.</div>
      <?php else: ?>
        <div class="space-y-3">
          <?php foreach ($reviews as $r): ?>
          <div class="bg-white border border-gray-100 rounded-xl p-5 shadow-card transition-shadow hover:shadow-lg">
            <div class="flex items-start justify-between gap-4">
              <div>
                <a href="<?= e(techzoneBaseUrl()) ?>/product-detail.php?slug=<?= e($r['product_slug']) ?>" target="_blank" class="text-sm font-bold text-navy-900 hover:text-cyan-600"><?= e($r['product_name']) ?></a>
                <p class="text-xs text-gray-500 mt-0.5"><?= e($r['user_name']) ?> &middot; <?= e(formatTanggalIndo($r['created_at'])) ?></p>
                <?= renderStars((float) $r['rating']) ?>
                <p class="text-sm text-gray-600 mt-2"><?= e($r['review']) ?></p>
              </div>
              <div class="text-right shrink-0 space-y-2">
                <span class="block text-[11px] font-semibold px-2.5 py-1 rounded-full <?= $r['status'] === 'visible' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' ?>">
                  <?= $r['status'] === 'visible' ? 'Tampil' : 'Disembunyikan' ?>
                </span>
                <form method="POST"><?= csrfField() ?><input type="hidden" name="form_action" value="toggle_status"><input type="hidden" name="review_id" value="<?= (int) $r['id'] ?>">
                  <button type="submit" class="text-xs text-cyan-600 hover:underline"><?= $r['status'] === 'visible' ? 'Sembunyikan' : 'Tampilkan' ?></button>
                </form>
                <form method="POST" onsubmit="return confirm('Hapus review ini?');"><?= csrfField() ?><input type="hidden" name="form_action" value="delete"><input type="hidden" name="review_id" value="<?= (int) $r['id'] ?>">
                  <button type="submit" class="text-xs text-red-500 hover:underline">Hapus</button>
                </form>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </main>
  </div>
</div>
</body>
</html>