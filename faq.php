<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'FAQ | TechZone';
$pageDescription = 'Pertanyaan yang sering diajukan seputar belanja, pembayaran, dan pengiriman di TechZone.';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';

$faqs = [
    ['Bagaimana cara belanja di TechZone?', 'Pilih produk yang Anda inginkan, klik "Tambah ke Keranjang" atau "Beli Sekarang", lalu lanjutkan ke proses checkout. Anda perlu masuk atau mendaftar akun terlebih dahulu untuk menyelesaikan pesanan.'],
    ['Metode pembayaran apa saja yang tersedia?', 'TechZone mendukung tiga metode pembayaran: QRIS, Transfer Bank (BCA), dan COD (Cash On Delivery) untuk area tertentu.'],
    ['Apakah pembayaran QRIS/Transfer perlu verifikasi?', 'Ya. Setelah Anda menekan tombol "Saya Sudah Membayar/Transfer", status pesanan akan berubah menjadi "Menunggu Verifikasi" hingga admin TechZone memverifikasi pembayaran Anda.'],
    ['Berapa lama proses verifikasi pembayaran?', 'Verifikasi umumnya dilakukan dalam waktu kerja admin. Anda dapat memantau status pesanan secara real-time melalui halaman "Pesanan Saya".'],
    ['Apakah ada biaya pengiriman?', 'Ya, biaya pengiriman dihitung berdasarkan metode yang dipilih (Reguler atau Express) dan ditampilkan secara transparan sebelum Anda membuat pesanan.'],
    ['Bagaimana jika produk yang saya terima tidak sesuai?', 'Silakan hubungi kami melalui halaman Kontak dengan menyertakan nomor pesanan. Kebijakan pengembalian lengkap dapat dilihat di halaman Kebijakan Pengembalian.'],
    ['Apakah saya bisa memberikan ulasan produk?', 'Bisa, review hanya dapat diberikan untuk produk dari pesanan yang berstatus Selesai, guna menjaga keaslian ulasan.'],
];

renderPageHero('Pertanyaan yang Sering Diajukan', 'Temukan jawaban atas pertanyaan umum seputar belanja di TechZone.', 'fa-circle-question');
?>
<main class="max-w-3xl mx-auto px-4 py-14">
  <div class="space-y-3">
    <?php foreach ($faqs as $i => [$question, $answer]): ?>
    <div class="reveal bg-white border border-gray-100 rounded-xl overflow-hidden shadow-card hover:border-cyan-200 transition-colors" style="transition-delay: <?= min($i * 60, 300) ?>ms">
      <button type="button" class="techzone-faq-btn w-full flex items-center justify-between px-5 py-4 text-left group">
        <span class="text-sm font-semibold text-navy-900 group-hover:text-cyan-600 transition-colors"><?= e($question) ?></span>
        <span class="w-7 h-7 rounded-full bg-gray-50 flex items-center justify-center shrink-0 ml-3 group-hover:bg-cyan-50 transition-colors">
          <i class="fa-solid fa-chevron-down text-gray-400 text-xs transition-transform duration-300 techzone-faq-icon"></i>
        </span>
      </button>
      <div class="techzone-faq-panel hidden px-5 pb-4">
        <p class="text-sm text-gray-600 leading-relaxed border-t border-gray-50 pt-3"><?= e($answer) ?></p>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="reveal mt-10 text-center bg-gradient-to-br from-navy-900 to-navy-800 rounded-2xl p-8 relative overflow-hidden">
    <div class="absolute inset-0 grid-pattern opacity-20"></div>
    <p class="relative text-white font-bold text-sm sm:text-base">Masih ada pertanyaan lain?</p>
    <p class="relative text-gray-400 text-xs sm:text-sm mt-1 mb-4">Tim kami siap membantu kapan saja.</p>
    <a href="contact.php" class="relative ripple-wrap btn-glow inline-block bg-cyan-500 hover:bg-cyan-400 text-navy-900 font-semibold text-sm px-6 py-2.5 rounded-full transition">
      Hubungi Kami <i class="fa-solid fa-arrow-right ml-1 text-xs"></i>
    </a>
  </div>
</main>
<script>
  document.querySelectorAll('.techzone-faq-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var panel = this.nextElementSibling;
      var icon = this.querySelector('.techzone-faq-icon');
      panel.classList.toggle('hidden');
      if (!panel.classList.contains('hidden')) {
        panel.classList.add('animate-fade-in');
      }
      icon.classList.toggle('rotate-180');
    });
  });
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
