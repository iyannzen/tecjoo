<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Kontak | TechZone';
$pageDescription = 'Hubungi TechZone untuk pertanyaan seputar produk IoT, pesanan, maupun kerja sama.';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';

$storeEmail    = getSetting('store_email', 'admin@techzone.local');
$storeWhatsapp = getSetting('store_whatsapp', '081200000000');
$storeAddress  = getSetting('store_address', 'Jl. Teknologi No. 1, Tangerang Selatan, Banten');
$storeHours    = getSetting('store_hours', 'Senin - Sabtu, 09.00 - 17.00 WIB');

renderPageHero('Hubungi Kami', 'Ada pertanyaan seputar produk atau pesanan? Tim TechZone siap membantu.', 'fa-headset');
?>
<main class="max-w-5xl mx-auto px-4 py-14">
  <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
    <div class="reveal space-y-4">
      <div class="group flex items-start gap-4 bg-white border border-gray-100 rounded-xl p-4 shadow-card card-hover">
        <span class="w-11 h-11 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0 transition-transform duration-300 group-hover:scale-110 group-hover:bg-cyan-500 group-hover:text-white"><i class="fa-solid fa-envelope"></i></span>
        <div>
          <p class="text-sm font-semibold text-navy-900">Email</p>
          <a href="mailto:<?= e($storeEmail) ?>" class="text-sm text-gray-500 hover:text-cyan-600 transition-colors"><?= e($storeEmail) ?></a>
        </div>
      </div>
      <div class="group flex items-start gap-4 bg-white border border-gray-100 rounded-xl p-4 shadow-card card-hover">
        <span class="w-11 h-11 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0 transition-transform duration-300 group-hover:scale-110 group-hover:bg-cyan-500 group-hover:text-white"><i class="fa-brands fa-whatsapp"></i></span>
        <div>
          <p class="text-sm font-semibold text-navy-900">WhatsApp</p>
          <p class="text-sm text-gray-500"><?= e($storeWhatsapp) ?></p>
        </div>
      </div>
      <div class="group flex items-start gap-4 bg-white border border-gray-100 rounded-xl p-4 shadow-card card-hover">
        <span class="w-11 h-11 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0 transition-transform duration-300 group-hover:scale-110 group-hover:bg-cyan-500 group-hover:text-white"><i class="fa-solid fa-location-dot"></i></span>
        <div>
          <p class="text-sm font-semibold text-navy-900">Alamat</p>
          <p class="text-sm text-gray-500"><?= e($storeAddress) ?></p>
        </div>
      </div>
      <div class="group flex items-start gap-4 bg-white border border-gray-100 rounded-xl p-4 shadow-card card-hover">
        <span class="w-11 h-11 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0 transition-transform duration-300 group-hover:scale-110 group-hover:bg-cyan-500 group-hover:text-white"><i class="fa-solid fa-clock"></i></span>
        <div>
          <p class="text-sm font-semibold text-navy-900">Jam Operasional</p>
          <p class="text-sm text-gray-500"><?= e($storeHours) ?></p>
        </div>
      </div>
    </div>

    <form action="mailto:<?= e($storeEmail) ?>" method="POST" enctype="text/plain" class="reveal delay-100 bg-white border border-gray-100 rounded-xl p-6 space-y-4 shadow-card transition-shadow hover:shadow-lg">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
        <input type="text" name="nama" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
        <input type="email" name="email" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Pesan</label>
        <textarea name="pesan" rows="4" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200"></textarea>
      </div>
      <button type="submit" class="w-full ripple-wrap btn-glow bg-cyan-500 hover:bg-cyan-400 text-navy-900 font-semibold py-2.5 rounded-lg transition">
        Kirim Pesan
      </button>
      <p class="text-xs text-gray-400">Pesan akan dikirim melalui aplikasi email default perangkat Anda.</p>
    </form>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
