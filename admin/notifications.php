<?php
/**
 * TechZone Admin - Notifikasi
 * Pesanan baru, pembayaran, peringatan stok menipis/habis, dsb.
 * Mendukung: tandai dibaca, hapus satu, hapus yang sudah dibaca, hapus semua, paginasi.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/notification_helpers.php';

$admin   = currentAdmin();
$pdo     = getDbConnection();
$adminId = (int) $admin['id'];

// Klik notifikasi: tandai dibaca lalu menuju pesanan/halaman yang dimaksud.
if (isset($_GET['open'])) {
    tzOpenNotification($pdo, $adminId, (int) $_GET['open'], true, 'admin/notifications.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? null);
    $act = $_POST['form_action'] ?? '';

    if ($act === 'mark_read') {
        $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :uid')
            ->execute([':id' => (int) ($_POST['notification_id'] ?? 0), ':uid' => $adminId]);
    } elseif ($act === 'mark_all_read') {
        $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = :uid')->execute([':uid' => $adminId]);
        setFlashMessage('success', 'Semua notifikasi ditandai sudah dibaca.');
    } elseif ($act === 'delete') {
        $pdo->prepare('DELETE FROM notifications WHERE id = :id AND user_id = :uid')
            ->execute([':id' => (int) ($_POST['notification_id'] ?? 0), ':uid' => $adminId]);
        setFlashMessage('success', 'Notifikasi dihapus.');
    } elseif ($act === 'delete_read') {
        $del = $pdo->prepare('DELETE FROM notifications WHERE user_id = :uid AND is_read = 1');
        $del->execute([':uid' => $adminId]);
        setFlashMessage('success', $del->rowCount() . ' notifikasi yang sudah dibaca dihapus.');
    } elseif ($act === 'delete_all') {
        $pdo->prepare('DELETE FROM notifications WHERE user_id = :uid')->execute([':uid' => $adminId]);
        setFlashMessage('success', 'Semua notifikasi dihapus.');
    }
    $back = max(1, (int) ($_POST['page'] ?? 1));
    redirectTo('admin/notifications.php' . ($back > 1 ? '?page=' . $back : ''));
}

$perPage = 20;
$cnt = $pdo->prepare('SELECT COUNT(*) AS total, SUM(is_read = 0) AS unread, SUM(is_read = 1) AS readc FROM notifications WHERE user_id = :uid');
$cnt->execute([':uid' => $adminId]);
$counts      = $cnt->fetch() ?: [];
$totalNotif  = (int) ($counts['total'] ?? 0);
$unreadNotif = (int) ($counts['unread'] ?? 0);
$readNotif   = (int) ($counts['readc'] ?? 0);
$totalPages  = max(1, (int) ceil($totalNotif / $perPage));
$page        = min(max(1, (int) ($_GET['page'] ?? 1)), $totalPages);
$offset      = ($page - 1) * $perPage;

$stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC, id DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset);
$stmt->execute([':uid' => $adminId]);
$notifications = $stmt->fetchAll();

$pageTitle = 'Notifikasi | TechZone Admin';
require __DIR__ . '/../includes/header.php';
?>
<div class="min-h-screen flex bg-gray-50">
  <?php require __DIR__ . '/_sidebar.php'; ?>

  <div id="techzone-admin-content" class="flex-1">
    <?php require __DIR__ . '/_topbar.php'; ?>

    <main class="p-6 max-w-4xl">
      <div class="flex items-center justify-between flex-wrap gap-3 mb-6">
        <div>
          <h1 class="text-xl font-extrabold text-navy-900">Notifikasi</h1>
          <p class="text-xs text-gray-500 mt-1"><?= $totalNotif ?> notifikasi &middot; <?= $unreadNotif ?> belum dibaca</p>
        </div>
        <?php if ($totalNotif > 0): ?>
        <div class="flex items-center gap-2 flex-wrap">
          <?php if ($unreadNotif > 0): ?>
          <form method="POST"><?= csrfField() ?><input type="hidden" name="form_action" value="mark_all_read"><input type="hidden" name="page" value="<?= $page ?>">
            <button type="submit" class="text-xs text-cyan-600 font-semibold px-3 py-2 rounded-lg border border-gray-200 hover:bg-cyan-50 transition-colors"><i class="fa-solid fa-check-double mr-1"></i> Tandai Semua Dibaca</button>
          </form>
          <?php endif; ?>
          <?php if ($readNotif > 0): ?>
          <form method="POST" onsubmit="return confirm('Hapus semua notifikasi yang sudah dibaca (<?= $readNotif ?>)?');"><?= csrfField() ?><input type="hidden" name="form_action" value="delete_read">
            <button type="submit" class="text-xs text-gray-600 font-semibold px-3 py-2 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors"><i class="fa-regular fa-trash-can mr-1"></i> Hapus yang Sudah Dibaca</button>
          </form>
          <?php endif; ?>
          <form method="POST" onsubmit="return confirm('Hapus SEMUA notifikasi (<?= $totalNotif ?>)? Tindakan ini tidak bisa dibatalkan.');"><?= csrfField() ?><input type="hidden" name="form_action" value="delete_all">
            <button type="submit" class="text-xs text-red-600 font-semibold px-3 py-2 rounded-lg border border-red-200 hover:bg-red-50 transition-colors"><i class="fa-solid fa-trash mr-1"></i> Hapus Semua</button>
          </form>
        </div>
        <?php endif; ?>
      </div>

      <?php if (empty($notifications)): ?>
        <div class="bg-white border border-gray-100 rounded-2xl p-12 text-center shadow-card">
          <i class="fa-regular fa-bell text-3xl text-gray-300 mb-3"></i>
          <p class="text-sm text-gray-500">Belum ada notifikasi.</p>
        </div>
      <?php else: ?>
        <div class="bg-white border border-gray-100 rounded-2xl shadow-card divide-y divide-gray-50 overflow-hidden">
          <?php foreach ($notifications as $n):
              [$icon, $tone] = tzNotificationMeta((string) $n['type']); ?>
            <div class="flex gap-4 p-4 <?= $n['is_read'] ? '' : 'bg-cyan-50/60' ?>">
              <span class="w-10 h-10 shrink-0 rounded-xl flex items-center justify-center <?= $tone ?>"><i class="fa-solid <?= $icon ?>"></i></span>
              <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-3">
                  <?php $nLink = tzNotificationLink($n, true); ?>
                  <p class="text-sm font-semibold text-navy-900"><?php if ($nLink): ?><a href="?open=<?= (int) $n['id'] ?>" class="hover:text-cyan-600 hover:underline"><?= e($n['title']) ?></a><?php else: ?><?= e($n['title']) ?><?php endif; ?><?php if (!$n['is_read']): ?> <span class="inline-block w-2 h-2 rounded-full bg-cyan-500 ml-1 align-middle"></span><?php endif; ?></p>
                  <span class="text-[11px] text-gray-400 shrink-0"><?= e(formatTanggalIndo($n['created_at'], true)) ?></span>
                </div>
                <p class="text-sm text-gray-600 mt-0.5"><?= e($n['message']) ?></p>
                <div class="flex items-center gap-4 mt-1.5">
                  <?php if ($nLink): ?><a href="?open=<?= (int) $n['id'] ?>" class="text-[11px] text-cyan-600 font-semibold hover:underline"><?= e(tzNotificationLinkLabel($n)) ?> <i class="fa-solid fa-arrow-right text-[9px]"></i></a><?php endif; ?>
                  <?php if (!$n['is_read']): ?>
                  <form method="POST"><?= csrfField() ?><input type="hidden" name="form_action" value="mark_read"><input type="hidden" name="page" value="<?= $page ?>"><input type="hidden" name="notification_id" value="<?= (int) $n['id'] ?>">
                    <button type="submit" class="text-[11px] text-cyan-600 hover:underline">Tandai dibaca</button>
                  </form>
                  <?php endif; ?>
                  <form method="POST" onsubmit="return confirm('Hapus notifikasi ini?');"><?= csrfField() ?><input type="hidden" name="form_action" value="delete"><input type="hidden" name="page" value="<?= $page ?>"><input type="hidden" name="notification_id" value="<?= (int) $n['id'] ?>">
                    <button type="submit" class="text-[11px] text-red-500 hover:underline"><i class="fa-regular fa-trash-can mr-0.5"></i> Hapus</button>
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="flex items-center justify-center gap-3 mt-6 text-sm">
          <?php if ($page > 1): ?><a href="?page=<?= $page - 1 ?>" class="px-4 py-2 rounded-lg border border-gray-200 bg-white text-gray-700 hover:bg-cyan-50 transition-colors"><i class="fa-solid fa-chevron-left text-xs mr-1"></i> Sebelumnya</a><?php endif; ?>
          <span class="text-xs text-gray-500">Halaman <?= $page ?> dari <?= $totalPages ?></span>
          <?php if ($page < $totalPages): ?><a href="?page=<?= $page + 1 ?>" class="px-4 py-2 rounded-lg border border-gray-200 bg-white text-gray-700 hover:bg-cyan-50 transition-colors">Berikutnya <i class="fa-solid fa-chevron-right text-xs ml-1"></i></a><?php endif; ?>
        </div>
        <?php endif; ?>
      <?php endif; ?>
    </main>
  </div>
</div>
</body>
</html>
