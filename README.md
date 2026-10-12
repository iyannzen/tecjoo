# TechZone — Toko Online Perangkat IoT

Website e-commerce untuk perangkat IoT, dibangun dengan **PHP Native + PDO + MySQL + Tailwind CSS**,
berjalan di atas **XAMPP (Apache + MySQL)**, **tanpa API eksternal maupun payment gateway apapun**.

**Status: SELESAI — seluruh 7 tahap roadmap telah rampung.** TechZone siap dijalankan di XAMPP
lokal maupun dipindahkan ke hosting PHP + MySQL.

---

## Daftar Isi

1. [Instalasi di XAMPP](#1-instalasi-di-xampp)
2. [Akun Admin Development](#2-akun-admin-development)
3. [Struktur Folder](#3-struktur-folder)
4. [Kupon untuk Pengujian](#4-kupon-untuk-pengujian)
5. [Catatan Simulasi Pembayaran](#5-catatan-simulasi-pembayaran)
6. [Roadmap Pengerjaan (Semua Tahap Selesai)](#6-roadmap-pengerjaan-semua-tahap-selesai)
7. [Catatan Keamanan yang Sudah Diterapkan](#7-catatan-keamanan-yang-sudah-diterapkan)
8. [Memindahkan ke Hosting](#8-memindahkan-ke-hosting)
9. [Ringkasan Audit Keamanan (Tahap 7)](#9-ringkasan-audit-keamanan-tahap-7)
10. [Checklist Pengujian Manual](#10-checklist-pengujian-manual)

---

## 1. Instalasi di XAMPP

1. Install [XAMPP](https://www.apachefriends.org/) (Apache + MySQL + PHP 8+).
2. Jalankan **Apache** dan **MySQL** dari XAMPP Control Panel.
3. Salin folder `techzone/` ke dalam folder `htdocs`, sehingga path-nya menjadi:
   ```
   C:\xampp\htdocs\techzone\              (Windows)
   /Applications/XAMPP/htdocs/techzone/   (Mac)
   /opt/lampp/htdocs/techzone/            (Linux)
   ```
4. Buka **phpMyAdmin**: `http://localhost/phpmyadmin`
5. Buat database baru bernama **`techzone_db`** (collation `utf8mb4_unicode_ci`).
6. Buka tab **Import**, pilih file `database/schema.sql`, klik **Go**.
7. Ulangi Import untuk file `database/seed.sql` (kategori dasar, kupon contoh & pengaturan toko).
8. Ulangi Import sekali lagi untuk file `database/seed-products.sql` (60 produk IoT lengkap
   dengan spesifikasi, fitur, dan gambar placeholder lokal).
9. Buat akun admin development dengan menjalankan seeder PHP (agar password di-hash oleh
   `password_hash()` PHP asli, bukan hash yang ditulis manual):
   - Lewat browser: buka `http://localhost/techzone/database/seed_admin.php`
   - Atau lewat terminal:
     ```
     cd /path/ke/htdocs/techzone
     php database/seed_admin.php
     ```
   Anda akan melihat konfirmasi akun admin berhasil dibuat.
10. Periksa `config/database.php` — untuk instalasi XAMPP default, biasanya tidak perlu diubah
    (Host: `localhost`, Database: `techzone_db`, Username: `root`, Password: kosong).
11. Buka website di browser: `http://localhost/techzone/`

> **Catatan XAMPP**: karena project berada di sub-folder `htdocs/techzone/` (bukan langsung di
> `htdocs/`), halaman 404/500 kustom di `.htaccess` perlu disesuaikan agar Apache menemukannya.
> Buka `.htaccess`, ubah dua baris berikut:
> ```
> ErrorDocument 404 /techzone/404.php
> ErrorDocument 500 /techzone/500.php
> ```
> Saat dipindahkan ke hosting (project menjadi root domain), kembalikan ke `/404.php` dan
> `/500.php` seperti semula — lihat komentar di dalam file `.htaccess`.

---

## 2. Akun Admin Development

```
URL      : http://localhost/techzone/admin/login.php
Email    : admin@techzone.local
Password : TechZoneAdmin@2026
```

> ⚠️ **PENTING**: Password di atas hanya untuk kebutuhan development/testing lokal. **WAJIB
> diganti** sebelum website digunakan di lingkungan production/hosting publik. Setelah login
> pertama kali, disarankan juga menghapus atau membatasi akses ke `database/seed_admin.php`
> agar tidak dapat dijalankan ulang oleh pihak lain.

Akun **user** tidak dibuat otomatis — silakan daftar sendiri melalui `register.php`.

---

## 3. Struktur Folder

```
techzone/
├── index.php                   Homepage (kategori, banner, produk unggulan & terbaru)
├── products.php                 Katalog produk (search, filter, sort, pagination)
├── product-detail.php           Detail produk (galeri, tab, add-to-cart, wishlist)
├── cart.php                      Keranjang belanja (guest & user, kupon)
├── checkout.php                  Checkout (alamat, pengiriman, pembayaran, ringkasan)
├── login.php / register.php    Autentikasi user
├── logout.php
├── forgot-password.php         Reset password mandiri (tanpa API email eksternal)
├── about.php / contact.php / faq.php
├── terms.php / privacy-policy.php / shipping-policy.php / return-policy.php
├── 404.php / 500.php           Halaman error
│
├── account/
│   ├── index.php                 Dashboard akun
│   ├── addresses.php              Kelola alamat (CRUD + set default)
│   ├── wishlist.php                Wishlist (pindah ke keranjang / hapus)
│   ├── orders.php                   Daftar pesanan
│   ├── order-detail.php             Detail pesanan (+ tombol Bayar Sekarang + form review)
│   ├── invoice.php                   Invoice cetak (printable)
│   ├── transactions.php              Riwayat transaksi
│   ├── notifications.php              Notifikasi in-app (tandai dibaca)
│   └── _sidebar.php
│
├── payment/
│   ├── index.php                 Router ke qris/transfer/cod sesuai order
│   ├── qris.php                   Simulasi QRIS (QR dekoratif + countdown)
│   ├── transfer.php                Simulasi Transfer Bank + upload bukti
│   └── cod.php                      Info pesanan COD
│
├── admin/
│   ├── login.php                 Login khusus admin
│   ├── index.php                  Dashboard admin (data asli dari database)
│   ├── products.php                Daftar produk (filter, aktif/nonaktif, unggulan)
│   ├── product-add.php              Tambah produk baru
│   ├── product-edit.php              Edit produk + galeri gambar tambahan
│   ├── _product_form.php             Form produk (partial, dipakai add & edit)
│   ├── categories.php                CRUD kategori
│   ├── inventory.php                  Kelola stok (update cepat + filter)
│   ├── orders.php                     Daftar pesanan (filter lengkap)
│   ├── order-detail.php                Update status/kurir/resi pesanan
│   ├── payments.php                    Verifikasi pembayaran (konfirmasi/tolak)
│   ├── users.php                        Kelola user (cari, aktif/nonaktif)
│   ├── reviews.php                       Kelola review (sembunyikan/tampilkan/hapus)
│   ├── coupons.php                        CRUD kupon
│   ├── banners.php                         CRUD banner homepage
│   ├── reports.php                          Laporan penjualan/produk/pesanan/pembayaran
│   ├── transactions.php                      Riwayat transaksi admin (filter lengkap)
│   ├── export-csv.php                         Export CSV riwayat transaksi
│   ├── settings.php                            Pengaturan toko (rekening, ongkir, dsb)
│   └── _sidebar.php / _topbar.php
│
├── actions/
│   ├── login.php                 Proses backend login user (+ merge cart guest)
│   ├── register.php              Proses backend registrasi user (+ merge cart guest)
│   ├── cart.php                   Tambah/update/hapus item, terapkan/batalkan kupon
│   ├── wishlist.php                Toggle tambah/hapus wishlist
│   ├── checkout.php                Proses checkout: transaksi DB penuh + rollback
│   ├── payment.php                  Klaim "Saya Sudah Membayar" (QRIS)
│   ├── upload-proof.php              Upload & validasi bukti transfer
│   └── review.php                     Submit review (hanya order Selesai)
│
├── config/
│   ├── database.php              Koneksi PDO
│   ├── app.php                   Konfigurasi umum, session, base URL dinamis
│   └── payment.php                Konfigurasi & label status pembayaran/pesanan
│
├── includes/
│   ├── functions.php             Fungsi umum (format Rupiah, tanggal, dsb)
│   ├── helpers.php                Nomor order, rate limiting login, dsb
│   ├── product_helpers.php        Kartu produk, rating bintang, harga efektif, recalc rating
│   ├── cart_helpers.php           Cart guest/user, merge saat login
│   ├── payment_helpers.php        QR simulasi, auto-expire, validasi file bukti
│   ├── upload_helpers.php         Validasi & simpan upload gambar (admin)
│   ├── notification_helpers.php   Buat notifikasi in-app, hitung belum dibaca
│   ├── csrf.php                   Proteksi CSRF token
│   ├── auth.php                   Proteksi halaman user
│   ├── admin_auth.php             Proteksi halaman admin
│   └── header.php / footer.php / navbar.php
│
├── database/
│   ├── schema.sql                 Struktur seluruh tabel
│   ├── seed.sql                   Data awal: kategori, kupon contoh & pengaturan toko
│   ├── seed-products.sql          60 produk IoT (generated, lihat folder _gen/)
│   ├── seed_admin.php             Seeder akun admin (password_hash asli)
│   └── _gen/                      Script Python (dev tool) untuk generate seed-products.sql
│
├── assets/images/products/        60 gambar placeholder SVG (dibuat lokal, tanpa API eksternal)
├── uploads/                       Folder upload (produk, bukti transfer, dst — eksekusi PHP dimatikan)
└── storage/logs/                  Log error PHP
```

---

## 4. Kupon untuk Pengujian

Dua kupon contoh sudah tersedia lewat `seed.sql` agar fitur kupon di halaman keranjang/checkout
dapat langsung diuji, selain kupon-kupon lain yang dapat dibuat admin di `admin/coupons.php`:

| Kode | Jenis | Nilai | Minimum Belanja |
|---|---|---|---|
| `TECHZONE10` | Persentase | 10% (maks. potongan Rp 50.000) | Rp 100.000 |
| `HEMAT20K` | Nominal | Rp 20.000 | Rp 150.000 |

---

## 5. Catatan Simulasi Pembayaran

- **QRIS**: gambar QR yang tampil adalah pola dekoratif yang dibuat lokal (`renderFakeQrSvg()`
  di `includes/payment_helpers.php`) — **bukan** QR code asli dan tidak terhubung ke aplikasi
  pembayaran mana pun. Ini murni simulasi tampilan sesuai batasan proyek (tanpa payment gateway).
- Status **"Lunas" tidak pernah** ditentukan oleh parameter URL atau input dari klien. Klik
  "Saya Sudah Membayar" / "Saya Sudah Transfer" hanya memindahkan status ke **"Menunggu
  Verifikasi"**; hanya admin (melalui `admin/payments.php`) yang dapat mengubahnya menjadi
  **"Lunas"**, dan itu pun lewat proses backend PHP yang memperbarui tabel `payments` dan
  `orders` sekaligus.
- Bukti transfer divalidasi ekstensi, MIME type asli file, dan ukuran maksimal 2MB, lalu
  disimpan dengan nama file baru (bukan nama asli) di `uploads/payments/` yang sudah diproteksi
  agar PHP tidak bisa dieksekusi di folder tersebut.
- Khusus COD: begitu admin menandai pesanan **Selesai**, status pembayaran otomatis berubah
  menjadi **"Dibayar di Tempat"** (secara internal disimpan sebagai `paid`, ditampilkan berbeda
  khusus untuk metode COD — lihat `paymentStatusLabel()` di `config/payment.php`).

---

## 6. Roadmap Pengerjaan (Semua Tahap Selesai)

- ✅ **Tahap 1** — Struktur folder, database, autentikasi, session, security.
- ✅ **Tahap 2** — Navbar & homepage final, 10 kategori, 60 produk IoT, pencarian, filter, detail produk.
- ✅ **Tahap 3** — Keranjang (guest & user, auto-merge saat login), wishlist, alamat, checkout
  dengan transaksi database penuh (row-locking stok, cegah double-order), dan kupon.
- ✅ **Tahap 4** — Simulasi pembayaran QRIS (QR dekoratif + countdown) & Transfer Bank (upload
  bukti), COD, verifikasi pembayaran oleh admin, invoice cetak, riwayat transaksi.
- ✅ **Tahap 5** — Admin: CRUD produk (+ galeri gambar), kategori, stok, kelola pesanan (update
  status/kurir/resi), kelola user (tanpa pernah menampilkan password).
- ✅ **Tahap 6** — Review produk (hanya dari order Selesai), notifikasi in-app, laporan
  penjualan/produk/pesanan/pembayaran, export CSV, kelola kupon & banner, pengaturan toko.
- ✅ **Tahap 7** — Audit keamanan menyeluruh, bug fixing, dan persiapan hosting (lihat bagian 9 & 10).

---

## 7. Catatan Keamanan yang Sudah Diterapkan

- Password disimpan dengan `password_hash()` / diverifikasi dengan `password_verify()` — tidak
  pernah dalam bentuk plaintext, tidak pernah ditampilkan ke siapa pun termasuk admin.
- Seluruh query database memakai **PDO prepared statement** (emulasi mode dimatikan).
- Proteksi **CSRF token** pada seluruh form yang mengubah data (login, register, checkout,
  tambah/edit/hapus produk, update profil, konfirmasi pembayaran, update status order, dsb).
- **Session regeneration** setelah login berhasil (mencegah session fixation).
- Cookie session `httponly`, `samesite=Lax`, dan `secure` otomatis saat HTTPS.
- **Rate limiting** percobaan login (mencegah brute-force) untuk user maupun admin, dengan
  session key terpisah.
- Login admin **terpisah total** dari login user, dengan pengecekan role di server
  (`includes/admin_auth.php`) pada setiap halaman `/admin/*` — bukan sekadar menyembunyikan menu.
- Seluruh output di-escape dengan `htmlspecialchars()` (fungsi `e()`) untuk mencegah XSS.
- Folder `uploads/` diproteksi `.htaccess` agar file PHP tidak bisa dieksekusi di dalamnya.
- Upload file (gambar produk/kategori/banner, bukti transfer) divalidasi ekstensi, MIME type
  asli file (`finfo`), ukuran maksimal, dan nama file selalu di-generate ulang.
- Stok produk dijaga tidak pernah negatif (row-lock `SELECT ... FOR UPDATE` + guard `WHERE
  stock >= qty` saat checkout, validasi eksplisit di form admin).
- Harga selalu dihitung ulang dari database saat checkout — tidak pernah dipercaya dari input
  frontend/cache keranjang.
- Pesan error database tidak pernah ditampilkan ke pengguna, hanya dicatat ke
  `storage/logs/php_error.log`.
- Audit log (`activity_logs`) mencatat aksi penting: login/logout, register, checkout, klaim
  pembayaran, konfirmasi/tolak pembayaran, CRUD produk/kategori/kupon/banner, ubah status
  pesanan, ubah stok, ubah status user, ubah pengaturan, export CSV.

---

## 8. Memindahkan ke Hosting

Konfigurasi database & base URL dibuat dinamis agar project mudah dipindah dari
`localhost/techzone/` ke domain hosting:

- `config/database.php` membaca environment variable `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
  jika tersedia di server hosting, jika tidak baru menggunakan default lokal.
- `techzoneBaseUrl()` di `config/app.php` menghitung base URL secara otomatis dari request,
  sehingga tidak ada path absolut atau `localhost` yang di-hardcode di kode.

### Langkah Deploy ke Hosting

1. Upload seluruh isi folder `techzone/` ke `public_html/` (atau document root domain Anda).
2. Buat database MySQL baru di control panel hosting (cPanel/Plesk/dsb), catat host/nama
   database/username/password yang diberikan.
3. Set environment variable `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` sesuai kredensial di atas
   (lewat panel hosting, atau langsung edit `config/database.php` jika hosting tidak mendukung
   environment variable).
4. Import `database/schema.sql`, `database/seed.sql`, `database/seed-products.sql` lewat
   phpMyAdmin bawaan hosting (urutan harus sesuai).
5. Jalankan `https://domainanda.com/database/seed_admin.php` sekali untuk membuat akun admin,
   lalu **segera hapus file `database/seed_admin.php`** dari server (atau pindahkan keluar
   folder publik) agar tidak bisa dijalankan ulang oleh pihak lain.
6. Pastikan `.htaccess` memakai `ErrorDocument 404 /404.php` dan `/500.php` (nilai default yang
   sudah benar untuk deployment di root domain).
7. Set environment variable `APP_ENV=production` agar detail error PHP tidak ditampilkan ke
   pengunjung (`config/app.php` otomatis menonaktifkan `display_errors` saat ini aktif).
8. Pastikan folder `uploads/` dan `storage/logs/` dapat ditulis oleh PHP (permission 755/775).
9. **Ganti password admin default** (`TechZoneAdmin@2026`) — ubah nilai `ADMIN_PASSWORD` di
   `database/seed_admin.php` sebelum menjalankannya, atau ganti langsung lewat database setelahnya.

---

## 9. Ringkasan Audit Keamanan (Tahap 7)

Audit menyeluruh dilakukan terhadap seluruh kode sebelum rilis final:

| Area yang Diperiksa | Hasil |
|---|---|
| Setiap halaman `/admin/*` memanggil `admin_auth.php` | ✅ Terverifikasi, tanpa terkecuali |
| Setiap halaman `/account/*` & `/payment/*` memanggil `requireUserLogin()` | ✅ Terverifikasi |
| Setiap form/handler yang mengubah data memvalidasi CSRF token | ✅ Terverifikasi |
| Tidak ada query SQL yang menyisipkan input pengguna secara langsung | ✅ Semua pakai PDO prepared statement |
| Password user tidak pernah ikut ter-`SELECT` di luar proses login/registrasi | ✅ Terverifikasi |
| Semua pembaruan stok dijaga agar tidak pernah negatif | ✅ Row-lock + guard di checkout, validasi di admin |
| Semua `<img>` memiliki atribut `alt` | ✅ Terverifikasi ulang dengan parser khusus |
| Tidak ada path absolut yang di-hardcode di form action/redirect | ✅ Terverifikasi |
| Tidak ada sisa kode debug (`var_dump`, `print_r`, TODO) | ✅ Terverifikasi |
| Pesan error database tidak pernah ditampilkan mentah ke pengguna | ✅ Hanya `RuntimeException` buatan sendiri yang tampil |
| Upload file divalidasi ekstensi + MIME asli + ukuran + nama file di-generate ulang | ✅ Terverifikasi |
| Folder `uploads/` tidak bisa mengeksekusi PHP | ✅ `.htaccess` khusus di dalam folder |
| Query list admin dibatasi `LIMIT` (menghindari query berat) | ✅ Terverifikasi & ditambahkan pada `admin/users.php` |

**Bug yang ditemukan dan diperbaiki selama audit:**

1. Path gambar kategori (`admin/categories.php`) belum diberi prefix folder `uploads/categories/`
   saat disimpan ke database — **diperbaiki**.
2. `.htaccess` meng-hardcode path `/techzone/` pada `ErrorDocument`, melanggar prinsip
   portabilitas ke hosting — **diperbaiki** (default sekarang portable untuk hosting, dengan
   catatan penyesuaian untuk instalasi XAMPP sub-folder).
3. Favicon merujuk ke file `favicon.png` yang tidak pernah dibuat — **diperbaiki** dengan
   favicon SVG lokal.
4. Halaman daftar user admin belum dibatasi `LIMIT` — **diperbaiki** untuk mencegah query berat
   pada basis data user yang sangat besar.

---

## 10. Checklist Pengujian Manual

**User**
- [ ] Registrasi akun baru → langsung ter-login otomatis
- [ ] Logout, lalu login kembali dengan akun yang sama
- [ ] Login dengan password salah 5x berturut-turut → muncul pesan rate-limit
- [ ] Kelola alamat (tambah, edit, hapus, jadikan utama)
- [ ] Tambah produk ke wishlist sebagai guest → diarahkan login, lalu wishlist tersimpan
- [ ] Tambah ke keranjang sebagai guest, lalu login → keranjang guest tergabung ke akun

**Produk**
- [ ] Cari produk via kolom pencarian (nama, SKU, brand)
- [ ] Filter kategori, rentang harga, brand, rating, ketersediaan
- [ ] Urutkan produk (termurah/termahal/terpopuler/rating/A-Z)
- [ ] Buka detail produk, cek seluruh tab (Deskripsi/Spesifikasi/Fitur/Kegunaan/Kompatibilitas/Isi Paket/Review)

**Keranjang & Checkout**
- [ ] Tambah beberapa produk berbeda, ubah jumlah, hapus salah satu
- [ ] Coba masukkan jumlah melebihi stok → muncul pesan penolakan
- [ ] Terapkan kupon `TECHZONE10` dan `HEMAT20K`, lalu batalkan
- [ ] Checkout dengan alamat baru sekaligus mencentang "simpan alamat"
- [ ] Checkout dengan QRIS → cek countdown, klik "Saya Sudah Membayar"
- [ ] Checkout dengan Transfer → unggah bukti transfer (coba file >2MB dan format salah, harus ditolak)
- [ ] Checkout dengan COD → order langsung berstatus Diproses
- [ ] Klik tombol "Buat Pesanan" dua kali berturut-turut dengan cepat → pastikan hanya 1 order tercipta

**Admin**
- [ ] Login admin dengan akun development
- [ ] Tambah, edit, nonaktifkan produk; tambah/hapus gambar galeri
- [ ] Tambah & edit kategori; coba hapus kategori yang masih punya produk (harus ditolak)
- [ ] Update stok dari halaman Inventory
- [ ] Konfirmasi & tolak pembayaran dari halaman Pembayaran, cek notifikasi user muncul
- [ ] Ubah status pesanan ke Dikirim/Selesai, isi kurir & resi, cek notifikasi user muncul
- [ ] Tandai pesanan COD sebagai Selesai → status pembayaran berubah jadi "Dibayar di Tempat"
- [ ] Nonaktifkan/aktifkan user; pastikan tidak bisa menonaktifkan akun sendiri
- [ ] Sembunyikan/tampilkan/hapus review
- [ ] Tambah/edit/nonaktifkan kupon & banner
- [ ] Ubah pengaturan toko (ongkir, rekening, dsb) → cek berubah di halaman checkout/transfer
- [ ] Lihat Laporan, lalu Export CSV dan buka hasilnya di Excel/Spreadsheet

**Review**
- [ ] Tombol "Beri Review" hanya muncul untuk pesanan berstatus Selesai
- [ ] Beri review, cek rating rata-rata produk ikut berubah di halaman detail produk

Jika seluruh poin di atas berjalan sesuai deskripsi, instalasi TechZone Anda berfungsi dengan baik.
