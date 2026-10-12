<?php
/**
 * TechZone - Angka pemberitahuan admin (dipanggil berkala oleh sidebar/topbar admin).
 * GET -> {"notifications":N,"orders":N,"payments":N,"stock":N}. Hanya untuk admin yang sedang login.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/automation_helpers.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!isAdminLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

$adminId = (int) ($_SESSION['user_id'] ?? 0);
session_write_close(); // jangan menahan request lain milik sesi yang sama

try {
    echo json_encode(tzAdminBadgeCounts(getDbConnection(), $adminId));
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server']);
}
