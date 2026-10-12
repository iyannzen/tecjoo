# Integrasi Midtrans (Snap) - TechZone

1. Jalankan `database/migration_midtrans.sql` (jika belum).
2. Key ada di `config/midtrans.local.php` (atau env MIDTRANS_SERVER_KEY / MIDTRANS_CLIENT_KEY). Jangan di-commit.
   Mode diatur di `'environment'` (`sandbox` / `production`). Key harus sesuai mode; default sandbox.
3. Dashboard Midtrans -> Settings -> Configuration -> Payment Notification URL:
   `https://DOMAIN-ANDA/payment/midtrans-notify.php`
   (Midtrans tidak bisa menjangkau localhost; tanpa webhook, status tetap tersinkron saat halaman bayar dibuka/dipoll.)
4. Aktif/nonaktifkan lewat Admin -> Pengaturan -> Metode Pembayaran Aktif -> Midtrans.

File: config/midtrans.php, includes/midtrans_helpers.php, payment/midtrans.php, payment/midtrans-notify.php, payment/midtrans-status.php
