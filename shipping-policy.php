<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Kebijakan Pengiriman | TechZone';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';

$shipReguler = (int) getSetting('shipping_cost_reguler', '15000');
$shipExpress = (int) getSetting('shipping_cost_express', '30000');

renderPageHero('Kebijakan Pengiriman', 'Informasi biaya, estimasi, dan proses pengiriman pesanan Anda.', 'fa-truck-fast');
?>
<main class="max-w-3xl mx-auto px-4 py-14">

  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
    <div class="reveal group bg-white border border-gray-100 rounded-xl p-5 shadow-card card-hover relative overflow-hidden">
      <div class="absolute -right-4 -top-4 w-16 h-16 bg-cyan-50 rounded-full transition-transform duration-300 group-hover:scale-150"></div>
      <p class="relative text-sm font-bold text-navy-900 mb-1"><i class="fa-solid fa-truck text-cyan-500 mr-2"></i>Reguler</p>
      <p class="relative text-lg font-extrabold text-navy-900"><?= e(formatRupiah($shipReguler)) ?></p>
      <p class="relative text-xs text-gray-500 mt-1">Estimasi 3-5 hari kerja</p>
    </div>
    <div class="reveal delay-100 group bg-white border border-gray-100 rounded-xl p-5 shadow-card card-hover relative overflow-hidden">
      <div class="absolute -right-4 -top-4 w-16 h-16 bg-cyan-50 rounded-full transition-transform duration-300 group-hover:scale-150"></div>
      <p class="relative text-sm font-bold text-navy-900 mb-1"><i class="fa-solid fa-bolt text-cyan-500 mr-2"></i>Express</p>
      <p class="relative text-lg font-extrabold text-navy-900"><?= e(formatRupiah($shipExpress)) ?></p>
      <p class="relative text-xs text-gray-500 mt-1">Estimasi 1-2 hari kerja</p>
    </div>
  </div>

  <div class="prose prose-sm max-w-none text-gray-600 space-y-4 leading-relaxed">
    <div class="reveal group bg-white border border-gray-100 rounded-xl p-5 shadow-card card-hover flex gap-4" style="transition-delay:0ms">
      <span class="w-10 h-10 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0 transition-all duration-300 group-hover:bg-cyan-500 group-hover:text-white group-hover:rotate-6"><i class="fa-solid fa-map-location-dot"></i></span>
      <div>
        <h2 class="text-navy-900 font-bold text-base mb-1">Wilayah Pengiriman</h2>
        <p>TechZone melayani pengiriman ke seluruh wilayah Indonesia yang dijangkau oleh mitra ekspedisi kami.</p>
      </div>
    </div>
    <div class="reveal group bg-white border border-gray-100 rounded-xl p-5 shadow-card card-hover flex gap-4" style="transition-delay:60ms">
      <span class="w-10 h-10 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0 transition-all duration-300 group-hover:bg-cyan-500 group-hover:text-white group-hover:rotate-6"><i class="fa-solid fa-box-open"></i></span>
      <div>
        <h2 class="text-navy-900 font-bold text-base mb-1">Proses Pengiriman</h2>
        <p>Pesanan akan dikirim setelah status pembayaran "Lunas" (untuk QRIS/Transfer) atau langsung diproses untuk metode COD. Nomor resi akan tersedia pada halaman Detail Pesanan setelah admin menginput data pengiriman.</p>
      </div>
    </div>
    <div class="reveal group bg-white border border-gray-100 rounded-xl p-5 shadow-card card-hover flex gap-4" style="transition-delay:120ms">
      <span class="w-10 h-10 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0 transition-all duration-300 group-hover:bg-cyan-500 group-hover:text-white group-hover:rotate-6"><i class="fa-solid fa-triangle-exclamation"></i></span>
      <div>
        <h2 class="text-navy-900 font-bold text-base mb-1">Kerusakan Saat Pengiriman</h2>
        <p>Apabila produk mengalami kerusakan akibat proses pengiriman, segera hubungi kami melalui halaman Kontak dengan menyertakan foto bukti kerusakan dan nomor pesanan.</p>
      </div>
    </div>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
