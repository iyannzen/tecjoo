<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

if (isUserLoggedIn()) {
    redirectTo('index.php');
}

$errors = $_SESSION['login_errors'] ?? [];
unset($_SESSION['login_errors']);

$pageTitle       = 'Masuk | TechZone';
$pageDescription = 'Masuk ke akun TechZone Anda untuk melanjutkan belanja.';
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
        <span class="tz-emblem__core"><i class="fa-solid fa-microchip"></i></span>
      </div>
      <h2 class="tz-auth__title">Selamat Datang <em class="gradient-text">Kembali</em></h2>
      <p class="tz-auth__lead">Masuk untuk melanjutkan belanja perangkat IoT favorit Anda.</p>
      <ul class="tz-perks">
        <li><i class="fa-solid fa-box"></i> Lacak pesanan secara real-time</li>
        <li><i class="fa-solid fa-heart"></i> Wishlist &amp; alamat tersimpan</li>
        <li><i class="fa-solid fa-ticket"></i> Promo &amp; kupon eksklusif</li>
      </ul>
    </section>

    <section class="tz-auth__panel">
      <div class="tz-auth__head">
        <span class="eyebrow eyebrow--left">Member Area</span>
        <h1 class="mt-3">Masuk ke TechZone</h1>
        <p>Silakan masukkan email dan password Anda.</p>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="tz-alert" role="alert">
          <i class="fa-solid fa-circle-exclamation"></i>
          <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>

      <form action="actions/login.php" method="POST" class="tz-form" data-loading-form>
        <?= csrfField() ?>

        <div class="tz-field">
          <input class="tz-input" type="email" id="email" name="email" placeholder=" " required autocomplete="email">
          <label for="email">Alamat Email</label>
          <i class="fa-regular fa-envelope tz-field__ico"></i>
          <span class="tz-field__line"></span>
        </div>

        <div class="tz-field">
          <input class="tz-input" type="password" id="password" name="password" placeholder=" " required autocomplete="current-password">
          <label for="password">Password</label>
          <i class="fa-solid fa-lock tz-field__ico"></i>
          <button type="button" class="tz-field__eye" data-toggle-password="#password" aria-label="Tampilkan password"><i class="fa-regular fa-eye"></i></button>
          <span class="tz-field__line"></span>
        </div>

        <div class="tz-row"><a href="forgot-password.php" class="tz-link">Lupa password?</a></div>

        <button type="submit" class="tz-submit ripple-wrap magnetic"><span>Masuk</span><i class="fa-solid fa-arrow-right-long"></i></button>
      </form>

      <div class="tz-divider">atau</div>
      <p class="tz-alt">Belum punya akun? <a href="register.php">Daftar sekarang</a></p>
    </section>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
