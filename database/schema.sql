-- =====================================================================
-- TECHZONE - Database Schema
-- Database: techzone_db
-- Jalankan file ini melalui phpMyAdmin (tab Import) atau mysql client.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS techzone_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE techzone_db;

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Tabel: users
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  phone VARCHAR(30) DEFAULT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','user') NOT NULL DEFAULT 'user',
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_role (role),
  INDEX idx_users_status (status)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabel: categories
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(120) NOT NULL UNIQUE,
  description TEXT,
  image VARCHAR(255) DEFAULT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_categories_status (status)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabel: products
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  sku VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(200) NOT NULL,
  slug VARCHAR(220) NOT NULL UNIQUE,
  brand VARCHAR(100) DEFAULT NULL,
  short_description VARCHAR(500) DEFAULT NULL,
  description TEXT,
  specifications TEXT COMMENT 'JSON encoded',
  features TEXT COMMENT 'JSON encoded',
  applications TEXT COMMENT 'JSON encoded',
  compatibility TEXT COMMENT 'JSON encoded',
  package_contents TEXT COMMENT 'JSON encoded',
  price DECIMAL(12,2) NOT NULL DEFAULT 0,
  discount_price DECIMAL(12,2) DEFAULT NULL,
  stock INT NOT NULL DEFAULT 0,
  weight INT NOT NULL DEFAULT 0 COMMENT 'gram',
  dimensions VARCHAR(100) DEFAULT NULL,
  main_image VARCHAR(255) DEFAULT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  featured TINYINT(1) NOT NULL DEFAULT 0,
  rating DECIMAL(3,2) NOT NULL DEFAULT 0,
  review_count INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_products_status (status),
  INDEX idx_products_featured (featured),
  INDEX idx_products_category (category_id),
  FULLTEXT INDEX ft_products_search (name, brand, short_description, description)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabel: product_images
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS product_images (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  image VARCHAR(255) NOT NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT fk_product_images_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  INDEX idx_product_images_product (product_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabel: carts & cart_items
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS carts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED DEFAULT NULL,
  session_id VARCHAR(128) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_carts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_carts_user (user_id),
  INDEX idx_carts_session (session_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cart_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cart_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  price DECIMAL(12,2) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_cart_items_cart FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
  CONSTRAINT fk_cart_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_cart_product (cart_id, product_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabel: wishlists
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS wishlists (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_wishlists_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_wishlists_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_wishlist (user_id, product_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabel: addresses
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS addresses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  recipient_name VARCHAR(150) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  address TEXT NOT NULL,
  city VARCHAR(100) NOT NULL,
  province VARCHAR(100) NOT NULL,
  postal_code VARCHAR(10) NOT NULL,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_addresses_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_addresses_user (user_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabel: bank_accounts (rekening tujuan transfer — dikelola admin,
-- boleh lebih dari satu bank sehingga pembeli benar-benar bisa memilih
-- mau transfer ke rekening bank yang mana)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bank_accounts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bank_name VARCHAR(50) NOT NULL,
  account_number VARCHAR(50) NOT NULL,
  account_holder VARCHAR(150) NOT NULL,
  logo_image VARCHAR(255) DEFAULT NULL COMMENT 'Path file logo yang diunggah admin, mis. uploads/banks/xxx.png',
  logo_bg VARCHAR(30) NOT NULL DEFAULT '#005AAB' COMMENT 'Warna latar badge fallback jika logo_image kosong',
  logo_fg VARCHAR(20) NOT NULL DEFAULT '#ffffff' COMMENT 'Warna teks badge fallback',
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

-- ---------------------------------------------------------------------
-- Tabel: orders
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_number VARCHAR(40) NOT NULL UNIQUE,
  user_id INT UNSIGNED NOT NULL,
  address_id INT UNSIGNED DEFAULT NULL,
  recipient_name VARCHAR(150) NOT NULL,
  recipient_phone VARCHAR(30) NOT NULL,
  shipping_address TEXT NOT NULL,
  shipping_city VARCHAR(100) NOT NULL,
  shipping_province VARCHAR(100) NOT NULL,
  shipping_postal_code VARCHAR(10) NOT NULL,
  shipping_method ENUM('reguler','express') NOT NULL DEFAULT 'reguler',
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
  shipping_cost DECIMAL(12,2) NOT NULL DEFAULT 0,
  discount DECIMAL(12,2) NOT NULL DEFAULT 0,
  total DECIMAL(12,2) NOT NULL DEFAULT 0,
  coupon_code VARCHAR(50) DEFAULT NULL,
  payment_method ENUM('qris','transfer','cod','midtrans') NOT NULL,
  payment_channel VARCHAR(50) DEFAULT NULL COMMENT 'E-wallet/bank pilihan pembeli, mis. DANA, BCA (informasional)',
  bank_account_id INT UNSIGNED DEFAULT NULL COMMENT 'Rekening tujuan transfer yang dipilih pembeli (lihat tabel bank_accounts)',
  payment_status ENUM('unpaid','waiting_verification','paid','failed','expired','cod') NOT NULL DEFAULT 'unpaid',
  order_status ENUM('waiting_payment','payment_verified','processing','packed','shipped','completed','cancelled','refund_requested','returned') NOT NULL DEFAULT 'waiting_payment',
  courier VARCHAR(100) DEFAULT NULL,
  tracking_number VARCHAR(100) DEFAULT NULL,
  notes TEXT,
  cancel_reason VARCHAR(255) DEFAULT NULL,
  refund_reason VARCHAR(255) DEFAULT NULL,
  order_token VARCHAR(64) DEFAULT NULL COMMENT 'Idempotency token untuk mencegah double order',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_orders_address FOREIGN KEY (address_id) REFERENCES addresses(id) ON DELETE SET NULL,
  CONSTRAINT fk_orders_bank_account FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id) ON DELETE SET NULL,
  INDEX idx_orders_user (user_id),
  INDEX idx_orders_status (order_status),
  INDEX idx_orders_payment_status (payment_status),
  INDEX idx_orders_number (order_number),
  UNIQUE KEY uniq_order_token (order_token)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabel: order_items  (menyimpan snapshot produk saat order dibuat)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS order_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED DEFAULT NULL,
  product_name VARCHAR(200) NOT NULL,
  sku VARCHAR(50) NOT NULL,
  price DECIMAL(12,2) NOT NULL,
  quantity INT UNSIGNED NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL,
  CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
  INDEX idx_order_items_order (order_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabel: payments
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  transaction_id VARCHAR(50) NOT NULL UNIQUE,
  payment_method ENUM('qris','transfer','cod','midtrans') NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  status ENUM('unpaid','waiting_verification','paid','failed','expired','cod') NOT NULL DEFAULT 'unpaid',
  payment_proof VARCHAR(255) DEFAULT NULL,
  snap_token VARCHAR(100) DEFAULT NULL,
  gateway_payload TEXT DEFAULT NULL,
  verified_by INT UNSIGNED DEFAULT NULL,
  verified_at DATETIME DEFAULT NULL,
  expires_at DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_payments_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_payments_verified_by FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_payments_order (order_id),
  INDEX idx_payments_status (status)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabel: reviews
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS reviews (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  order_id INT UNSIGNED NOT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  review TEXT,
  status ENUM('visible','hidden') NOT NULL DEFAULT 'visible',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_reviews_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_reviews_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5),
  UNIQUE KEY uniq_review_per_order_product (order_id, product_id, user_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabel: coupons & coupon_usages
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS coupons (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL UNIQUE,
  type ENUM('percentage','fixed') NOT NULL DEFAULT 'percentage',
  value DECIMAL(12,2) NOT NULL,
  minimum_purchase DECIMAL(12,2) NOT NULL DEFAULT 0,
  maximum_discount DECIMAL(12,2) DEFAULT NULL,
  usage_limit INT UNSIGNED DEFAULT NULL,
  used_count INT UNSIGNED NOT NULL DEFAULT 0,
  start_date DATE DEFAULT NULL,
  end_date DATE DEFAULT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS coupon_usages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  coupon_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  order_id INT UNSIGNED NOT NULL,
  discount_amount DECIMAL(12,2) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_coupon_usages_coupon FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
  CONSTRAINT fk_coupon_usages_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_coupon_usages_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabel: notifications
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  message TEXT NOT NULL,
  type VARCHAR(50) NOT NULL DEFAULT 'info',
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_notifications_user (user_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabel: banners
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS banners (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  subtitle VARCHAR(255) DEFAULT NULL,
  image VARCHAR(255) DEFAULT NULL,
  button_text VARCHAR(100) DEFAULT NULL,
  button_url VARCHAR(255) DEFAULT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabel: settings
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(100) NOT NULL UNIQUE,
  setting_value TEXT,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabel: activity_logs
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS activity_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED DEFAULT NULL,
  action VARCHAR(100) NOT NULL,
  description TEXT,
  ip_address VARCHAR(45) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_activity_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_activity_logs_user (user_id),
  INDEX idx_activity_logs_action (action)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
