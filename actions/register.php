<?php
/**
 * TechZone - Proses Registrasi User (backend)
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/cart_helpers.php';
require_once __DIR__ . '/../includes/automation_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('register.php');
}

verifyCsrfToken($_POST['csrf_token'] ?? null);

$name     = trim($_POST['name'] ?? '');
$email    = trim(strtolower($_POST['email'] ?? ''));
$phone    = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$confirm  = $_POST['password_confirmation'] ?? '';

$errors = [];

if ($name === '' || mb_strlen($name) < 3) {
    $errors[] = 'Nama lengkap minimal 3 karakter.';
}
if (!isValidEmail($email)) {
    $errors[] = 'Format email tidak valid.';
}
if ($phone === '' || !preg_match('/^[0-9+\-\s]{8,20}$/', $phone)) {
    $errors[] = 'Nomor HP tidak valid.';
}
if (!isPasswordStrongEnough($password)) {
    $errors[] = 'Password minimal 8 karakter dan mengandung huruf serta angka.';
}
if ($password !== $confirm) {
    $errors[] = 'Konfirmasi password tidak sama.';
}

if (!empty($errors)) {
    $_SESSION['register_errors'] = $errors;
    $_SESSION['register_old']    = ['name' => $name, 'email' => $email, 'phone' => $phone];
    redirectTo('register.php');
}

$pdo = getDbConnection();

// Pastikan email belum terdaftar.
$check = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
$check->execute([':email' => $email]);

if ($check->fetch()) {
    $_SESSION['register_errors'] = ['Email sudah terdaftar. Silakan gunakan email lain atau masuk.'];
    $_SESSION['register_old']    = ['name' => $name, 'email' => $email, 'phone' => $phone];
    redirectTo('register.php');
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare(
    "INSERT INTO users (name, email, phone, password, role, status, created_at)
     VALUES (:name, :email, :phone, :password, 'user', 'active', NOW())"
);
$stmt->execute([
    ':name'     => $name,
    ':email'    => $email,
    ':phone'    => $phone,
    ':password' => $hashedPassword,
]);

$newUserId = (int) $pdo->lastInsertId();

logActivity($newUserId, 'register', 'User baru mendaftar melalui formulir registrasi.');

// Notifikasi: admin tahu ada pengguna baru; pembeli mendapat sambutan.
notifyAdmins($pdo, 'Pengguna baru mendaftar', $name . ' (' . $email . ') baru saja membuat akun.', 'user', 'admin/users.php?q=' . rawurlencode($email));
createNotification($pdo, $newUserId, 'Selamat datang di TechZone', 'Akun Anda sudah aktif. Yuk jelajahi produk IoT pilihan kami.', 'info', 'products.php');

// Langsung login-kan user setelah berhasil daftar.
$guestSessionId = session_id();
loginAsUser([
    'id'   => $newUserId,
    'role' => 'user',
    'name' => $name,
]);
mergeGuestCartIntoUser($pdo, $guestSessionId, $newUserId);

setFlashMessage('success', 'Registrasi berhasil! Selamat datang di TechZone, ' . $name . '.');
redirectTo('index.php');
