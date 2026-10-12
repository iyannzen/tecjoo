-- =======================================================================
-- TechZone - Migrasi: Pilihan E-wallet/Bank + Pembatalan & Pengembalian Dana
-- Jalankan file ini SEKALI SAJA jika database Anda dibuat SEBELUM fitur ini
-- ditambahkan. Jika Anda baru membuat database dari database/schema.sql
-- yang terbaru, migrasi ini TIDAK perlu dijalankan (kolom sudah ada).
-- =======================================================================

-- ---------------------------------------------------------------------
-- Tabel baru: bank_accounts (rekening tujuan transfer, boleh lebih dari satu)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bank_accounts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bank_name VARCHAR(50) NOT NULL,
  account_number VARCHAR(50) NOT NULL,
  account_holder VARCHAR(150) NOT NULL,
  logo_image VARCHAR(255) DEFAULT NULL,
  logo_bg VARCHAR(30) NOT NULL DEFAULT '#005AAB',
  logo_fg VARCHAR(20) NOT NULL DEFAULT '#ffffff',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_bank_accounts_active (is_active)
) ENGINE=InnoDB;

INSERT INTO bank_accounts (bank_name, account_number, account_holder, logo_bg, logo_fg, is_active, sort_order) VALUES
('BCA',         '2450123456', 'PT TechZone Indonesia', '#005AAB', '#ffffff', 1, 1),
('BRI',         '0123456789', 'PT TechZone Indonesia', '#00529C', '#ffffff', 1, 2),
('BNI',         '8877665544', 'PT TechZone Indonesia', '#F37021', '#ffffff', 1, 3),
('Mandiri',     '1330099887', 'PT TechZone Indonesia', '#003D79', '#FFC600', 1, 4),
('BSI',         '7712345566', 'PT TechZone Indonesia', '#00A651', '#ffffff', 1, 5),
('CIMB Niaga',  '8009988776', 'PT TechZone Indonesia', '#7A1315', '#ffffff', 1, 6);

ALTER TABLE orders
  ADD COLUMN payment_channel VARCHAR(50) DEFAULT NULL COMMENT 'E-wallet/bank pilihan pembeli, mis. DANA, BCA (informasional)' AFTER payment_method,
  ADD COLUMN bank_account_id INT UNSIGNED DEFAULT NULL COMMENT 'Rekening tujuan transfer yang dipilih pembeli' AFTER payment_channel,
  ADD COLUMN cancel_reason VARCHAR(255) DEFAULT NULL AFTER notes,
  ADD COLUMN refund_reason VARCHAR(255) DEFAULT NULL AFTER cancel_reason;

ALTER TABLE orders
  ADD CONSTRAINT fk_orders_bank_account FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id) ON DELETE SET NULL;

ALTER TABLE orders
  MODIFY COLUMN order_status ENUM('waiting_payment','payment_verified','processing','packed','shipped','completed','cancelled','refund_requested','returned') NOT NULL DEFAULT 'waiting_payment';
