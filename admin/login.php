<?php
/**
 * TechZone Admin - Login
 * Catatan: file ini TIDAK meng-include admin_auth.php karena justru
 * halaman inilah yang menghasilkan session admin. requireAdminLogin()
 * hanya dipakai di halaman-halaman admin lain setelah login.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';

if (isAdminLoggedIn()) {
    redirectTo('admin/index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? null);

    $rateLimitKey = 'login_attempts_admin';

    if (!isLoginAttemptAllowed($rateLimitKey)) {
        $errors[] = 'Terlalu banyak percobaan login gagal. Silakan coba lagi dalam beberapa menit.';
    } else {
        $email    = trim(strtolower($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        $pdo  = getDbConnection();
        $stmt = $pdo->prepare("SELECT id, name, email, password, role, status FROM users WHERE email = :email AND role = 'admin' LIMIT 1");
        $stmt->execute([':email' => $email]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($password, $admin['password'])) {
            recordFailedLoginAttempt($rateLimitKey);
            logActivity(null, 'admin_login_failed', 'Percobaan login admin gagal untuk email: ' . $email);
            $errors[] = 'Email atau password admin salah.';
        } elseif ($admin['status'] !== 'active') {
            $errors[] = 'Akun admin ini sedang dinonaktifkan.';
        } else {
            resetLoginAttempts($rateLimitKey);
            loginAsUser($admin);
            logActivity((int) $admin['id'], 'admin_login', 'Admin berhasil login.');
            redirectTo('admin/index.php');
        }
    }
}

$pageTitle = 'Login Admin | TechZone';
$bodyClass = 'tz-auth-page';
require __DIR__ . '/../includes/header.php';
?>
<main class="tz-stage" style="min-height:100vh">
  <div class="tz-stage__grid grid-pattern"></div>
  <div class="tz-stage__beam"></div>
  <div class="tz-stage__orb tz-stage__orb--1"></div>
  <div class="tz-stage__orb tz-stage__orb--2"></div>
  <div class="tz-stage__orb tz-stage__orb--3"></div>
  <canvas class="tz-particles" data-particles></canvas>

  <div class="tz-auth tz-auth--single" data-tilt-card>
    <span class="tz-scanline"></span>
    <section class="tz-auth__panel">
      <div class="tz-auth__head text-center" style="margin-bottom:1.6rem">
        <span class="tz-vault__seal">
          <span class="tz-emblem__ring tz-emblem__ring--3"></span>
          <span class="tz-emblem__ring"></span>
          <span class="tz-emblem__ring tz-emblem__ring--2"></span>
          <span class="tz-emblem__dot" style="--r:48px"></span>
          <span class="tz-vault__core"><i class="fa-solid fa-shield-halved"></i></span>
        </span>
        <h1>Panel Admin <em class="gradient-text" style="font-style:italic">TechZone</em></h1>
        <p>Khusus untuk pengelola toko TechZone.</p>
        <span class="tz-secure"><b></b> Area Terbatas</span>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="tz-alert" role="alert">
          <i class="fa-solid fa-circle-exclamation"></i>
          <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>

      <form method="POST" class="tz-form" data-loading-form>
        <?= csrfField() ?>
        <div class="tz-field">
          <input class="tz-input" type="email" id="email" name="email" placeholder=" " required autocomplete="username">
          <label for="email">Email Admin</label>
          <i class="fa-regular fa-envelope tz-field__ico"></i><span class="tz-field__line"></span>
        </div>
        <div class="tz-field">
          <input class="tz-input" type="password" id="password" name="password" placeholder=" " required autocomplete="current-password">
          <label for="password">Password</label>
          <i class="fa-solid fa-lock tz-field__ico"></i>
          <button type="button" class="tz-field__eye" data-toggle-password="#password" aria-label="Tampilkan password"><i class="fa-regular fa-eye"></i></button>
          <span class="tz-field__line"></span>
        </div>
        <button type="submit" class="tz-submit ripple-wrap magnetic"><span>Masuk ke Panel Admin</span><i class="fa-solid fa-arrow-right-long"></i></button>
      </form>

      <p class="text-center"><a href="<?= e(techzoneBaseUrl()) ?>/index.php" class="tz-back"><i class="fa-solid fa-arrow-left"></i> Kembali ke Toko</a></p>
    </section>
  </div>
</main>
<script src="<?= e(techzoneBaseUrl()) ?>/assets/js/app.js"></script>
</body>
</html>
