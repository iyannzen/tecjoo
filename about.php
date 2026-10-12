<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Tentang Kami | TechZone';
$pageDescription = 'Kenali TechZone, toko online yang fokus menyediakan perangkat IoT berkualitas untuk pelajar, hobiis, dan profesional di Indonesia.';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
renderPageHero('Tentang TechZone', 'Teknologi IoT untuk Dunia yang Lebih Terhubung', 'fa-microchip');
?>
<main class="max-w-4xl mx-auto px-4 py-14">
  <div class="reveal prose prose-sm max-w-none text-gray-600 space-y-4 leading-relaxed bg-white border border-gray-100 rounded-2xl p-6 sm:p-8 shadow-card">
    <p>
      TechZone adalah toko online yang berfokus khusus pada perangkat Internet of Things (IoT) — mulai dari
      mikrokontroler, sensor, modul komunikasi, hingga paket kit siap pakai untuk belajar dan membangun proyek
      teknologi. Kami hadir untuk menjadi mitra terpercaya bagi pelajar, mahasiswa, hobiis elektronika, maupun
      profesional yang membutuhkan komponen IoT berkualitas dengan harga wajar.
    </p>
    <p>
      Setiap produk yang kami jual dipilih dengan mempertimbangkan kualitas, kompatibilitas, dan relevansinya
      dengan kebutuhan proyek IoT masa kini — mulai dari smart home, automation, monitoring lingkungan, hingga
      robotika dan pengembangan prototipe produk.
    </p>
    <p>
      Kami percaya bahwa teknologi IoT seharusnya dapat diakses oleh siapa saja yang ingin belajar dan berinovasi.
      Karena itu, TechZone berkomitmen untuk terus melengkapi katalog produk, memberikan deskripsi teknis yang
      jelas pada setiap item, serta memberikan layanan pelanggan yang responsif.
    </p>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mt-12">
    <div class="reveal group bg-white border border-gray-100 rounded-xl p-6 text-center shadow-card card-hover">
      <div class="w-12 h-12 mx-auto rounded-xl bg-navy-900 group-hover:bg-gradient-to-br group-hover:from-cyan-400 group-hover:to-blue-600 flex items-center justify-center mb-3 transition-all duration-300 group-hover:rotate-6">
        <i class="fa-solid fa-microchip text-cyan-400 group-hover:text-white transition"></i>
      </div>
      <p class="font-bold text-navy-900">60+ Produk IoT</p>
      <p class="text-xs text-gray-500 mt-1">Dari mikrokontroler hingga kit lengkap</p>
    </div>
    <div class="reveal delay-100 group bg-white border border-gray-100 rounded-xl p-6 text-center shadow-card card-hover">
      <div class="w-12 h-12 mx-auto rounded-xl bg-navy-900 group-hover:bg-gradient-to-br group-hover:from-cyan-400 group-hover:to-blue-600 flex items-center justify-center mb-3 transition-all duration-300 group-hover:rotate-6">
        <i class="fa-solid fa-shield-halved text-cyan-400 group-hover:text-white transition"></i>
      </div>
      <p class="font-bold text-navy-900">Transaksi Aman</p>
      <p class="text-xs text-gray-500 mt-1">QRIS, Transfer Bank, dan COD</p>
    </div>
    <div class="reveal delay-200 group bg-white border border-gray-100 rounded-xl p-6 text-center shadow-card card-hover">
      <div class="w-12 h-12 mx-auto rounded-xl bg-navy-900 group-hover:bg-gradient-to-br group-hover:from-cyan-400 group-hover:to-blue-600 flex items-center justify-center mb-3 transition-all duration-300 group-hover:rotate-6">
        <i class="fa-solid fa-headset text-cyan-400 group-hover:text-white transition"></i>
      </div>
      <p class="font-bold text-navy-900">Dukungan Responsif</p>
      <p class="text-xs text-gray-500 mt-1">Siap membantu kebutuhan proyek Anda</p>
    </div>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
