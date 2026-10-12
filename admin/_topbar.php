<?php
require_once __DIR__ . '/../includes/notification_helpers.php';
$admin = currentAdmin();
$tzAdminUnread = 0;
try { $tzAdminUnread = getUnreadNotificationCount(getDbConnection(), (int) $admin['id']); } catch (Throwable $e) {}
$tzNdItems   = getRecentNotifications(getDbConnection(), (int) $admin['id'], 6);
$tzNdUnread  = $tzAdminUnread;
$tzNdAdmin   = true;
$tzNdPageUrl = techzoneBaseUrl() . '/admin/notifications.php';
$flash = getFlashMessage();
$tzCrumb = trim(explode('|', $pageTitle ?? 'Admin')[0]);
?>
<header class="tz-admin-top">
  <div class="tz-admin-top__left">
    <button id="techzone-admin-sidebar-toggle" type="button" aria-label="Buka/tutup sidebar" class="tz-burger"><i class="fa-solid fa-bars-staggered"></i></button>
    <div class="tz-crumb">
      <small>TechZone Admin</small>
      <b><?= e($tzCrumb) ?></b>
    </div>
  </div>

  <div class="tz-admin-top__right">
    <div class="tz-clock" data-clock>
      <i class="fa-regular fa-clock" style="color:var(--gold-400)"></i>
      <div>
        <div class="tz-clock__time" data-clock-time>--:--:--</div>
        <div class="tz-clock__date" data-clock-date>&nbsp;</div>
      </div>
    </div>
    <?php include __DIR__ . '/../includes/notification_dropdown.php'; ?>
    <a href="<?= e(techzoneBaseUrl()) ?>/index.php" target="_blank" class="tz-pill-link hidden sm:inline-flex">
      <i class="fa-solid fa-arrow-up-right-from-square"></i> Lihat Toko
    </a>
    <div class="tz-user">
      <span class="tz-avatar"><?= e(strtoupper(substr($admin['name'] ?? 'A', 0, 1))) ?></span>
      <span class="n hidden sm:block"><?= e($admin['name'] ?? 'Admin') ?></span>
    </div>
    <a href="<?= e(techzoneBaseUrl()) ?>/logout.php?scope=admin" class="tz-logout" title="Keluar" aria-label="Keluar"><i class="fa-solid fa-right-from-bracket"></i></a>
  </div>
</header>

<?php if ($flash):
    $fType = $flash['type'] === 'error' ? 'error' : ($flash['type'] === 'info' ? 'info' : 'success');
    $fIcon = $fType === 'error' ? 'fa-circle-exclamation' : ($fType === 'info' ? 'fa-circle-info' : 'fa-circle-check');
?>
<div class="tz-admin-flash tz-admin-flash--<?= $fType ?>"><i class="fa-solid <?= $fIcon ?>"></i> <?= e($flash['message']) ?></div>
<?php endif; ?>

<script src="<?= e(techzoneBaseUrl()) ?>/assets/js/app.js"></script>
