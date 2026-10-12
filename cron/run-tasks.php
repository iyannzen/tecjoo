<?php
/**
 * TechZone - Jalankan tugas berkala secara manual / terjadwal (opsional).
 *
 * Situs sudah menjalankan tugas ini otomatis (maks. 1x / 5 menit) saat ada pengunjung.
 * Bila ingin lebih presisi (mis. toko sepi pengunjung), jadwalkan file ini:
 *   Linux (cron, tiap 10 menit):  *\/10 * * * * php /path/ke/techzone/cron/run-tasks.php
 *   Windows (Task Scheduler):     php C:\xampp\htdocs\techzone\cron\run-tasks.php
 * Hanya bisa dijalankan dari command line.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/../includes/automation_helpers.php';

$result = runScheduledTasks(null, true);
echo '[' . date('Y-m-d H:i:s') . '] Selesai. Dibatalkan otomatis: ' . ($result['expired'] ?? 0)
   . ', pengingat keranjang: ' . ($result['reminded'] ?? 0) . PHP_EOL;
