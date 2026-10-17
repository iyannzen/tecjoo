-- TechZone - Migrasi fitur Chat produk (OPSIONAL)
-- Kedua tabel ini dibuat OTOMATIS oleh aplikasi saat fitur chat pertama kali dibuka
-- (user menekan tombol "Chat" di produk, atau admin membuka Admin > Chat).
-- Jalankan file ini hanya jika pembuatan otomatis gagal (mis. user database tidak punya hak CREATE).

CREATE TABLE IF NOT EXISTS chat_threads (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  user_unread INT UNSIGNED NOT NULL DEFAULT 0,
  admin_unread INT UNSIGNED NOT NULL DEFAULT 0,
  last_sender VARCHAR(10) DEFAULT NULL,
  last_preview VARCHAR(190) DEFAULT NULL,
  last_message_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_chat_user_product (user_id, product_id),
  INDEX idx_chat_last (last_message_at),
  INDEX idx_chat_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS chat_messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  thread_id INT UNSIGNED NOT NULL,
  sender VARCHAR(10) NOT NULL,          -- user | admin | bot
  sender_id INT UNSIGNED DEFAULT NULL,
  type VARCHAR(10) NOT NULL DEFAULT 'text',   -- text | product
  body TEXT DEFAULT NULL,
  product_id INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_chatmsg_thread (thread_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
