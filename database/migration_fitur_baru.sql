-- TechZone - Migrasi fitur baru (OPSIONAL)
-- Tabel di bawah dibuat OTOMATIS oleh aplikasi saat pertama kali dibutuhkan.
-- Jalankan file ini hanya jika Anda ingin membuatnya manual (mis. via phpMyAdmin).

CREATE TABLE IF NOT EXISTS order_status_history (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  status VARCHAR(40) NOT NULL,
  note VARCHAR(255) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_osh_order (order_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ewallet_channels (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL,
  mark VARCHAR(20) NOT NULL DEFAULT '',
  logo_image VARCHAR(255) DEFAULT NULL,
  logo_bg VARCHAR(30) NOT NULL DEFAULT '#1CA1E9',
  logo_fg VARCHAR(20) NOT NULL DEFAULT '#ffffff',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_ewallet_name (name),
  INDEX idx_ewallet_active (is_active)
) ENGINE=InnoDB;
-- (ewallet_channels diisi 6 e-wallet bawaan otomatis sekali saat pertama dipakai;
--  kelola dari Admin > Rekening & E-Wallet > E-Wallet.)

-- Pengaturan baru (opsional; bila tidak ada, dipakai nilai bawaan):
--   payment_expiry_hours  = 24   (batas waktu bayar QRIS/Transfer, jam)
--   low_stock_threshold   = 10   (ambang notifikasi stok menipis)
--   cart_reminder_hours   = 24   (pengingat keranjang yang ditinggalkan, jam)
-- Semuanya bisa diubah dari Admin > Pengaturan.
