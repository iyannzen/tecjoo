<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireUserLogin();
$user = currentUser();
$pdo  = getDbConnection();

function safeCountUser(PDO $pdo, string $sql, array $params): int
{
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

$totalOrders    = safeCountUser($pdo, 'SELECT COUNT(*) FROM orders WHERE user_id = :uid', [':uid' => $user['id']]);
$totalWishlist  = safeCountUser($pdo, 'SELECT COUNT(*) FROM wishlists WHERE user_id = :uid', [':uid' => $user['id']]);
$totalAddresses = safeCountUser($pdo, 'SELECT COUNT(*) FROM addresses WHERE user_id = :uid', [':uid' => $user['id']]);

$pageTitle = 'Dashboard Akun | TechZone';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
  <div>
      <!-- Greeting banner -->
      <div class="reveal relative bg-navy-900 rounded-2xl p-6 sm:p-8 mb-8 overflow-hidden">
        <div class="absolute inset-0 grid-pattern opacity-30"></div>
        <div class="absolute -top-10 -right-10 w-48 h-48 bg-cyan-500/20 rounded-full blur-3xl animate-blob"></div>
        <div class="relative flex items-center gap-4">
          <span class="hidden sm:flex w-14 h-14 rounded-2xl bg-gradient-to-br from-cyan-400 to-blue-600 items-center justify-center shadow-glow animate-float shrink-0">
            <i class="fa-solid fa-user-astronaut text-white text-xl"></i>
          </span>
          <div>
            <h1 class="text-xl font-extrabold text-white mb-1">Halo, <?= e($user['name']) ?> 👋</h1>
            <p class="text-sm text-gray-400">Selamat datang kembali di akun TechZone Anda.</p>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <a href="orders.php" class="reveal group bg-white border border-gray-100 rounded-xl p-5 card-hover shadow-card">
          <span class="w-11 h-11 rounded-xl bg-cyan-50 flex items-center justify-center mb-3 transition-all duration-300 group-hover:bg-gradient-to-br group-hover:from-cyan-400 group-hover:to-blue-600 group-hover:rotate-6">
            <i class="fa-solid fa-box text-cyan-500 group-hover:text-white transition"></i>
          </span>
          <p class="text-lg font-extrabold text-navy-900"><span data-count-to="<?= $totalOrders ?>">0</span></p>
          <p class="text-xs text-gray-500">Total Pesanan</p>
        </a>
        <a href="wishlist.php" class="reveal delay-100 group bg-white border border-gray-100 rounded-xl p-5 card-hover shadow-card">
          <span class="w-11 h-11 rounded-xl bg-red-50 flex items-center justify-center mb-3 transition-all duration-300 group-hover:bg-gradient-to-br group-hover:from-red-400 group-hover:to-pink-500 group-hover:rotate-6">
            <i class="fa-solid fa-heart text-red-500 group-hover:text-white transition"></i>
          </span>
          <p class="text-lg font-extrabold text-navy-900"><span data-count-to="<?= $totalWishlist ?>">0</span></p>
          <p class="text-xs text-gray-500">Produk Wishlist</p>
        </a>
        <a href="addresses.php" class="reveal delay-200 group bg-white border border-gray-100 rounded-xl p-5 card-hover shadow-card">
          <span class="w-11 h-11 rounded-xl bg-amber-50 flex items-center justify-center mb-3 transition-all duration-300 group-hover:bg-gradient-to-br group-hover:from-amber-400 group-hover:to-orange-500 group-hover:rotate-6">
            <i class="fa-solid fa-location-dot text-amber-500 group-hover:text-white transition"></i>
          </span>
          <p class="text-lg font-extrabold text-navy-900"><span data-count-to="<?= $totalAddresses ?>">0</span></p>
          <p class="text-xs text-gray-500">Alamat Tersimpan</p>
        </a>
      </div>

      <div class="reveal bg-white border border-gray-100 rounded-xl p-6 shadow-card transition-shadow hover:shadow-lg">
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-sm font-bold text-navy-900">Informasi Akun</h2>
          <a href="profile.php" class="text-xs font-semibold text-cyan-600 hover:underline"><i class="fa-solid fa-pen mr-1"></i>Edit Profil</a>
        </div>
        <dl class="text-sm space-y-2">
          <div class="flex justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">Nama</dt><dd class="font-medium text-navy-900"><?= e($user['name']) ?></dd></div>
          <div class="flex justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">Email</dt><dd class="font-medium text-navy-900"><?= e($user['email']) ?></dd></div>
          <div class="flex justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">Nomor HP</dt><dd class="font-medium text-navy-900"><?= e($user['phone']) ?></dd></div>
          <div class="flex justify-between"><dt class="text-gray-500">Bergabung Sejak</dt><dd class="font-medium text-navy-900"><?= e(formatTanggalIndo($user['created_at'])) ?></dd></div>
        </dl>
       </div>
    </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>