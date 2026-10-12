<?php
/**
 * TechZone - Webhook Notifikasi Midtrans (HTTP Notification)
 *
 * Daftarkan URL ini di Dashboard Midtrans:
 *   Settings -> Configuration -> Payment Notification URL
 *   https://DOMAIN-ANDA/payment/midtrans-notify.php
 *
 * Satu-satunya jalur "resmi" yang mengubah pesanan menjadi LUNAS (bersama sinkronisasi
 * status langsung ke server Midtrans). Callback di browser TIDAK dipercaya.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/midtrans_helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$n   = json_decode($raw ?: '', true);
if (!is_array($n)) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid payload']);
    exit;
}

// 1. Verifikasi signature (SHA512 dari order_id + status_code + gross_amount + ServerKey)
if (!midtransVerifySignature($n)) {
    error_log('[TechZone][Midtrans Webhook] Signature tidak valid untuk order ' . ($n['order_id'] ?? '?'));
    http_response_code(403);
    echo json_encode(['message' => 'Invalid signature']);
    exit;
}

// 2. Terapkan status (cek nominal + idempoten di dalamnya)
$pdo    = getDbConnection();
$result = midtransApplyStatus($pdo, $n);

// Order tidak dikenal (mis. tombol "Test Notification" di dashboard) -> tetap 200 agar tidak di-retry.
if ($result === 'amount_mismatch') {
    http_response_code(400);
}
echo json_encode(['message' => 'OK', 'result' => $result]);
