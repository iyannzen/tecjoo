-- =======================================================================
-- TechZone - Migrasi tambahan: Logo gambar untuk rekening bank
-- Jalankan file ini HANYA jika Anda SUDAH menjalankan
-- database/migration_cancel_refund.sql sebelumnya (sehingga tabel
-- bank_accounts sudah ada tapi belum punya kolom logo_image).
-- Jika Anda baru migrasi dari awal, PAKAI migration_cancel_refund.sql
-- yang terbaru saja — kolom ini sudah termasuk di sana.
-- =======================================================================

ALTER TABLE bank_accounts
  ADD COLUMN logo_image VARCHAR(255) DEFAULT NULL COMMENT 'Path file logo yang diunggah admin, mis. uploads/banks/xxx.png' AFTER account_holder;
