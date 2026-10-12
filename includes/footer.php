<?php $base = techzoneBaseUrl(); ?>
<footer class="tz-footer">
  <div class="absolute inset-0 grid-pattern opacity-40 pointer-events-none"></div>
  <span class="tz-footer__mega" aria-hidden="true">TechZone</span>

  <div class="tz-footer__inner">
    <div class="tz-footer__brand">
      <a href="<?= e($base) ?>/index.php" class="tz-logo w-fit">
        <span class="tz-logo__mark"><i class="fa-solid fa-microchip"></i></span>
        <span class="tz-logo__text">Tech<em class="gradient-text">Zone</em></span>
      </a>
      <p class="tz-footer__tag">Teknologi IoT untuk Dunia yang Lebih Terhubung</p>
      <div class="tz-social">
        <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram text-sm"></i></a>
        <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f text-sm"></i></a>
        <a href="#" aria-label="X"><i class="fa-brands fa-x-twitter text-sm"></i></a>
        <a href="#" aria-label="Youtube"><i class="fa-brands fa-youtube text-sm"></i></a>
      </div>
    </div>

    <div>
      <h4>Menu</h4>
      <ul>
        <li><a href="<?= e($base) ?>/products.php">Produk</a></li>
        <li><a href="<?= e($base) ?>/products.php#kategori">Kategori</a></li>
        <li><a href="<?= e($base) ?>/about.php">Tentang Kami</a></li>
        <li><a href="<?= e($base) ?>/contact.php">Kontak</a></li>
        <li><a href="<?= e($base) ?>/faq.php">FAQ</a></li>
      </ul>
    </div>

    <div>
      <h4>Bantuan</h4>
      <ul>
        <li><a href="<?= e($base) ?>/faq.php">Cara Belanja</a></li>
        <li><a href="<?= e($base) ?>/shipping-policy.php">Pengiriman</a></li>
        <li><a href="<?= e($base) ?>/return-policy.php">Pengembalian</a></li>
      </ul>
    </div>

    <div>
      <h4>Legal</h4>
      <ul>
        <li><a href="<?= e($base) ?>/terms.php">Syarat &amp; Ketentuan</a></li>
        <li><a href="<?= e($base) ?>/privacy-policy.php">Kebijakan Privasi</a></li>
        <li><a href="<?= e($base) ?>/return-policy.php">Kebijakan Pengembalian</a></li>
      </ul>
    </div>
  </div>

  <div class="tz-footer__bar">
    <div><span>&copy; <?= date('Y') ?> TechZone. Semua Hak Dilindungi.</span><span>Crafted for makers &amp; innovators</span></div>
  </div>
</footer>

<button type="button" class="tz-totop" id="tz-totop" aria-label="Kembali ke atas">
  <svg viewBox="0 0 50 50"><circle cx="25" cy="25" r="24"/></svg>
  <i class="fa-solid fa-arrow-up text-sm"></i>
</button>
<script src="<?= e($base) ?>/assets/js/app.js"></script>
</body>
</html>
