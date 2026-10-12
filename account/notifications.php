<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/notification_helpers.php';

requireUserLogin();
$userId = (int) $_SESSION['user_id'];
$pdo    = getDbConnection();

// Klik notifikasi (dari dropdown atau daftar): tandai dibaca lalu menuju pesanan/halaman yang dimaksud.
if (isset($_GET['open'])) {
    tzOpenNotification($pdo, $userId, (int) $_GET['open'], false, 'account/notifications.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? null);
    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'mark_read') {
        $id = (int) ($_POST['notification_id'] ?? 0);
        $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :uid')->execute([':id' => $id, ':uid' => $userId]);
    } elseif ($formAction === 'mark_all_read') {
        $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = :uid')->execute([':uid' => $userId]);
        setFlashMessage('success', 'Semua notifikasi ditandai sudah dibaca.');
    } elseif ($formAction === 'delete') {
        $id = (int) ($_POST['notification_id'] ?? 0);
        $pdo->prepare('DELETE FROM notifications WHERE id = :id AND user_id = :uid')->execute([':id' => $id, ':uid' => $userId]);
        setFlashMessage('success', 'Notifikasi dihapus.');
    } elseif ($formAction === 'delete_read') {
        $del = $pdo->prepare('DELETE FROM notifications WHERE user_id = :uid AND is_read = 1');
        $del->execute([':uid' => $userId]);
        setFlashMessage('success', $del->rowCount() . ' notifikasi yang sudah dibaca dihapus.');
    } elseif ($formAction === 'delete_all') {
        $del = $pdo->prepare('DELETE FROM notifications WHERE user_id = :uid');
        $del->execute([':uid' => $userId]);
        setFlashMessage('success', 'Semua notifikasi dihapus.');
    }
    $backPage = max(1, (int) ($_POST['page'] ?? 1));
    redirectTo('account/notifications.php' . ($backPage > 1 ? '?page=' . $backPage : ''));
}

$perPage = 20;
$cnt = $pdo->prepare('SELECT COUNT(*) AS total, SUM(is_read = 0) AS unread, SUM(is_read = 1) AS readc FROM notifications WHERE user_id = :uid');
$cnt->execute([':uid' => $userId]);
$counts      = $cnt->fetch() ?: [];
$totalNotif  = (int) ($counts['total'] ?? 0);
$unreadNotif = (int) ($counts['unread'] ?? 0);
$readNotif   = (int) ($counts['readc'] ?? 0);
$totalPages  = max(1, (int) ceil($totalNotif / $perPage));
$page        = min(max(1, (int) ($_GET['page'] ?? 1)), $totalPages);
$offset      = ($page - 1) * $perPage;

$stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC, id DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset);
$stmt->execute([':uid' => $userId]);
$notifications = $stmt->fetchAll();

$typeIcon = ['order' => 'fa-box', 'payment' => 'fa-money-check-dollar', 'cart' => 'fa-cart-shopping', 'stock' => 'fa-triangle-exclamation', 'info' => 'fa-circle-info'];

$pageTitle = 'Notifikasi | TechZone';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
  <div>
      <div class="flex items-center justify-between flex-wrap gap-3 mb-6">
        <div>
          <h1 class="reveal text-xl font-extrabold text-navy-900">Notifikasi</h1>
          <?php if ($totalNotif > 0): ?><p class="text-xs text-gray-500 mt-1"><?= $totalNotif ?> notifikasi &middot; <?= $unreadNotif ?> belum dibaca</p><?php endif; ?>
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
        <div class="bg-white border border-gray-100 rounded-xl p-12 text-center shadow-card transition-shadow hover:shadow-lg">
          <i class="fa-regular fa-bell text-4xl text-gray-200 mb-3 animate-float"></i>
          <p class="text-gray-500 text-sm">Belum ada notifikasi.</p>
        </div>
      <?php else: ?>
        <div class="space-y-2">
          <?php foreach ($notifications as $n): ?>
          <div class="bg-white border <?= $n['is_read'] ? 'border-gray-100' : 'border-cyan-200 bg-cyan-50/30' ?> rounded-xl p-4 flex items-start gap-3">
            <span class="w-9 h-9 rounded-lg bg-navy-50 text-navy-700 flex items-center justify-center shrink-0">
              <i class="fa-solid <?= e($typeIcon[$n['type']] ?? 'fa-bell') ?>"></i>
            </span>
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between gap-2">
                <?php $nLink = tzNotificationLink($n, false); ?>
                <p class="text-sm font-semibold text-navy-900">
                  <?php if ($nLink): ?><a href="?open=<?= (int) $n['id'] ?>" class="hover:text-cyan-600 hover:underline"><?= e($n['title']) ?></a><?php else: ?><?= e($n['title']) ?><?php endif; ?>
                </p>
                <span class="text-[10px] text-gray-400 shrink-0"><?= e(formatTanggalIndo($n['created_at'], true)) ?></span>
              </div>
              <p class="text-xs text-gray-500 mt-0.5"><?= e($n['message']) ?></p>
              <div class="flex items-center gap-4 mt-1.5">
                <?php if ($nLink): ?><a href="?open=<?= (int) $n['id'] ?>" class="text-[11px] text-cyan-600 font-semibold hover:underline"><?= $n['type'] === 'cart' ? 'Buka keranjang' : 'Lihat pesanan' ?> <i class="fa-solid fa-arrow-right text-[9px]"></i></a><?php endif; ?>
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
    </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>