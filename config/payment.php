<?php
/**
 * TechZone - Konfigurasi Pembayaran (Simulasi Lokal)
 *
 * Metode pembayaran:
 *   - midtrans : Payment gateway Midtrans Snap (VA, QRIS, e-wallet, kartu) -- otomatis
 *                lunas via webhook (lihat payment/midtrans.php & midtrans-notify.php)
 *   - qris / transfer / cod : alur manual (diverifikasi admin) -- lihat actions/payment.php
 *
 * Nilai-nilai pembayaran (nomor rekening, gambar QRIS, ongkir, dsb)
 * disimpan di tabel `settings` agar dapat diubah admin tanpa mengedit
 * kode. Fungsi berikut menyediakan default fallback jika baris setting
 * belum ada di database.
 */

require_once __DIR__ . '/../includes/functions.php';

/**
 * Mengambil seluruh konfigurasi pembayaran dari tabel settings,
 * dengan nilai default sebagai fallback.
 */
function getPaymentConfig(): array
{
    $defaults = [
        'bank_name'                     => 'BCA',
        'bank_account_number'           => '1234567890',
        'bank_account_holder'           => 'TechZone',
        'qris_image'                    => 'assets/images/qris-sample.png',
        'shipping_cost_reguler'         => '15000',
        'shipping_cost_express'         => '30000',
        'payment_qris_active'           => '1',
        'payment_transfer_active'       => '1',
        'payment_cod_active'            => '1',
        'payment_midtrans_active'       => '1',
        'qris_payment_duration_minutes' => '15',
    ];

    $settings = getAllSettings();

    return array_merge($defaults, array_intersect_key($settings, $defaults));
}

/** Daftar metode pembayaran yang valid pada sistem (whitelist). */
function getValidPaymentMethods(): array
{
    return ['qris', 'transfer', 'cod', 'midtrans'];
}

/** Daftar status pembayaran yang valid pada sistem (whitelist). */
function getValidPaymentStatuses(): array
{
    return ['unpaid', 'waiting_verification', 'paid', 'failed', 'expired', 'cod'];
}

/** Daftar status pesanan yang valid pada sistem (whitelist). */
function getValidOrderStatuses(): array
{
    return [
        'waiting_payment', 'payment_verified', 'processing',
        'packed', 'shipped', 'completed', 'cancelled', 'refund_requested', 'returned',
    ];
}

/** Label Bahasa Indonesia untuk status pembayaran. Untuk COD yang sudah lunas, tampilkan "Dibayar di Tempat". */
function paymentStatusLabel(string $status, string $paymentMethod = ''): string
{
    if ($status === 'paid' && $paymentMethod === 'cod') {
        return 'Dibayar di Tempat';
    }

    $labels = [
        'unpaid'                => 'Menunggu Pembayaran',
        'waiting_verification'  => 'Menunggu Verifikasi',
        'paid'                  => 'Lunas',
        'failed'                => 'Gagal',
        'expired'               => 'Kadaluarsa',
        'cod'                   => 'COD',
    ];

    return $labels[$status] ?? $status;
}

/** Ambil daftar rekening bank tujuan transfer yang aktif, diurutkan sesuai sort_order admin. */
function getActiveBankAccounts(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT * FROM bank_accounts WHERE is_active = 1 ORDER BY sort_order ASC, id ASC');
    return $stmt->fetchAll();
}

/** Ambil satu rekening bank berdasarkan ID (dipakai saat checkout & tampilan instruksi transfer). */
function getBankAccountById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM bank_accounts WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/** Label Bahasa Indonesia untuk status pesanan. */
function orderStatusLabel(string $status): string
{
    $labels = [
        'waiting_payment'   => 'Menunggu Pembayaran',
        'payment_verified'  => 'Pembayaran Diverifikasi',
        'processing'        => 'Diproses',
        'packed'            => 'Dikemas',
        'shipped'           => 'Dikirim',
        'completed'         => 'Selesai',
        'cancelled'         => 'Dibatalkan',
        'refund_requested'  => 'Pengembalian Diajukan',
        'returned'          => 'Dana Dikembalikan',
    ];

    return $labels[$status] ?? $status;
}

/** Kelas warna badge Tailwind untuk tiap status pesanan (dipakai bersama orderStatusLabel). */
function orderStatusBadgeClass(string $status): string
{
    $classes = [
        'waiting_payment'   => 'bg-amber-50 text-amber-700',
        'payment_verified'  => 'bg-cyan-50 text-cyan-700',
        'processing'        => 'bg-blue-50 text-blue-700',
        'packed'            => 'bg-indigo-50 text-indigo-700',
        'shipped'           => 'bg-purple-50 text-purple-700',
        'completed'         => 'bg-emerald-50 text-emerald-700',
        'cancelled'         => 'bg-gray-100 text-gray-500',
        'refund_requested'  => 'bg-orange-50 text-orange-700',
        'returned'          => 'bg-red-50 text-red-600',
    ];

    return $classes[$status] ?? 'bg-gray-100 text-gray-600';
}
