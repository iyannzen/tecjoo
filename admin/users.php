<?php
/**
 * TechZone Admin - Kelola User
 * Admin TIDAK PERNAH dapat melihat password user (kolom password tidak pernah di-select).
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$admin = currentAdmin();
$pdo   = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'toggle_status') {
    verifyCsrfToken($_POST['csrf_token'] ?? null);
    $targetId = (int) ($_POST['user_id'] ?? 0);

    if ($targetId === (int) $admin['id']) {
        setFlashMessage('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
    } else {
        $checkStmt = $pdo->prepare("SELECT role FROM users WHERE id = :id LIMIT 1");
        $checkStmt->execute([':id' => $targetId]);
        $target = $checkStmt->fetch();

        if ($target && $target['role'] === 'admin') {
            setFlashMessage('error', 'Tidak dapat mengubah status akun admin lain melalui halaman ini.');
        } else {
            $pdo->prepare("UPDATE users SET status = IF(status='active','inactive','active'), updated_at = NOW() WHERE id = :id")
                ->execute([':id' => $targetId]);
            logActivity($admin['id'], 'toggle_user_status', 'Mengubah status user #' . $targetId);
            setFlashMessage('success', 'Status user berhasil diubah.');
        }
    }
    redirectTo('admin/users.php' . (!empty($_GET['q']) ? '?q=' . urlencode($_GET['q']) : ''));
}

$q = trim($_GET['q'] ?? '');
$where = ["role = 'user'"];
$params = [];
if ($q !== '') {
    $where[] = '(name LIKE :q OR email LIKE :q2 OR phone LIKE :q3)';
    $params[':q'] = '%' . $q . '%'; $params[':q2'] = '%' . $q . '%'; $params[':q3'] = '%' . $q . '%';
}
$whereSql = 'WHERE ' . implode(' AND ', $where);

$stmt = $pdo->prepare("SELECT id, name, email, phone, status, created_at FROM users $whereSql ORDER BY created_at DESC LIMIT 200");
$stmt->execute($params);
$users = $stmt->fetchAll();

// Hitung jumlah order per user sekaligus (efisien, 1 query).
$orderCounts = [];
if (!empty($users)) {
    $ids = array_column($users, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $countStmt = $pdo->prepare("SELECT user_id, COUNT(*) AS total FROM orders WHERE user_id IN ($placeholders) GROUP BY user_id");
    $countStmt->execute($ids);
    foreach ($countStmt->fetchAll() as $row) {
        $orderCounts[$row['user_id']] = (int) $row['total'];
    }
}

$pageTitle = 'Kelola User | TechZone Admin';
require __DIR__ . '/../includes/header.php';
?>
<div class="min-h-screen flex bg-gray-50">
  <?php require __DIR__ . '/_sidebar.php'; ?>
  <div id="techzone-admin-content" class="flex-1">
    <?php require __DIR__ . '/_topbar.php'; ?>
    <main class="p-6">
      <h1 class="reveal text-2xl font-extrabold text-navy-900 mb-1">Kelola User</h1>
      <p class="text-sm text-gray-500 mb-6"><?= count($users) ?> user terdaftar</p>

      <form method="GET" class="mb-5 max-w-md">
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cari nama, email, atau nomor HP..."
               class="w-full text-sm border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
      </form>

      <div class="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
            <tr>
              <th class="text-left px-4 py-3">Nama</th>
              <th class="text-left px-4 py-3">Kontak</th>
              <th class="text-center px-4 py-3">Pesanan</th>
              <th class="text-left px-4 py-3">Bergabung</th>
              <th class="text-center px-4 py-3">Status</th>
              <th class="text-center px-4 py-3">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <?php foreach ($users as $u): ?>
            <tr>
              <td class="px-4 py-3 font-medium text-navy-900"><?= e($u['name']) ?></td>
              <td class="px-4 py-3 text-gray-600">
                <p><?= e($u['email']) ?></p>
                <p class="text-xs text-gray-400"><?= e($u['phone']) ?></p>
              </td>
              <td class="px-4 py-3 text-center"><?= $orderCounts[$u['id']] ?? 0 ?></td>
              <td class="px-4 py-3 text-xs text-gray-500"><?= e(formatTanggalIndo($u['created_at'])) ?></td>
              <td class="px-4 py-3 text-center">
                <span class="text-[11px] font-semibold px-2 py-1 rounded-full <?= $u['status'] === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' ?>">
                  <?= $u['status'] === 'active' ? 'Aktif' : 'Nonaktif' ?>
                </span>
              </td>
              <td class="px-4 py-3 text-center">
                <form method="POST"><?= csrfField() ?><input type="hidden" name="form_action" value="toggle_status"><input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                  <button type="submit" class="text-xs text-cyan-600 hover:underline"><?= $u['status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($users)): ?>
              <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400 text-sm">Tidak ada user ditemukan.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <p class="text-xs text-gray-400 mt-3"><i class="fa-solid fa-shield-halved mr-1"></i>Password user tidak pernah ditampilkan ke admin, sesuai kebijakan keamanan.</p>
    </main>
  </div>
</div>
</body>
</html>