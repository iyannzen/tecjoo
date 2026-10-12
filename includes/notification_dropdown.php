<?php
/**
 * Dropdown lonceng notifikasi (dipakai navbar toko & topbar admin).
 * Klik lonceng = buka; klik lagi / klik di luar / tombol Esc = tutup.
 * Klik sebuah notifikasi = tandai dibaca lalu menuju pesanan/halaman yang dimaksud.
 *
 * Variabel yang harus di-set sebelum include:
 *   $tzNdItems   array  notifikasi terbaru
 *   $tzNdUnread  int    jumlah belum dibaca
 *   $tzNdAdmin   bool   true bila untuk admin
 *   $tzNdPageUrl string URL halaman notifikasi lengkap
 */
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/notification_helpers.php';
?>
<div class="tz-notif" data-notif>
  <button type="button" class="tz-icon-btn" data-notif-toggle aria-haspopup="true" aria-expanded="false" aria-label="Notifikasi" title="Notifikasi">
    <i class="fa-regular fa-bell"></i>
    <span class="tz-badge" data-badge="notifications" <?= $tzNdUnread > 0 ? '' : 'hidden' ?>><?= $tzNdUnread > 99 ? '99+' : (int) $tzNdUnread ?></span>
  </button>

  <div class="tz-notif__panel" role="menu">
    <div class="tz-notif__head">
      <b>Notifikasi</b>
      <?php if ($tzNdUnread > 0): ?>
        <form method="POST" action="<?= e($tzNdPageUrl) ?>"><?= csrfField() ?><input type="hidden" name="form_action" value="mark_all_read"><button type="submit">Tandai semua dibaca</button></form>
      <?php endif; ?>
    </div>

    <div class="tz-notif__list">
      <?php if (empty($tzNdItems)): ?>
        <p class="tz-notif__empty"><i class="fa-regular fa-bell-slash"></i>Belum ada notifikasi.</p>
      <?php endif; ?>
      <?php foreach ($tzNdItems as $n): [$nIcon] = tzNotificationMeta((string) $n['type']); ?>
        <a href="<?= e($tzNdPageUrl . '?open=' . (int) $n['id']) ?>" class="tz-notif__item <?= $n['is_read'] ? '' : 'is-unread' ?>" role="menuitem">
          <span class="tz-notif__ico"><i class="fa-solid <?= e($nIcon) ?>"></i></span>
          <span class="tz-notif__txt">
            <b><?= e($n['title']) ?></b>
            <span><?= e(mb_strimwidth((string) $n['message'], 0, 110, '…')) ?></span>
            <time><?= e(formatTanggalIndo($n['created_at'], true)) ?></time>
          </span>
        </a>
      <?php endforeach; ?>
    </div>

    <a href="<?= e($tzNdPageUrl) ?>" class="tz-notif__all">Lihat semua notifikasi <i class="fa-solid fa-arrow-right"></i></a>
  </div>
</div>
