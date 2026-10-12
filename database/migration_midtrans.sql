-- TechZone - Migrasi Midtrans (jalankan sekali)
ALTER TABLE orders   MODIFY payment_method ENUM('qris','transfer','cod','midtrans') NOT NULL;
ALTER TABLE payments MODIFY payment_method ENUM('qris','transfer','cod','midtrans') NOT NULL;
ALTER TABLE payments ADD COLUMN snap_token VARCHAR(100) DEFAULT NULL,
                     ADD COLUMN gateway_payload TEXT DEFAULT NULL;
