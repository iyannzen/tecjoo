<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Kebijakan Pengembalian | TechZone';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';

renderPageHero('Kebijakan Pengembalian', 'Syarat dan prosedur pengembalian produk yang perlu Anda pahami.', 'fa-rotate-left');
?>
<main class="max-w-3xl mx-auto px-4 py-14">
  <div class="prose prose-sm max-w-none text-gray-600 space-y-4 leading-relaxed">
    <div class="reveal group bg-white border border-gray-100 rounded-xl p-5 shadow-card card-hover flex gap-4" style="transition-delay:0ms">
      <span class="w-10 h-10 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0 transition-all duration-300 group-hover:bg-cyan-500 group-hover:text-white group-hover:rotate-6"><i class="fa-solid fa-file-signature"></i></span>
      <div>
        <h2 class="text-navy-900 font-bold text-base mb-1">Syarat Pengembalian</h2>
        <p>Pengembalian produk dapat diajukan maksimal 3 hari setelah pesanan berstatus "Selesai", dengan kondisi produk belum digunakan, tidak rusak akibat kelalaian pengguna, dan kemasan asli masih lengkap.</p>
      </div>
    </div>
    <div class="reveal group bg-white border border-gray-100 rounded-xl p-5 shadow-card card-hover flex gap-4" style="transition-delay:60ms">
      <span class="w-10 h-10 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0 transition-all duration-300 group-hover:bg-cyan-500 group-hover:text-white group-hover:rotate-6"><i class="fa-solid fa-user-shield"></i></span>
      <div>
        <h2 class="text-navy-900 font-bold text-base mb-1">Produk yang Tidak Dapat Dikembalikan</h2>
        <p>Produk yang telah disolder, dimodifikasi, atau mengalami kerusakan fisik akibat kesalahan pemasangan/penggunaan tidak dapat dikembalikan.</p>
      </div>
    </div>
    <div class="reveal group bg-white border border-gray-100 rounded-xl p-5 shadow-card card-hover flex gap-4" style="transition-delay:120ms">
      <span class="w-10 h-10 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0 transition-all duration-300 group-hover:bg-cyan-500 group-hover:text-white group-hover:rotate-6"><i class="fa-solid fa-cart-shopping"></i></span>
      <div>
        <h2 class="text-navy-900 font-bold text-base mb-1">Proses Pengajuan</h2>
        <p>Hubungi tim TechZone melalui halaman Kontak dengan menyertakan nomor pesanan, foto produk, dan alasan pengembalian. Tim kami akan meninjau dan memberikan instruksi lebih lanjut.</p>
      </div>
    </div>
    <div class="reveal group bg-white border border-gray-100 rounded-xl p-5 shadow-card card-hover flex gap-4" style="transition-delay:180ms">
      <span class="w-10 h-10 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0 transition-all duration-300 group-hover:bg-cyan-500 group-hover:text-white group-hover:rotate-6"><i class="fa-solid fa-tags"></i></span>
      <div>
        <h2 class="text-navy-900 font-bold text-base mb-1">Pengembalian Dana</h2>
        <p>Setelah produk yang dikembalikan diterima dan diverifikasi, admin akan memperbarui status pesanan menjadi "Dikembalikan" dan proses pengembalian dana akan dikoordinasikan sesuai metode pembayaran awal.</p>
      </div>
    </div>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
