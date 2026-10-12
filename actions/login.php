<?php
/**
 * TechZone - Proses Login User (backend)
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/cart_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('login.php');
}

verifyCsrfToken($_POST['csrf_token'] ?? null);

$rateLimitKey = 'login_attempts_user';

if (!isLoginAttemptAllowed($rateLimitKey)) {
    $_SESSION['login_errors'] = ['Terlalu banyak percobaan login gagal. Silakan coba lagi dalam beberapa menit.'];
    redirectTo('login.php');
}

$email    = trim(strtolower($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    $_SESSION['login_errors'] = ['Email dan password wajib diisi.'];
    redirectTo('login.php');
}

$pdo  = getDbConnection();
$stmt = $pdo->prepare("SELECT id, name, email, password, role, status FROM users WHERE email = :email LIMIT 1");
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    recordFailedLoginAttempt($rateLimitKey);
    logActivity(null, 'login_failed', 'Percobaan login gagal untuk email: ' . $email);

    $_SESSION['login_errors'] = ['Email atau password yang Anda masukkan salah.'];
    redirectTo('login.php');
}

if ($user['status'] !== 'active') {
    $_SESSION['login_errors'] = ['Akun Anda saat ini dinonaktifkan. Silakan hubungi admin TechZone.'];
    redirectTo('login.php');
}

resetLoginAttempts($rateLimitKey);

$guestSessionId = session_id();
loginAsUser($user);
mergeGuestCartIntoUser($pdo, $guestSessionId, (int) $user['id']);

logActivity((int) $user['id'], 'login', 'User berhasil login.');

setFlashMessage('success', 'Selamat datang kembali, ' . $user['name'] . '!');

// Redirect ke halaman tujuan sebelumnya jika ada (mis. setelah diarahkan dari checkout).
$redirectAfter = $_SESSION['redirect_after_login'] ?? 'index.php';
unset($_SESSION['redirect_after_login']);
redirectTo($redirectAfter);
