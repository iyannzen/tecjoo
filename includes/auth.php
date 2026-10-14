<?php
/**
 * TechZone - Autentikasi & Proteksi Halaman User
 *
 * Include file ini di bagian paling atas halaman yang HANYA boleh
 * diakses oleh user yang sudah login (mis. account/*.php, checkout, dsb).
 */

require_once __DIR__ . '/functions.php';

/** Apakah ada user (bukan admin) yang sedang login? */
function isUserLoggedIn(): bool
{
    // Admin memakai mekanisme session yang sama (lihat loginAsUser()), jadi role
    // 'admin' harus tetap dianggap TIDAK login sebagai user biasa di sisi toko.
    return !empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') !== '' && ($_SESSION['role'] ?? '') !== 'admin';
}

/** Apakah admin sedang login? (didefinisikan di sini agar bisa dipakai admin/login.php
 *  tanpa memicu redirect otomatis dari admin_auth.php) */
function isAdminLoggedIn(): bool
{
    return !empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'admin';
}

/**
 * Ubah sebuah URL (mis. HTTP_REFERER) menjadi path RELATIF terhadap root situs
 * (contoh: "product-detail.php?slug=esp32"), supaya aman dipakai redirectTo().
 * URL dari domain lain atau yang tidak valid dikembalikan sebagai $fallback.
 */
function tzRelativeReturnPath(string $url, string $fallback = 'index.php'): string
{
    if ($url === '') {
        return $fallback;
    }
    $p = parse_url($url);
    if ($p === false) {
        return $fallback;
    }
    $host = strtolower($_SERVER['HTTP_HOST'] ?? '');
    if (!empty($p['host'])) {
        $urlHost = strtolower($p['host'] . (isset($p['port']) ? ':' . $p['port'] : ''));
        if ($urlHost !== $host && strtolower($p['host']) !== preg_replace('/:\d+$/', '', $host)) {
            return $fallback;
        }
    }
    $path     = $p['path'] ?? '/';
    $basePath = (string) (parse_url(techzoneBaseUrl(), PHP_URL_PATH) ?? '');
    if ($basePath !== '' && $basePath !== '/' && strpos($path, $basePath) === 0) {
        $path = substr($path, strlen($basePath));
    }
    $path = ltrim($path, '/');
    if ($path === '' || strpos($path, '..') !== false) {
        return $fallback;
    }
    if (!empty($p['query'])) {
        $path .= '?' . $p['query'];
    }
    return $path;
}

/** Wajibkan user login. Jika belum, redirect ke halaman login. */
function requireUserLogin(): void
{
    if (!isUserLoggedIn()) {
        // Untuk halaman yang dibuka langsung (GET), ingat tujuannya agar setelah login kembali ke sana.
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && !empty($_SERVER['REQUEST_URI'])) {
            $_SESSION['redirect_after_login'] = tzRelativeReturnPath($_SERVER['REQUEST_URI'], 'index.php');
        }
        setFlashMessage('error', 'Silakan masuk terlebih dahulu untuk melanjutkan.');
        redirectTo('login.php');
    }
}

/**
 * Wajibkan login untuk sebuah AKSI (tambah keranjang, wishlist, dsb).
 * Pengunjung yang belum login hanya boleh melihat-lihat; jika mencoba beraksi, ia diarahkan
 * ke halaman login dan setelah masuk dikembalikan ke halaman tempat ia tadi berada.
 */
function requireUserLoginForAction(string $message): void
{
    if (isUserLoggedIn()) {
        return;
    }
    $_SESSION['redirect_after_login'] = tzRelativeReturnPath($_SERVER['HTTP_REFERER'] ?? '', 'index.php');
    setFlashMessage('error', $message);
    redirectTo('login.php');
}

/** Ambil data user yang sedang login dari database (data terbaru, bukan dari session). */
function currentUser(): ?array
{
    if (!isUserLoggedIn()) {
        return null;
    }

    static $user = null;
    if ($user !== null) {
        return $user;
    }

    $pdo  = getDbConnection();
    $stmt = $pdo->prepare('SELECT id, name, email, phone, role, status, created_at FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;

    // Jika user dinonaktifkan admin setelah login, paksa logout.
    if ($user && $user['status'] !== 'active') {
        session_unset();
        session_destroy();
        redirectTo('login.php');
    }

    return $user;
}

/**
 * Melakukan login untuk sebuah baris user dari tabel `users`.
 * Meregenerasi session ID untuk mencegah session fixation.
 */
function loginAsUser(array $userRow): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $userRow['id'];
    $_SESSION['role']    = $userRow['role'];
    $_SESSION['name']    = $userRow['name'];
}

/** Logout user (dan admin) yang sedang login, lalu hancurkan session. */
function logoutCurrentSession(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}