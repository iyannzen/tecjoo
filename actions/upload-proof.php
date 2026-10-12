<?php
/**
 * TechZone - Proses Upload Bukti Transfer (backend)
 *
 * Validasi ketat: ekstensi & MIME type whitelist, ukuran maksimal, dan
 * nama file di-generate ulang (bukan nama asli) agar tidak bisa dipakai
 * untuk upload file executable. Folder uploads/payments/ juga sudah
 * diproteksi via .htaccess agar PHP tidak bisa dieksekusi di sana.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/payment_helpers.php';
require_once __DIR__ . '/../includes/automation_helpers.php';

requireUserLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('account/orders.php');
}

verifyCsrfToken($_POST['csrf_token'] ?? null);

$userId      = (int) $_SESSION['user_id'];
$orderNumber = trim($_POST['order_number'] ?? '');

if ($orderNumber === '') {
    redirectTo('account/orders.php');
}

$pdo = getDbConnection();

$orderStmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = :num AND user_id = :uid AND payment_method = 'transfer' LIMIT 1");
$orderStmt->execute([':num' => $orderNumber, ':uid' => $userId]);
$order = $orderStmt->fetch();

if (!$order) {
    setFlashMessage('error', 'Pesanan tidak ditemukan.');
    redirectTo('account/orders.php');
}

$paymentStmt = $pdo->prepare('SELECT * FROM payments WHERE order_id = :id ORDER BY id DESC LIMIT 1');
$paymentStmt->execute([':id' => $order['id']]);
$payment = $paymentStmt->fetch();

if ($payment['status'] !== 'unpaid') {
    setFlashMessage('error', 'Status pembayaran ini sudah tidak dapat diproses ulang.');
    redirectTo('payment/transfer.php?order=' . urlencode($orderNumber));
}

// ---------------------------------------------------------------------
// Validasi file upload
// ---------------------------------------------------------------------
if (empty($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
    setFlashMessage('error', 'Silakan unggah file bukti transfer yang valid.');
    redirectTo('payment/transfer.php?order=' . urlencode($orderNumber));
}

$file = $_FILES['payment_proof'];

$maxSizeBytes = 2 * 1024 * 1024; // 2MB
if ($file['size'] > $maxSizeBytes) {
    setFlashMessage('error', 'Ukuran file maksimal 2MB.');
    redirectTo('payment/transfer.php?order=' . urlencode($orderNumber));
}

$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($extension, getAllowedProofExtensions(), true)) {
    setFlashMessage('error', 'Tipe file tidak didukung. Gunakan JPG, PNG, atau PDF.');
    redirectTo('payment/transfer.php?order=' . urlencode($orderNumber));
}

$finfo    = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, getAllowedProofMimeTypes(), true)) {
    setFlashMessage('error', 'Isi file tidak sesuai dengan ekstensi yang diizinkan.');
    redirectTo('payment/transfer.php?order=' . urlencode($orderNumber));
}

// Nama file BARU (bukan nama asli) untuk mencegah path traversal / upload berbahaya.
$newFilename = 'proof_' . $order['order_number'] . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
$destination = __DIR__ . '/../uploads/payments/' . $newFilename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    setFlashMessage('error', 'Gagal menyimpan file. Silakan coba lagi.');
    redirectTo('payment/transfer.php?order=' . urlencode($orderNumber));
}

try {
    $pdo->beginTransaction();

    $pdo->prepare("UPDATE payments SET status = 'waiting_verification', payment_proof = :proof, updated_at = NOW() WHERE id = :id")
        ->execute([':proof' => $newFilename, ':id' => $payment['id']]);
    $pdo->prepare("UPDATE orders SET payment_status = 'waiting_verification', updated_at = NOW() WHERE id = :id")
        ->execute([':id' => $order['id']]);

    $pdo->commit();

    notifyAdmins($pdo, 'Pembayaran Masuk', 'Bukti transfer untuk pesanan ' . $orderNumber . ' menunggu verifikasi Anda.', 'payment');
    logActivity($userId, 'upload_payment_proof', 'Upload bukti transfer untuk order ' . $orderNumber . '.');
    setFlashMessage('success', 'Bukti transfer berhasil diunggah. Menunggu verifikasi admin.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    @unlink($destination);
    error_log('[TechZone][Upload Proof Error] ' . $e->getMessage());
    setFlashMessage('error', 'Terjadi kesalahan. Silakan coba kembali.');
}

redirectTo('payment/transfer.php?order=' . urlencode($orderNumber));
