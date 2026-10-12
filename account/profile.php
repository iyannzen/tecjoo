<?php
/**
 * TechZone - Profil & Pengaturan Akun User
 * CRUD data diri: edit profil (nama, email, no HP), ubah password,
 * dan nonaktifkan akun sendiri.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';

requireUserLogin();
$user   = currentUser();
$pdo    = getDbConnection();
$userId = (int) $user['id'];

$profileErrors  = [];
$passwordErrors = [];
$deleteErrors   = [];

// ---------------------------------------------------------------------
// Update profil (nama, email, no HP)
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'update_profile') {
    verifyCsrfToken($_POST['csrf_token'] ?? null);

    $name  = trim($_POST['name'] ?? '');
    $email = trim(strtolower($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');

    if ($name === '' || mb_strlen($name) < 3) {
        $profileErrors[] = 'Nama lengkap minimal 3 karakter.';
    }
    if (!isValidEmail($email)) {
        $profileErrors[] = 'Format email tidak valid.';
    }
    if ($phone === '' || !preg_match('/^[0-9+\-\s]{8,20}$/', $phone)) {
        $profileErrors[] = 'Nomor HP tidak valid.';
    }

    if (empty($profileErrors)) {
        $check = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id != :uid LIMIT 1');
        $check->execute([':email' => $email, ':uid' => $userId]);
        if ($check->fetch()) {
            $profileErrors[] = 'Email sudah digunakan oleh akun lain.';
        }
    }

    if (empty($profileErrors)) {
        $stmt = $pdo->prepare('UPDATE users SET name = :name, email = :email, phone = :phone, updated_at = NOW() WHERE id = :id');
        $stmt->execute([':name' => $name, ':email' => $email, ':phone' => $phone, ':id' => $userId]);
        $_SESSION['name'] = $name;
        logActivity($userId, 'update_profile', 'User memperbarui data profil.');
        setFlashMessage('success', 'Profil berhasil diperbarui.');
        redirectTo('account/profile.php');
    }

    // Data gagal disimpan: tampilkan ulang input yang baru diketik.
    $user = array_merge($user, ['name' => $name, 'email' => $email, 'phone' => $phone]);
}

// ---------------------------------------------------------------------
// Ubah password
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'update_password') {
    verifyCsrfToken($_POST['csrf_token'] ?? null);

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword     = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['new_password_confirmation'] ?? '';

    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $userId]);
    $row = $stmt->fetch();

    if (!$row || !password_verify($currentPassword, $row['password'])) {
        $passwordErrors[] = 'Password saat ini salah.';
    }
    if (!isPasswordStrongEnough($newPassword)) {
        $passwordErrors[] = 'Password baru minimal 8 karakter dan mengandung huruf serta angka.';
    }
    if ($newPassword !== $confirmPassword) {
        $passwordErrors[] = 'Konfirmasi password baru tidak sama.';
    }

    if (empty($passwordErrors)) {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE users SET password = :password, updated_at = NOW() WHERE id = :id')
            ->execute([':password' => $hashed, ':id' => $userId]);
        logActivity($userId, 'change_password', 'User mengubah password akun.');
        setFlashMessage('success', 'Password berhasil diubah.');
        redirectTo('account/profile.php');
    }
}

// ---------------------------------------------------------------------
// Nonaktifkan akun (soft-delete). Akun dengan riwayat pesanan tidak bisa
// dihapus permanen karena data pesanan harus tetap ada, jadi statusnya
// dinonaktifkan dan user otomatis logout.
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'deactivate_account') {
    verifyCsrfToken($_POST['csrf_token'] ?? null);

    $confirmPassword = $_POST['deactivate_password'] ?? '';
    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $userId]);
    $row = $stmt->fetch();

    if (!$row || !password_verify($confirmPassword, $row['password'])) {
        $deleteErrors[] = 'Password salah. Akun tidak dinonaktifkan.';
    } else {
        $pdo->prepare("UPDATE users SET status = 'inactive', updated_at = NOW() WHERE id = :id")->execute([':id' => $userId]);
        logActivity($userId, 'deactivate_account', 'User menonaktifkan akunnya sendiri.');
        logoutCurrentSession();
        techzoneStartSession();
        setFlashMessage('success', 'Akun Anda telah dinonaktifkan.');
        redirectTo('login.php');
    }
}

$pageTitle = 'Profil Saya | TechZone';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
  <h1 class="reveal text-xl font-extrabold text-navy-900 mb-1">Profil Saya</h1>
  <p class="text-sm text-gray-500 mb-6">Kelola data diri, password, dan status akun Anda.</p>

  <!-- Edit Profil -->
  <div class="bg-white border border-gray-100 rounded-xl p-6 mb-6 shadow-card transition-shadow hover:shadow-lg">
    <h2 class="text-sm font-bold text-navy-900 mb-4"><i class="fa-solid fa-user mr-2 text-cyan-500"></i>Data Diri</h2>

    <?php if (!empty($profileErrors)): ?>
      <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3 mb-4">
        <ul class="list-disc list-inside space-y-1"><?php foreach ($profileErrors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <?= csrfField() ?>
      <input type="hidden" name="form_action" value="update_profile">

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">Nama Lengkap</label>
        <input type="text" name="name" required value="<?= e($user['name']) ?>"
               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Email</label>
          <input type="email" name="email" required value="<?= e($user['email']) ?>"
                 class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Nomor HP</label>
          <input type="text" name="phone" required value="<?= e($user['phone']) ?>"
                 class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
        </div>
      </div>

      <button type="submit" class="ripple-wrap btn-glow bg-cyan-500 hover:bg-cyan-400 text-navy-900 font-semibold text-sm px-6 py-2.5 rounded-lg transition">
        Simpan Perubahan
      </button>
    </form>
  </div>

  <!-- Ubah Password -->
  <div class="bg-white border border-gray-100 rounded-xl p-6 mb-6 shadow-card transition-shadow hover:shadow-lg">
    <h2 class="text-sm font-bold text-navy-900 mb-4"><i class="fa-solid fa-key mr-2 text-cyan-500"></i>Ubah Password</h2>

    <?php if (!empty($passwordErrors)): ?>
      <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3 mb-4">
        <ul class="list-disc list-inside space-y-1"><?php foreach ($passwordErrors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <?= csrfField() ?>
      <input type="hidden" name="form_action" value="update_password">

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">Password Saat Ini</label>
        <input type="password" name="current_password" required
               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Password Baru</label>
          <input type="password" name="new_password" required
                 class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Konfirmasi Password Baru</label>
          <input type="password" name="new_password_confirmation" required
                 class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
        </div>
      </div>
      <p class="text-[11px] text-gray-400">Minimal 8 karakter, mengandung huruf dan angka.</p>

      <button type="submit" class="ripple-wrap btn-glow bg-cyan-500 hover:bg-cyan-400 text-navy-900 font-semibold text-sm px-6 py-2.5 rounded-lg transition">
        Ubah Password
      </button>
    </form>
  </div>

  <!-- Nonaktifkan Akun -->
  <div class="bg-white border border-red-100 rounded-xl p-6">
    <h2 class="text-sm font-bold text-red-600 mb-2"><i class="fa-solid fa-triangle-exclamation mr-2"></i>Nonaktifkan Akun</h2>
    <p class="text-xs text-gray-500 mb-4">
      Akun Anda akan dinonaktifkan dan Anda akan otomatis keluar. Riwayat pesanan tetap tersimpan sesuai kebijakan toko
      dan tidak dihapus permanen. Hubungi Kontak kami jika Anda ingin masuk kembali di kemudian hari.
    </p>

    <?php if (!empty($deleteErrors)): ?>
      <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3 mb-4">
        <ul class="list-disc list-inside space-y-1"><?php foreach ($deleteErrors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <form method="POST" class="flex flex-col sm:flex-row gap-3 sm:items-end"
          onsubmit="return confirm('Yakin ingin menonaktifkan akun Anda?');">
      <?= csrfField() ?>
      <input type="hidden" name="form_action" value="deactivate_account">
      <div class="flex-1">
        <label class="block text-xs font-medium text-gray-700 mb-1">Konfirmasi Password</label>
        <input type="password" name="deactivate_password" required
               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-400">
      </div>
      <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-semibold text-sm px-6 py-2.5 rounded-lg transition">
        Nonaktifkan Akun
      </button>
    </form>
  </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>