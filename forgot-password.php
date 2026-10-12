<?php
/**
 * TechZone - Lupa Password
 *
 * Karena website ini tidak boleh menggunakan API eksternal (termasuk
 * layanan pengiriman email pihak ketiga), reset password dibuat sebagai
 * simulasi verifikasi mandiri: user membuktikan kepemilikan akun dengan
 * mencocokkan email + nomor HP yang terdaftar, lalu membuat password baru.
 * Seluruh proses tetap diproses PHP + MySQL, bukan sekadar tampilan.
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/helpers.php';

if (isUserLoggedIn()) {
    redirectTo('index.php');
}

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? null);

    $email       = trim(strtolower($_POST['email'] ?? ''));
    $phone       = trim($_POST['phone'] ?? '');
    $newPassword = $_POST['password'] ?? '';
    $confirm     = $_POST['password_confirmation'] ?? '';

    if (!isValidEmail($email) || $phone === '') {
        $errors[] = 'Email dan nomor HP wajib diisi dengan benar.';
    }
    if (!isPasswordStrongEnough($newPassword)) {
        $errors[] = 'Password baru minimal 8 karakter dan mengandung huruf serta angka.';
    }
    if ($newPassword !== $confirm) {
        $errors[] = 'Konfirmasi password baru tidak sama.';
    }

    if (empty($errors)) {
        $pdo  = getDbConnection();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND phone = :phone AND role = 'user' LIMIT 1");
        $stmt->execute([':email' => $email, ':phone' => $phone]);
        $user = $stmt->fetch();

        if (!$user) {
            $errors[] = 'Kombinasi email dan nomor HP tidak cocok dengan data akun manapun.';
        } else {
            $update = $pdo->prepare('UPDATE users SET password = :password, updated_at = NOW() WHERE id = :id');
            $update->execute([
                ':password' => password_hash($newPassword, PASSWORD_DEFAULT),
                ':id'       => $user['id'],
            ]);
            logActivity((int) $user['id'], 'reset_password', 'Password direset mandiri melalui halaman lupa password.');
            $success = true;
        }
    }
}

$pageTitle = 'Lupa Password | TechZone';
$bodyClass = 'tz-auth-page';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<main class="tz-stage">
  <div class="tz-stage__grid grid-pattern"></div>
  <div class="tz-stage__beam"></div>
  <div class="tz-stage__orb tz-stage__orb--1"></div>
  <div class="tz-stage__orb tz-stage__orb--2"></div>
  <div class="tz-stage__orb tz-stage__orb--3"></div>
  <canvas class="tz-particles" data-particles></canvas>

  <div class="tz-auth tz-auth--single" data-tilt-card>
    <section class="tz-auth__panel">
      <div class="tz-auth__head text-center">
        <span class="tz-vault__seal" style="width:84px;height:84px">
          <span class="tz-emblem__ring tz-emblem__ring--3"></span>
          <span class="tz-emblem__ring tz-emblem__ring--2"></span>
          <span class="tz-vault__core" style="inset:16px;border-radius:50%"><i class="fa-solid fa-key"></i></span>
        </span>
        <h1>Lupa Password</h1>
        <p>Verifikasi identitas Anda dengan email &amp; nomor HP terdaftar, lalu buat password baru.</p>
      </div>

      <?php if ($success): ?>
        <div class="tz-alert tz-alert--ok">
          <i class="fa-solid fa-circle-check"></i>
          <div>Password berhasil diperbarui. Silakan <a href="login.php" class="tz-link">masuk</a> dengan password baru Anda.</div>
        </div>
      <?php else: ?>
        <?php if (!empty($errors)): ?>
          <div class="tz-alert" role="alert">
            <i class="fa-solid fa-circle-exclamation"></i>
            <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
          </div>
        <?php endif; ?>

        <form method="POST" class="tz-form" data-loading-form>
          <?= csrfField() ?>
          <div class="tz-field">
            <input class="tz-input" type="email" id="email" name="email" placeholder=" " required autocomplete="email">
            <label for="email">Email Terdaftar</label>
            <i class="fa-regular fa-envelope tz-field__ico"></i><span class="tz-field__line"></span>
          </div>
          <div class="tz-field">
            <input class="tz-input" type="text" id="phone" name="phone" placeholder=" " required autocomplete="tel" inputmode="tel">
            <label for="phone">Nomor HP Terdaftar</label>
            <i class="fa-solid fa-mobile-screen tz-field__ico"></i><span class="tz-field__line"></span>
          </div>
          <div>
            <div class="tz-field">
              <input class="tz-input" type="password" id="password" name="password" placeholder=" " required minlength="8" autocomplete="new-password" data-strength="#pw-meter">
              <label for="password">Password Baru</label>
              <i class="fa-solid fa-lock tz-field__ico"></i>
              <button type="button" class="tz-field__eye" data-toggle-password="#password" aria-label="Tampilkan password"><i class="fa-regular fa-eye"></i></button>
              <span class="tz-field__line"></span>
            </div>
            <div class="tz-meter" id="pw-meter" data-level="0"><i></i><i></i><i></i><i></i><span>&nbsp;</span></div>
          </div>
          <div class="tz-field">
            <input class="tz-input" type="password" id="password_confirmation" name="password_confirmation" placeholder=" " required minlength="8" autocomplete="new-password">
            <label for="password_confirmation">Konfirmasi Password Baru</label>
            <i class="fa-solid fa-shield-halved tz-field__ico"></i>
            <button type="button" class="tz-field__eye" data-toggle-password="#password_confirmation" aria-label="Tampilkan password"><i class="fa-regular fa-eye"></i></button>
            <span class="tz-field__line"></span>
          </div>
          <button type="submit" class="tz-submit ripple-wrap magnetic"><span>Reset Password</span><i class="fa-solid fa-arrow-right-long"></i></button>
        </form>
      <?php endif; ?>

      <p class="tz-alt" style="margin-top:1.6rem"><a href="login.php"><i class="fa-solid fa-arrow-left text-xs mr-1"></i> Kembali ke halaman masuk</a></p>
    </section>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
