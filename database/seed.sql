-- =====================================================================
-- TECHZONE - Seed Data (Tahap 1)
-- Berisi: 1 akun admin development + pengaturan awal toko + kategori dasar.
-- TIDAK berisi user/produk dummy maupun transaksi palsu.
-- Kategori & 60 produk lengkap akan ditambahkan pada seed-products.sql (Tahap 2).
-- =====================================================================

USE techzone_db;

-- ---------------------------------------------------------------------
-- Akun Admin Development
-- CATATAN: Akun admin TIDAK dibuat lewat file .sql ini, karena hash
-- password harus dibuat oleh fungsi PHP password_hash() yang asli
-- (bukan hash yang ditulis manual/karangan, yang berisiko salah/tidak valid).
--
-- Setelah meng-import schema.sql dan seed.sql ini, jalankan sekali:
--   http://localhost/techzone/database/seed_admin.php
-- atau lewat CLI:
--   php database/seed_admin.php
--
-- Script tersebut akan membuat akun admin development:
--   Email    : admin@techzone.local
--   Password : TechZoneAdmin@2026
-- PENTING: Password ini WAJIB DIGANTI sebelum website dipakai production.
-- ---------------------------------------------------------------------

-- ---------------------------------------------------------------------
-- Kategori Dasar (10 kategori sesuai spesifikasi)
-- ---------------------------------------------------------------------
INSERT INTO categories (name, slug, description, status) VALUES
('Mikrokontroler', 'mikrokontroler', 'Board pengembangan mikrokontroler untuk berbagai kebutuhan proyek IoT.', 'active'),
('Sensor', 'sensor', 'Berbagai jenis sensor untuk mendeteksi suhu, gerak, gas, cahaya, dan lainnya.', 'active'),
('Modul Komunikasi', 'modul-komunikasi', 'Modul komunikasi nirkabel maupun kabel untuk transfer data antar perangkat.', 'active'),
('Smart Home', 'smart-home', 'Perangkat untuk membangun sistem rumah pintar.', 'active'),
('Relay & Automation', 'relay-automation', 'Modul relay dan automation untuk kendali perangkat listrik.', 'active'),
('Motor & Aktuator', 'motor-aktuator', 'Motor, servo, dan aktuator untuk kebutuhan robotika dan automation.', 'active'),
('Display', 'display', 'Modul tampilan seperti LCD dan OLED untuk visualisasi data.', 'active'),
('Power Module', 'power-module', 'Modul catu daya, charging, dan konversi tegangan.', 'active'),
('IoT Kit', 'iot-kit', 'Paket lengkap untuk belajar dan membangun proyek IoT.', 'active'),
('Aksesori', 'aksesori', 'Kabel, breadboard, dan aksesori pendukung proyek elektronik.', 'active')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- ---------------------------------------------------------------------
-- Kupon Contoh (untuk pengujian fitur checkout — admin UI kupon
-- akan tersedia pada tahap pengerjaan Admin Area)
-- ---------------------------------------------------------------------
INSERT INTO coupons (code, type, value, minimum_purchase, maximum_discount, usage_limit, used_count, start_date, end_date, status) VALUES
('TECHZONE10', 'percentage', 10, 100000, 50000, 1000, 0, '2026-01-01', '2027-12-31', 'active'),
('HEMAT20K', 'fixed', 20000, 150000, NULL, 500, 0, '2026-01-01', '2027-12-31', 'active')
ON DUPLICATE KEY UPDATE code = VALUES(code);

-- ---------------------------------------------------------------------
-- Pengaturan Toko (settings)
-- ---------------------------------------------------------------------
INSERT INTO settings (setting_key, setting_value) VALUES
('store_name', 'TechZone'),
('store_tagline', 'Teknologi IoT untuk Dunia yang Lebih Terhubung'),
('store_email', 'iyann.zen@gmail.com'),
('store_whatsapp', '0881025277204'),
('store_address', 'Jl. Teknologi No. 1, Tangerang Selatan, Banten'),
('store_hours', 'Senin - Sabtu, 09.00 - 17.00 WIB'),
('bank_name', 'BCA'),
('bank_account_number', '1234567890'),
('bank_account_holder', 'TechZone'),
('qris_image', 'assets/images/qris-sample.png'),
('shipping_cost_reguler', '15000'),
('shipping_cost_express', '30000'),
('payment_qris_active', '1'),
('payment_transfer_active', '1'),
('payment_cod_active', '1'),
('qris_payment_duration_minutes', '15')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
