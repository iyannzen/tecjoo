<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

if (isUserLoggedIn()) {
    redirectTo('index.php');
}

$errors = $_SESSION['register_errors'] ?? [];
$old    = $_SESSION['register_old'] ?? [];
unset($_SESSION['register_errors'], $_SESSION['register_old']);

$pageTitle       = 'Daftar Akun | TechZone';
$pageDescription = 'Buat akun TechZone untuk mulai berbelanja perangkat IoT.';
$bodyClass       = 'tz-auth-page';
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

  <div class="tz-auth" data-tilt-card>
    <section class="tz-auth__brand">
      <div class="tz-emblem">
        <span class="tz-emblem__ring tz-emblem__ring--3"></span>
        <span class="tz-emblem__ring"></span>
        <span class="tz-emblem__ring tz-emblem__ring--2"></span>
        <span class="tz-emblem__dot"></span><span class="tz-emblem__dot"></span><span class="tz-emblem__dot"></span>
        <span class="tz-emblem__core"><i class="fa-solid fa-user-plus"></i></span>
      </div>
      <h2 class="tz-auth__title">Gabung Bersama <em class="gradient-text">TechZone</em></h2>
      <p class="tz-auth__lead">Buat akun gratis dan mulai jelajahi ratusan perangkat IoT.</p>
      <ul class="tz-perks">
        <li><i class="fa-solid fa-circle-check"></i> Cek status pesanan real-time</li>
        <li><i class="fa-solid fa-circle-check"></i> Simpan wishlist &amp; alamat</li>
        <li><i class="fa-solid fa-circle-check"></i> Promo &amp; kupon eksklusif</li>
      </ul>
    </section>

    <section class="tz-auth__panel">
      <div class="tz-auth__head">
        <span class="eyebrow eyebrow--left">Keanggotaan Baru</span>
        <h1 class="mt-3">Buat Akun TechZone</h1>
        <p>Daftar untuk mulai belanja perangkat IoT favorit Anda.</p>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="tz-alert" role="alert">
          <i class="fa-solid fa-circle-exclamation"></i>
          <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>

      <form action="actions/register.php" method="POST" class="tz-form" data-loading-form>
        <?= csrfField() ?>

        <div class="tz-field">
          <input class="tz-input" type="text" id="name" name="name" placeholder=" " required value="<?= e($old['name'] ?? '') ?>" autocomplete="name">
          <label for="name">Nama Lengkap</label>
          <i class="fa-regular fa-user tz-field__ico"></i><span class="tz-field__line"></span>
        </div>

        <div class="tz-field">
          <input class="tz-input" type="email" id="email" name="email" placeholder=" " required value="<?= e($old['email'] ?? '') ?>" autocomplete="email">
          <label for="email">Alamat Email</label>
          <i class="fa-regular fa-envelope tz-field__ico"></i><span class="tz-field__line"></span>
        </div>

        <div class="tz-field">
          <input class="tz-input" type="text" id="phone" name="phone" placeholder=" " required value="<?= e($old['phone'] ?? '') ?>" autocomplete="tel" inputmode="tel">
          <label for="phone">Nomor HP (08xxxxxxxxxx)</label>
          <i class="fa-solid fa-mobile-screen tz-field__ico"></i><span class="tz-field__line"></span>
        </div>

        <div>
          <div class="tz-field">
            <input class="tz-input" type="password" id="password" name="password" placeholder=" " required minlength="8" autocomplete="new-password" data-strength="#pw-meter">
            <label for="password">Password</label>
            <i class="fa-solid fa-lock tz-field__ico"></i>
            <button type="button" class="tz-field__eye" data-toggle-password="#password" aria-label="Tampilkan password"><i class="fa-regular fa-eye"></i></button>
            <span class="tz-field__line"></span>
          </div>
          <div class="tz-meter" id="pw-meter" data-level="0"><i></i><i></i><i></i><i></i><span>&nbsp;</span></div>
          <p class="tz-hint">Minimal 8 karakter, kombinasi huruf dan angka.</p>
        </div>

        <div class="tz-field">
          <input class="tz-input" type="password" id="password_confirmation" name="password_confirmation" placeholder=" " required minlength="8" autocomplete="new-password">
          <label for="password_confirmation">Konfirmasi Password</label>
          <i class="fa-solid fa-shield-halved tz-field__ico"></i>
          <button type="button" class="tz-field__eye" data-toggle-password="#password_confirmation" aria-label="Tampilkan password"><i class="fa-regular fa-eye"></i></button>
          <span class="tz-field__line"></span>
        </div>

        <button type="submit" class="tz-submit ripple-wrap magnetic"><span>Daftar Sekarang</span><i class="fa-solid fa-arrow-right-long"></i></button>
      </form>

      <div class="tz-divider">atau</div>
      <p class="tz-alt">Sudah punya akun? <a href="login.php">Masuk di sini</a></p>
    </section>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
