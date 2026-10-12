<?php
require_once __DIR__ . '/includes/functions.php';
http_response_code(404);
$pageTitle = 'Halaman Tidak Ditemukan | TechZone';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<main class="relative min-h-[75vh] flex items-center justify-center overflow-hidden bg-navy-900">
  <div class="absolute inset-0 grid-pattern opacity-30"></div>
  <div class="absolute -top-16 -left-16 w-72 h-72 bg-cyan-500/20 rounded-full blur-3xl animate-blob"></div>
  <div class="absolute bottom-0 right-0 w-72 h-72 bg-blue-600/20 rounded-full blur-3xl animate-blob" style="animation-delay:3s"></div>
  <div class="relative max-w-lg mx-auto px-4 py-24 text-center animate-fade-in-up">
    <p class="gradient-text text-8xl font-extrabold mb-2 animate-float">404</p>
    <h1 class="text-xl font-bold text-white mb-2">Halaman Tidak Ditemukan</h1>
    <p class="text-sm text-gray-400 mb-6">Halaman yang Anda cari mungkin sudah dipindahkan atau tidak tersedia.</p>
    <a href="<?= e(techzoneBaseUrl()) ?>/index.php"
       class="inline-block ripple-wrap btn-glow bg-cyan-500 hover:bg-cyan-400 text-navy-900 font-semibold px-6 py-2.5 rounded-full transition">
      <i class="fa-solid fa-house mr-1.5"></i>Kembali ke Beranda
    </a>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
