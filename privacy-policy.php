<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Kebijakan Privasi | TechZone';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';

renderPageHero('Kebijakan Privasi', 'Bagaimana kami mengumpulkan, menggunakan, dan melindungi data Anda.', 'fa-user-shield');
?>
<main class="max-w-3xl mx-auto px-4 py-14">
  <div class="prose prose-sm max-w-none text-gray-600 space-y-4 leading-relaxed">
    <p>Terakhir diperbarui: <?= e(formatTanggalIndo(date('Y-m-d'))) ?></p>

    <div class="reveal group bg-white border border-gray-100 rounded-xl p-5 shadow-card card-hover flex gap-4" style="transition-delay:0ms">
      <span class="w-10 h-10 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0 transition-all duration-300 group-hover:bg-cyan-500 group-hover:text-white group-hover:rotate-6"><i class="fa-solid fa-file-signature"></i></span>
      <div>
        <h2 class="text-navy-900 font-bold text-base mb-1">1. Data yang Kami Kumpulkan</h2>
        <p>Kami mengumpulkan data yang Anda berikan secara langsung saat mendaftar akun dan bertransaksi, meliputi nama, email, nomor HP, dan alamat pengiriman.</p>
      </div>
    </div>
    <div class="reveal group bg-white border border-gray-100 rounded-xl p-5 shadow-card card-hover flex gap-4" style="transition-delay:60ms">
      <span class="w-10 h-10 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0 transition-all duration-300 group-hover:bg-cyan-500 group-hover:text-white group-hover:rotate-6"><i class="fa-solid fa-user-shield"></i></span>
      <div>
        <h2 class="text-navy-900 font-bold text-base mb-1">2. Penggunaan Data</h2>
        <p>Data Anda digunakan untuk memproses pesanan, verifikasi pembayaran, pengiriman produk, serta komunikasi terkait status transaksi Anda.</p>
      </div>
    </div>
    <div class="reveal group bg-white border border-gray-100 rounded-xl p-5 shadow-card card-hover flex gap-4" style="transition-delay:120ms">
      <span class="w-10 h-10 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0 transition-all duration-300 group-hover:bg-cyan-500 group-hover:text-white group-hover:rotate-6"><i class="fa-solid fa-cart-shopping"></i></span>
      <div>
        <h2 class="text-navy-900 font-bold text-base mb-1">3. Penyimpanan Data</h2>
        <p>Seluruh data disimpan dalam basis data internal TechZone dan tidak dibagikan ke pihak ketiga mana pun, termasuk layanan API eksternal, tanpa persetujuan Anda.</p>
      </div>
    </div>
    <div class="reveal group bg-white border border-gray-100 rounded-xl p-5 shadow-card card-hover flex gap-4" style="transition-delay:180ms">
      <span class="w-10 h-10 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0 transition-all duration-300 group-hover:bg-cyan-500 group-hover:text-white group-hover:rotate-6"><i class="fa-solid fa-tags"></i></span>
      <div>
        <h2 class="text-navy-900 font-bold text-base mb-1">4. Keamanan Password</h2>
        <p>Password Anda disimpan dalam bentuk hash satu arah dan tidak pernah dapat dilihat oleh siapa pun, termasuk oleh admin TechZone.</p>
      </div>
    </div>
    <div class="reveal group bg-white border border-gray-100 rounded-xl p-5 shadow-card card-hover flex gap-4" style="transition-delay:240ms">
      <span class="w-10 h-10 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0 transition-all duration-300 group-hover:bg-cyan-500 group-hover:text-white group-hover:rotate-6"><i class="fa-solid fa-truck"></i></span>
      <div>
        <h2 class="text-navy-900 font-bold text-base mb-1">5. Hak Anda</h2>
        <p>Anda berhak mengubah atau memperbarui data pribadi Anda kapan saja melalui halaman Profil pada akun Anda.</p>
      </div>
    </div>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
