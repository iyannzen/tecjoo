<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Syarat & Ketentuan | TechZone';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';

renderPageHero('Syarat & Ketentuan', 'Ketentuan penggunaan layanan TechZone yang perlu Anda ketahui.', 'fa-file-signature');
?>
<main class="max-w-3xl mx-auto px-4 py-14">
  <div class="prose prose-sm max-w-none text-gray-600 space-y-4 leading-relaxed">
    <p>Terakhir diperbarui: <?= e(formatTanggalIndo(date('Y-m-d'))) ?></p>

    <div class="reveal group bg-white border border-gray-100 rounded-xl p-5 shadow-card card-hover flex gap-4" style="transition-delay:0ms">
      <span class="w-10 h-10 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0 transition-all duration-300 group-hover:bg-cyan-500 group-hover:text-white group-hover:rotate-6"><i class="fa-solid fa-file-signature"></i></span>
      <div>
        <h2 class="text-navy-900 font-bold text-base mb-1">1. Penerimaan Ketentuan</h2>
        <p>Dengan mengakses dan menggunakan situs TechZone, Anda menyetujui untuk terikat dengan syarat dan ketentuan yang berlaku di bawah ini.</p>
      </div>
    </div>
    <div class="reveal group bg-white border border-gray-100 rounded-xl p-5 shadow-card card-hover flex gap-4" style="transition-delay:60ms">
      <span class="w-10 h-10 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0 transition-all duration-300 group-hover:bg-cyan-500 group-hover:text-white group-hover:rotate-6"><i class="fa-solid fa-user-shield"></i></span>
      <div>
        <h2 class="text-navy-900 font-bold text-base mb-1">2. Akun Pengguna</h2>
        <p>Anda bertanggung jawab menjaga kerahasiaan informasi akun dan kata sandi Anda. Segala aktivitas yang terjadi pada akun Anda menjadi tanggung jawab pemilik akun.</p>
      </div>
    </div>
    <div class="reveal group bg-white border border-gray-100 rounded-xl p-5 shadow-card card-hover flex gap-4" style="transition-delay:120ms">
      <span class="w-10 h-10 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0 transition-all duration-300 group-hover:bg-cyan-500 group-hover:text-white group-hover:rotate-6"><i class="fa-solid fa-cart-shopping"></i></span>
      <div>
        <h2 class="text-navy-900 font-bold text-base mb-1">3. Pemesanan &amp; Pembayaran</h2>
        <p>Setiap pesanan yang dibuat melalui situs ini akan diproses setelah pembayaran diverifikasi (untuk metode QRIS dan Transfer Bank) atau langsung diproses untuk metode COD sesuai ketersediaan layanan di wilayah Anda.</p>
      </div>
    </div>
    <div class="reveal group bg-white border border-gray-100 rounded-xl p-5 shadow-card card-hover flex gap-4" style="transition-delay:180ms">
      <span class="w-10 h-10 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0 transition-all duration-300 group-hover:bg-cyan-500 group-hover:text-white group-hover:rotate-6"><i class="fa-solid fa-tags"></i></span>
      <div>
        <h2 class="text-navy-900 font-bold text-base mb-1">4. Harga &amp; Ketersediaan Produk</h2>
        <p>Harga dan stok produk dapat berubah sewaktu-waktu tanpa pemberitahuan sebelumnya. TechZone berhak membatalkan pesanan apabila terjadi kesalahan harga atau produk sudah tidak tersedia.</p>
      </div>
    </div>
    <div class="reveal group bg-white border border-gray-100 rounded-xl p-5 shadow-card card-hover flex gap-4" style="transition-delay:240ms">
      <span class="w-10 h-10 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0 transition-all duration-300 group-hover:bg-cyan-500 group-hover:text-white group-hover:rotate-6"><i class="fa-solid fa-truck"></i></span>
      <div>
        <h2 class="text-navy-900 font-bold text-base mb-1">5. Pengiriman</h2>
        <p>Estimasi waktu pengiriman bergantung pada metode pengiriman yang dipilih dan lokasi tujuan. Selengkapnya dapat dilihat pada halaman Kebijakan Pengiriman.</p>
      </div>
    </div>
    <div class="reveal group bg-white border border-gray-100 rounded-xl p-5 shadow-card card-hover flex gap-4" style="transition-delay:300ms">
      <span class="w-10 h-10 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0 transition-all duration-300 group-hover:bg-cyan-500 group-hover:text-white group-hover:rotate-6"><i class="fa-solid fa-scale-balanced"></i></span>
      <div>
        <h2 class="text-navy-900 font-bold text-base mb-1">6. Pembatasan Tanggung Jawab</h2>
        <p>TechZone tidak bertanggung jawab atas kerusakan yang timbul akibat kesalahan perakitan atau penggunaan produk yang tidak sesuai dengan spesifikasi teknis yang tercantum.</p>
      </div>
    </div>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
