<?php
/**
 * TechZone - Fungsi Umum / Bootstrap
 *
 * File ini menjadi titik masuk utama yang men-include konfigurasi dasar
 * (app config, koneksi database) dan menyediakan fungsi bantu yang
 * dipakai di banyak halaman.
 */

if (!defined('TECHZONE_APP')) {
    define('TECHZONE_APP', true);
}

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

techzoneStartSession();

/**
 * Redirect ke URL relatif terhadap base URL project, lalu hentikan eksekusi.
 */
function redirectTo(string $path): void
{
    $path = ltrim($path, '/');
    header('Location: ' . techzoneBaseUrl() . '/' . $path);
    exit;
}

/**
 * Escape output HTML untuk mencegah XSS.
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Format angka menjadi format Rupiah, contoh: Rp 289.000
 */
function formatRupiah($amount): string
{
    return 'Rp ' . number_format((float) $amount, 0, ',', '.');
}

/**
 * Format tanggal ke format Indonesia, contoh: 04 September 2026
 */
function formatTanggalIndo(string $datetime, bool $withTime = false): string
{
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    $ts = strtotime($datetime);
    if ($ts === false) {
        return $datetime;
    }

    $formatted = date('d', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);

    if ($withTime) {
        $formatted .= ', ' . date('H:i', $ts) . ' WIB';
    }

    return $formatted;
}

/**
 * Menghasilkan slug ramah-URL dari sebuah string.
 */
function makeSlug(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
    $text = trim($text, '-');
    return $text === '' ? bin2hex(random_bytes(4)) : $text;
}

/**
 * Mengambil seluruh baris tabel settings sebagai associative array (key => value).
 * Menggunakan cache statis per-request agar tidak query berulang.
 */
function getAllSettings(): array
{
    static $settings = null;

    if ($settings !== null) {
        return $settings;
    }

    $settings = [];

    try {
        $pdo  = getDbConnection();
        $stmt = $pdo->query('SELECT setting_key, setting_value FROM settings');
        foreach ($stmt->fetchAll() as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Throwable $e) {
        error_log('[TechZone][Settings Error] ' . $e->getMessage());
    }

    return $settings;
}

/**
 * Ambil satu nilai setting dengan fallback default.
 */
function getSetting(string $key, string $default = ''): string
{
    $settings = getAllSettings();
    return $settings[$key] ?? $default;
}

/**
 * Mencatat aktivitas penting ke tabel activity_logs (audit log).
 */
function logActivity(?int $userId, string $action, string $description = ''): void
{
    try {
        $pdo  = getDbConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO activity_logs (user_id, action, description, ip_address, created_at)
             VALUES (:user_id, :action, :description, :ip, NOW())'
        );
        $stmt->execute([
            ':user_id'     => $userId,
            ':action'      => $action,
            ':description' => $description,
            ':ip'          => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Throwable $e) {
        error_log('[TechZone][Activity Log Error] ' . $e->getMessage());
    }
}

/**
 * Menyimpan pesan flash (toast/alert) sekali-tampil ke session.
 */
function setFlashMessage(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Mengambil (dan menghapus) pesan flash dari session, jika ada.
 */
function getFlashMessage(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Validasi sederhana: apakah string berupa email yang valid.
 */
function isValidEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Render badge kecil bergaya "logo" (bukan logo asli/berhak cipta) untuk
 * menampilkan daftar e-wallet / bank yang didukung pada halaman pembayaran.
 * Menggunakan warna khas brand + inisial/nama singkat, murni CSS/teks.
 * Dipakai sebagai FALLBACK otomatis oleh renderPaymentLogoBox() jika file
 * logo belum ada, jadi tetap dipertahankan.
 */
function renderPaymentBrandBadge(string $name, string $bg, string $fg = '#ffffff'): string
{
    $label = e($name);
    return '<span class="inline-flex items-center justify-center h-8 px-3 rounded-lg text-[11px] font-extrabold tracking-wide shadow-sm ' .
        'transition-all duration-200 hover:scale-110 hover:shadow-md cursor-default select-none" ' .
        'style="background:' . e($bg) . ';color:' . e($fg) . ';">' . $label . '</span>';
}

/**
 * Render ikon "logo" kotak polos berwarna (fallback teks/inisial), TANPA
 * gambar. Dipakai secara langsung di tempat yang sengaja tidak perlu
 * gambar (mis. pratinjau admin) — untuk daftar metode pembayaran yang
 * dilihat pembeli, gunakan renderPaymentLogoBox() di bawah.
 */
function renderPaymentLogoIcon(string $mark, string $bg, string $fg = '#ffffff', string $size = 'w-10 h-10'): string
{
    return '<span class="' . $size . ' rounded-xl flex items-center justify-center font-extrabold shrink-0 shadow-sm ' .
        'text-[10px] tracking-tight leading-[1.1] text-center px-1 select-none" ' .
        'style="background:' . e($bg) . ';color:' . e($fg) . ';white-space:pre-line;">' . e($mark) . '</span>';
}

/**
 * Render KOTAK LOGO PEMBAYARAN (gaya app-icon persegi, putih, dengan
 * border tipis) yang mengambil gambar dari file ($imagePath, relatif
 * terhadap root situs, mis. "assets/images/payments/wallets/dana.png").
 *
 * Jika file gambar BELUM ada di server, otomatis "jatuh" ke badge warna
 * + inisial (renderPaymentLogoIcon) tanpa perlu ubah kode apapun —
 * begitu Anda menaruh file gambar di path yang sama, logo asli akan
 * langsung tampil menggantikan badge warna tadi.
 *
 * @param string      $imagePath Path relatif ke file gambar logo (boleh belum ada).
 * @param string      $mark      Inisial/teks fallback jika gambar belum ada.
 * @param string      $bg        Warna latar fallback.
 * @param string      $fg        Warna teks fallback.
 * @param string      $size      Kelas Tailwind ukuran kotak, mis. "w-11 h-11".
 * @param string|null $label     Nama brand untuk atribut alt (aksesibilitas).
 */
function renderPaymentLogoBox(string $imagePath, string $mark, string $bg, string $fg = '#ffffff', string $size = 'w-11 h-11', ?string $label = null): string
{
    $src  = e(techzoneBaseUrl() . '/' . ltrim($imagePath, '/'));
    $alt  = e($label ?? $mark);
    $fallbackId = 'lgf_' . substr(md5($imagePath . $mark . microtime()), 0, 10);

    $fallback = '<span id="' . $fallbackId . '" class="' . $size . ' rounded-xl items-center justify-center font-extrabold shrink-0 shadow-sm ' .
        'text-[10px] tracking-tight leading-[1.1] text-center px-1 select-none" ' .
        'style="display:none;background:' . e($bg) . ';color:' . e($fg) . ';white-space:pre-line;">' . e($mark) . '</span>';

    $img = '<img src="' . $src . '" alt="' . $alt . '" loading="lazy" ' .
        'class="' . $size . ' rounded-xl shrink-0 object-contain bg-white border border-gray-100 shadow-sm p-1.5 transition-transform duration-200 hover:scale-105" ' .
        'onerror="this.style.display=\'none\';document.getElementById(\'' . $fallbackId . '\').style.display=\'flex\';">';

    return $img . $fallback;
}

/** E-wallet bawaan: dipakai untuk mengisi tabel pertama kali & sebagai cadangan bila database tak terjangkau. */
function tzDefaultEwallets(): array
{
    return [
        // nama, warna latar, warna teks, inisial ('|' = baris baru)
        ['DANA',      '#1CA1E9', '#ffffff', 'DANA'],
        ['GoPay',     '#00AA5B', '#ffffff', 'Go|Pay'],
        ['OVO',       '#4C2A86', '#ffffff', 'OVO'],
        ['ShopeePay', '#EE4D2D', '#ffffff', 'SPay'],
        ['LinkAja',   '#E4032E', '#ffffff', 'Link|Aja'],
        ['Jenius',    '#EEF200', '#0a1128', 'Jenius'],
    ];
}

/**
 * Membuat tabel ewallet_channels (dikelola dari Admin > Rekening & E-Wallet) bila belum ada
 * dan mengisinya dengan e-wallet bawaan SEKALI SAJA (ditandai di tabel settings, jadi kalau admin
 * menghapus semuanya, daftar tidak muncul lagi sendiri).
 * PENTING: panggil di luar transaksi (perintah DDL di MySQL memaksa commit).
 */
function tzEnsureEwalletTable(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    try {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS ewallet_channels (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(50) NOT NULL,
                mark VARCHAR(20) NOT NULL DEFAULT \'\',
                logo_image VARCHAR(255) DEFAULT NULL,
                logo_bg VARCHAR(30) NOT NULL DEFAULT \'#1CA1E9\',
                logo_fg VARCHAR(20) NOT NULL DEFAULT \'#ffffff\',
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                sort_order INT UNSIGNED NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_ewallet_name (name),
                INDEX idx_ewallet_active (is_active)
            ) ENGINE=InnoDB'
        );

        $flag = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'ewallet_seeded' LIMIT 1")->fetchColumn();
        if ($flag === false || $flag === null || $flag === '') {
            if ((int) $pdo->query('SELECT COUNT(*) FROM ewallet_channels')->fetchColumn() === 0) {
                $ins = $pdo->prepare('INSERT INTO ewallet_channels (name, mark, logo_bg, logo_fg, is_active, sort_order) VALUES (:n, :m, :bg, :fg, 1, :s)');
                foreach (tzDefaultEwallets() as $i => [$name, $bg, $fg, $mark]) {
                    $ins->execute([':n' => $name, ':m' => $mark, ':bg' => $bg, ':fg' => $fg, ':s' => $i + 1]);
                }
            }
            $pdo->exec("INSERT INTO settings (setting_key, setting_value) VALUES ('ewallet_seeded', '1') ON DUPLICATE KEY UPDATE setting_value = '1'");
        }
    } catch (Throwable $e) {
        error_log('[TechZone][Ewallet Table] ' . $e->getMessage());
    }
}

/**
 * Daftar e-wallet untuk checkout & halaman pembayaran.
 * Dikelola admin (Admin > Rekening & E-Wallet > E-Wallet). Bentuk tiap item tetap:
 * [nama, warna latar, warna teks, inisial, path logo]. Hanya yang AKTIF, kecuali $onlyActive = false.
 * Bila admin belum mengunggah logo, dipakai file assets/images/payments/wallets/<nama>.png (jika ada).
 */
function paymentEwalletBrands(bool $onlyActive = true): array
{
    static $cache = [];
    $key = $onlyActive ? 'active' : 'all';
    if (isset($cache[$key])) {
        return $cache[$key];
    }

    $out  = [];
    $rows = null;
    try {
        $pdo = getDbConnection();
        tzEnsureEwalletTable($pdo);
        $sql  = 'SELECT name, mark, logo_image, logo_bg, logo_fg FROM ewallet_channels'
              . ($onlyActive ? ' WHERE is_active = 1' : '') . ' ORDER BY sort_order ASC, id ASC';
        $rows = $pdo->query($sql)->fetchAll();
    } catch (Throwable $e) {
        $rows = null;
    }

    if ($rows === null) { // database tak terjangkau -> pakai daftar bawaan
        $rows = [];
        foreach (tzDefaultEwallets() as [$name, $bg, $fg, $mark]) {
            $rows[] = ['name' => $name, 'mark' => $mark, 'logo_image' => null, 'logo_bg' => $bg, 'logo_fg' => $fg];
        }
    }

    foreach ($rows as $r) {
        $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($r['name'])), '-');
        $img  = !empty($r['logo_image']) ? $r['logo_image'] : 'assets/images/payments/wallets/' . $slug . '.png';
        $mark = str_replace('|', "\n", (string) ($r['mark'] !== '' ? $r['mark'] : mb_strtoupper(mb_substr($r['name'], 0, 4))));
        $out[] = [$r['name'], $r['logo_bg'], $r['logo_fg'], $mark, $img];
    }
    return $cache[$key] = $out;
}

/** Daftar bank besar di Indonesia: nama, warna+inisial fallback, dan path file logo (Anda isi sendiri). */
function paymentBankBrands(): array
{
    return [
        ['BCA', '#005AAB', '#ffffff', 'BCA', 'assets/images/payments/banks/bca.png'],
        ['BRI', '#00529C', '#ffffff', 'BRI', 'assets/images/payments/banks/bri.png'],
        ['BNI', '#F37021', '#ffffff', 'BNI', 'assets/images/payments/banks/bni.png'],
        ['Mandiri', '#003D79', '#FFC600', "Man\ndiri", 'assets/images/payments/banks/mandiri.png'],
        ['BSI', '#00A651', '#ffffff', 'BSI', 'assets/images/payments/banks/bsi.png'],
        ['BTN', '#1E4B9B', '#ffffff', 'BTN', 'assets/images/payments/banks/btn.png'],
        ['CIMB Niaga', '#7A1315', '#ffffff', 'CIMB', 'assets/images/payments/banks/cimb-niaga.png'],
        ['Permata', '#00A99D', '#ffffff', "Perm\nata", 'assets/images/payments/banks/permata.png'],
        ['Danamon', '#F7941D', '#ffffff', "Dana\nmon", 'assets/images/payments/banks/danamon.png'],
        ['SeaBank', '#F26A21', '#ffffff', 'Sea', 'assets/images/payments/banks/seabank.png'],
        ['Maybank', '#FFC72C', '#0a1128', "May\nbank", 'assets/images/payments/banks/maybank.png'],
        ['Bank Panin', '#00338D', '#ffffff', 'Panin', 'assets/images/payments/banks/panin.png'],
        ['UOB Indonesia', '#E4032E', '#ffffff', 'UOB', 'assets/images/payments/banks/uob.png'],
        ['OCBC NISP', '#E4032E', '#ffffff', 'OCBC', 'assets/images/payments/banks/ocbc-nisp.png'],
        ['Bank Mega', '#003DA5', '#FDB913', 'Mega', 'assets/images/payments/banks/mega.png'],
        ['Sinarmas', '#ED1C24', '#ffffff', "Sinar\nmas", 'assets/images/payments/banks/sinarmas.png'],
        ['Bank Nagari', '#004C97', '#ffffff', 'Nagari', 'assets/images/payments/banks/nagari.png'],
    ];
}

/** Validasi nama channel (e-wallet/bank) yang dipilih user agar tidak sembarang string yang tersimpan. */
function isValidPaymentChannel(string $name, string $method): bool
{
    $list = $method === 'transfer' ? paymentBankBrands() : paymentEwalletBrands();
    foreach ($list as [$bn]) {
        if ($bn === $name) {
            return true;
        }
    }
    return false;
}

/**
 * Render hero banner kecil (navy, animasi blob + grid pattern) untuk halaman
 * statis seperti About, Contact, FAQ, Kebijakan, dsb agar tidak terasa polos.
 *
 * @param string $title    Judul utama halaman
 * @param string $subtitle Sub-judul / deskripsi singkat
 * @param string $icon     Kelas Font Awesome, mis. 'fa-circle-info'
 */
function renderPageHero(string $title, string $subtitle = '', string $icon = 'fa-microchip'): void
{
    ?>
    <section class="relative bg-navy-900 overflow-hidden">
      <div class="absolute inset-0 grid-pattern opacity-30"></div>
      <div class="absolute -top-16 -left-16 w-64 h-64 bg-cyan-500/20 rounded-full blur-3xl animate-blob"></div>
      <div class="absolute -bottom-16 -right-10 w-64 h-64 bg-blue-600/20 rounded-full blur-3xl animate-blob" style="animation-delay:3s"></div>
      <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-14 md:py-16 text-center">
        <div class="animate-fade-in-up w-14 h-14 mx-auto rounded-2xl bg-gradient-to-br from-cyan-400 to-blue-600 flex items-center justify-center mb-4 shadow-glow animate-float">
          <i class="fa-solid <?= e($icon) ?> text-white text-xl"></i>
        </div>
        <h1 class="animate-fade-in-up delay-100 text-2xl sm:text-3xl font-extrabold text-white"><?= e($title) ?></h1>
        <?php if ($subtitle !== ''): ?>
          <p class="animate-fade-in-up delay-200 text-gray-300 text-sm sm:text-base mt-3 max-w-2xl mx-auto"><?= e($subtitle) ?></p>
        <?php endif; ?>
      </div>
      <svg class="relative block w-full text-gray-50" viewBox="0 0 1440 32" fill="currentColor" preserveAspectRatio="none" style="height:24px">
        <path d="M0,32 C240,0 480,32 720,16 C960,0 1200,28 1440,8 L1440,32 L0,32 Z"></path>
      </svg>
    </section>
    <?php

    /**
 * Mendapatkan base URL aplikasi secara dinamis (mendukung HTTPS Railway).
 */
function techzoneBaseUrl(): string
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
                (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') 
                ? 'https://' : 'http://';
                
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    return $protocol . $host;
}
}
