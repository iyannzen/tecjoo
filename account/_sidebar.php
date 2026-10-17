<?php
/**
 * TechZone - Sidebar Area Akun User
 * Include setelah requireUserLogin(). Menu yang halamannya belum tersedia
 * pada tahap ini ditandai "segera" agar tidak menghasilkan link rusak.
 */
$base       = techzoneBaseUrl();
$currentUri = basename($_SERVER['SCRIPT_NAME']);

require_once __DIR__ . '/../includes/notification_helpers.php';
$unreadNotif = 0;
try {
    $unreadNotif = getUnreadNotificationCount(getDbConnection(), (int) $_SESSION['user_id']);
} catch (Throwable $e) {
    $unreadNotif = 0;
}

require_once __DIR__ . '/../includes/chat_helpers.php';
$unreadChat = tzChatUnreadTotal(getDbConnection(), 'user', (int) $_SESSION['user_id']);

$accountMenu = [
    ['Dashboard', 'index.php', 'fa-gauge', true],
    ['Profil', 'profile.php', 'fa-user', false],
    ['Alamat', 'addresses.php', 'fa-location-dot', true],
    ['Pesanan Saya', 'orders.php', 'fa-box', true],
    ['Riwayat Transaksi', 'transactions.php', 'fa-receipt', true],
    ['Wishlist', 'wishlist.php', 'fa-heart', true],
    ['Notifikasi', 'notifications.php', 'fa-bell', true],
    ['Chat Admin', '../chat.php', 'fa-comments', true],
    ['Ubah Password', 'change-password.php', 'fa-key', false],
];
?>
<aside class="md:w-64 shrink-0">
  <nav class="bg-white border border-gray-100 rounded-xl p-3 space-y-1 md:sticky md:top-20 shadow-card transition-shadow hover:shadow-lg">
    <?php foreach ($accountMenu as [$label, $file, $icon, $available]): ?>
      <?php if ($available): ?>
        <a href="<?= e($base) ?>/<?= strpos($file, '../') === 0 ? e(substr($file, 3)) : 'account/' . e($file) ?>"
           class="relative flex items-center justify-between gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 group
           <?= $currentUri === $file ? 'bg-gradient-to-r from-cyan-50 to-cyan-50/40 text-cyan-700 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:pl-4' ?>">
          <?php if ($currentUri === $file): ?>
            <span class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-5 bg-cyan-500 rounded-r-full"></span>
          <?php endif; ?>
          <span><i class="fa-solid <?= $icon ?> w-4 <?= $currentUri === $file ? 'text-cyan-600' : 'text-gray-400 group-hover:text-cyan-500' ?> transition-colors"></i> <?= e($label) ?></span>
          <?php if ($file === '../chat.php' && $unreadChat > 0): ?>
            <span class="bg-red-500 text-white text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center animate-pulse-glow"><?= $unreadChat > 9 ? '9+' : $unreadChat ?></span>
          <?php endif; ?>
          <?php if ($file === 'notifications.php' && $unreadNotif > 0): ?>
            <span class="bg-red-500 text-white text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center animate-pulse-glow"><?= $unreadNotif > 9 ? '9+' : $unreadNotif ?></span>
          <?php endif; ?>
        </a>
      <?php else: ?>
        <span class="flex items-center justify-between gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-400 cursor-not-allowed">
          <span><i class="fa-solid <?= $icon ?> w-4"></i> <?= e($label) ?></span>
          <span class="text-[9px] bg-gray-100 text-gray-400 px-1.5 py-0.5 rounded">segera</span>
        </span>
      <?php endif; ?>
    <?php endforeach; ?>
    <hr class="my-1 border-gray-100">
    <a href="<?= e($base) ?>/logout.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-red-500 hover:bg-red-50 hover:pl-4 transition-all duration-200">
      <i class="fa-solid fa-right-from-bracket w-4"></i> Keluar
    </a>
  </nav>
</aside>
