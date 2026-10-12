<?php
/**
 * TechZone - Header Layout  (tema "Noir & Aurum")
 * Variabel opsional yang bisa di-set sebelum include file ini:
 *   $pageTitle       (string) judul halaman
 *   $pageDescription (string) meta description
 *   $bodyClass       (string) class tambahan untuk <body> (mis. 'tz-auth-page')
 */
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/automation_helpers.php';

// Tugas berkala ringan (batal otomatis pesanan kedaluwarsa, pengingat keranjang, cek stok).
// Berjalan paling sering 1x / 5 menit; tidak memperlambat halaman di antaranya.
try { runScheduledTasks(); } catch (Throwable $e) { error_log('[TechZone][Scheduler] ' . $e->getMessage()); }

$pageTitle       = $pageTitle ?? 'TechZone - Toko Perangkat IoT Terpercaya';
$pageDescription = $pageDescription ?? 'TechZone menyediakan berbagai perangkat IoT: mikrokontroler, sensor, modul komunikasi, smart home, hingga IoT kit lengkap.';
$bodyClass       = $bodyClass ?? '';
$flash           = getFlashMessage();
$isAdminArea     = strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') !== false;
?>
<!DOCTYPE html>
<html lang="id" class="<?= $isAdminArea ? 'tz-theme-admin' : 'tz-theme-store' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#0a0a0f">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($pageDescription) ?>">
<link rel="icon" type="image/svg+xml" href="<?= e(techzoneBaseUrl()) ?>/assets/images/favicon.svg">
<script>
  // Tema terang/gelap toko (disimpan di browser). Area admin selalu gelap.
  try { var tzT = localStorage.getItem('tz_theme'); if (tzT === 'dark' && !document.documentElement.classList.contains('tz-theme-admin')) document.documentElement.setAttribute('data-theme', 'dark'); } catch (e) {}
</script>
<script>
  // Intro hanya tampil sekali per sesi browser (dicek sebelum render agar tidak berkedip).
  try { if (sessionStorage.getItem('tz_intro_seen')) document.documentElement.classList.add('tz-seen'); } catch (e) {}
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,500;0,600;0,700;0,800;1,500;1,600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<!-- Utilitas CSS statis (pengganti Tailwind CDN): tanpa kompilasi di browser, jauh lebih ringan -->
<link rel="stylesheet" href="<?= e(techzoneBaseUrl()) ?>/assets/css/tailwind.static.css?v=<?= @filemtime(__DIR__ . '/../assets/css/tailwind.static.css') ?: 1 ?>">
<link rel="stylesheet" href="<?= e(techzoneBaseUrl()) ?>/assets/css/app.css?v=<?= @filemtime(__DIR__ . '/../assets/css/app.css') ?: 1 ?>">
<noscript><style>#tz-loader{display:none!important}.reveal{opacity:1!important;transform:none!important}</style></noscript>
</head>
<body class="font-sans bg-gray-50 text-gray-800 antialiased tz-body <?= $isAdminArea ? 'tz-admin' : 'tz-store' ?> <?= e($bodyClass) ?>">

<!-- Intro mewah (sekali per sesi) -->
<div id="tz-loader" aria-hidden="true">
  <div class="tz-loader__curtain tz-loader__curtain--top"></div>
  <div class="tz-loader__curtain tz-loader__curtain--bottom"></div>
  <div class="tz-loader__core">
    <svg class="tz-loader__ring" viewBox="0 0 120 120" fill="none">
      <circle cx="60" cy="60" r="54" stroke="rgba(201,162,77,.18)" stroke-width="1"/>
      <circle class="tz-loader__arc" cx="60" cy="60" r="54" stroke="url(#tzg)" stroke-width="1.6" stroke-linecap="round"/>
      <defs><linearGradient id="tzg" x1="0" y1="0" x2="120" y2="120"><stop stop-color="#f6e7b4"/><stop offset="1" stop-color="#9c7625"/></linearGradient></defs>
    </svg>
    <span class="tz-loader__chip"><i class="fa-solid fa-microchip"></i></span>
    <p class="tz-loader__word"><span>T</span><span>E</span><span>C</span><span>H</span><span class="gap"></span><span>Z</span><span>O</span><span>N</span><span>E</span></p>
    <p class="tz-loader__sub">Premium IoT Boutique</p>
  </div>
</div>
<div class="tz-progress" id="tz-progress"></div>

<?php if ($flash):
    $fType = $flash['type'] === 'error' ? 'error' : ($flash['type'] === 'info' ? 'info' : 'success');
    $fIcon = $fType === 'error' ? 'fa-circle-exclamation' : ($fType === 'info' ? 'fa-circle-info' : 'fa-circle-check');
?>
<div id="techzone-toast" class="tz-toast tz-toast--<?= $fType ?>" role="status">
  <span class="tz-toast__icon"><i class="fa-solid <?= $fIcon ?>"></i></span>
  <p class="tz-toast__msg"><?= e($flash['message']) ?></p>
  <button type="button" class="tz-toast__close" aria-label="Tutup" onclick="this.parentNode.classList.add('is-out')"><i class="fa-solid fa-xmark"></i></button>
  <span class="tz-toast__bar"></span>
</div>
<script>
  setTimeout(function () {
    var t = document.getElementById('techzone-toast');
    if (t) { t.classList.add('is-out'); setTimeout(function(){ t.remove(); }, 500); }
  }, 4200);
</script>
<?php endif; ?>
