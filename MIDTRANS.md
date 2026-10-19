# Integrasi Midtrans (Snap) - TechZone

1. Jalankan `database/migration_midtrans.sql` (jika belum).
2. Key ada di `config/midtrans.local.php` (atau env MIDTRANS_SERVER_KEY / MIDTRANS_CLIENT_KEY). Jangan di-commit.
   Mode diatur di `'environment'` (`sandbox` / `production`). Key harus sesuai mode; default sandbox.
3. Dashboard Midtrans -> Settings -> Configuration -> Payment Notification URL:
   `https://DOMAIN-ANDA/payment/midtrans-notify.php`
   (Midtrans tidak bisa menjangkau localhost; tanpa webhook, status tetap tersinkron saat halaman bayar dibuka/dipoll.)
4. Aktif/nonaktifkan lewat Admin -> Pengaturan -> Metode Pembayaran Aktif -> Midtrans.

File: config/midtrans.php, includes/midtrans_helpers.php, payment/midtrans.php, payment/midtrans-notify.php, payment/midtrans-status.php

## Alur konfirmasi admin (tanpa Midtrans Simulator)

1. User klik **Bayar Sekarang** -> popup Snap -> pilih metode (GoPay, BCA, dll) -> bayar.
2. `payment/midtrans-claim.php` memindahkan pembayaran ke **Menunggu Verifikasi** dan mengirim notifikasi ke admin.
3. Admin membuka **Verifikasi Pembayaran** (`admin/payments.php`) -> **Konfirmasi** (atau **Tolak**).
4. Halaman user (`payment/midtrans.php`) polling status; begitu dikonfirmasi otomatis berpindah ke "Pembayaran Dikonfirmasi" lalu ke detail pesanan (status Diproses).

Status Lunas hanya bisa ditetapkan admin (atau webhook Midtrans bila pembayaran benar-benar masuk).
