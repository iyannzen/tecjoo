<?php
/**
 * TechZone - Helper Midtrans (Snap + Core API)
 *
 * Fungsi utama:
 *   midtransCreateSnapToken()   -> minta Snap token ke Midtrans
 *   midtransGetStatus()         -> cek status transaksi (GET /v2/{order_id}/status)
 *   midtransCancelTransaction() -> batalkan transaksi pending
 *   midtransApplyStatus()       -> terapkan status Midtrans ke order/payment di database
 *                                  (dipakai webhook, halaman bayar, pembatalan & expire)
 */

require_once __DIR__ . '/../config/midtrans.php';
require_once __DIR__ . '/notification_helpers.php';
require_once __DIR__ . '/automation_helpers.php';

/** Request HTTP ke Midtrans. @return array [httpCode, decodedBody|null] */
function midtransRequest(string $method, string $url, ?array $body = null): array
{
    $ch = curl_init($url);
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode(MIDTRANS_SERVER_KEY . ':'),
    ];
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 25,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    $raw  = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($raw === false) {
        error_log('[TechZone][Midtrans] cURL error: ' . curl_error($ch));
    }
    curl_close($ch);

    $decoded = is_string($raw) ? json_decode($raw, true) : null;
    return [$code, is_array($decoded) ? $decoded : null];
}

/**
 * Buat Snap token untuk sebuah order.
 * @return array ['token' => string|null, 'error' => string|null]
 */
function midtransCreateSnapToken(PDO $pdo, array $order, array $user): array
{
    if (!midtransIsConfigured()) {
        return ['token' => null, 'error' => 'Key Midtrans belum dikonfigurasi.'];
    }

    $gross = (int) round((float) $order['total']);
    if ($gross < 1) {
        return ['token' => null, 'error' => 'Total pembayaran tidak valid.'];
    }

    // item_details (jumlahnya HARUS sama dengan gross_amount, jika tidak Midtrans menolak).
    $items = [];
    $st = $pdo->prepare('SELECT product_id, product_name, price, quantity FROM order_items WHERE order_id = :id');
    $st->execute([':id' => $order['id']]);
    foreach ($st->fetchAll() as $it) {
        $items[] = [
            'id'       => 'P' . (int) $it['product_id'],
            'price'    => (int) round((float) $it['price']),
            'quantity' => (int) $it['quantity'],
            'name'     => mb_substr((string) $it['product_name'], 0, 50),
        ];
    }
    if ((float) $order['shipping_cost'] > 0) {
        $items[] = ['id' => 'SHIPPING', 'price' => (int) round((float) $order['shipping_cost']), 'quantity' => 1, 'name' => 'Ongkos Kirim'];
    }
    if ((float) $order['discount'] > 0) {
        $items[] = ['id' => 'DISCOUNT', 'price' => -1 * (int) round((float) $order['discount']), 'quantity' => 1, 'name' => 'Diskon'];
    }
    $sum = 0;
    foreach ($items as $i) {
        $sum += $i['price'] * $i['quantity'];
    }

    $nameParts = preg_split('/\s+/', trim((string) $order['recipient_name']), 2);
    $customer = [
        'first_name' => mb_substr($nameParts[0] ?? 'Pelanggan', 0, 50),
        'last_name'  => mb_substr($nameParts[1] ?? '', 0, 50),
        'email'      => $user['email'] ?? '',
        'phone'      => mb_substr(preg_replace('/[^0-9+]/', '', (string) $order['recipient_phone']), 0, 19),
    ];

    $hours = tzPaymentExpiryHours();
    $payload = [
        'transaction_details' => [
            'order_id'     => $order['order_number'],
            'gross_amount' => $gross,
        ],
        'customer_details' => $customer,
        'expiry'           => ['unit' => 'hours', 'duration' => max(1, $hours)],
        'callbacks'        => ['finish' => techzoneBaseUrl() . '/payment/midtrans.php?order=' . urlencode($order['order_number'])],
    ];
    if ($sum === $gross && !empty($items)) {
        $payload['item_details'] = $items;
    }

    [$code, $res] = midtransRequest('POST', MIDTRANS_SNAP_URL, $payload);
    if (in_array($code, [200, 201], true) && !empty($res['token'])) {
        return ['token' => $res['token'], 'error' => null];
    }

    error_log('[TechZone][Midtrans] Gagal membuat token (HTTP ' . $code . '): ' . json_encode($res));
    $msg = $res['error_messages'][0] ?? ($res['status_message'] ?? 'Gagal menghubungi Midtrans.');
    return ['token' => null, 'error' => (string) $msg];
}

/** Cek status transaksi di Midtrans. @return array [httpCode, body|null] */
function midtransGetStatus(string $orderNumber): array
{
    return midtransRequest('GET', MIDTRANS_API_URL . '/v2/' . rawurlencode($orderNumber) . '/status');
}

/** Batalkan transaksi pending di Midtrans. @return array [httpCode, body|null] */
function midtransCancelTransaction(string $orderNumber): array
{
    return midtransRequest('POST', MIDTRANS_API_URL . '/v2/' . rawurlencode($orderNumber) . '/cancel');
}

/** Verifikasi signature_key dari notifikasi Midtrans. */
function midtransVerifySignature(array $n): bool
{
    if (empty($n['order_id']) || empty($n['status_code']) || !isset($n['gross_amount']) || empty($n['signature_key'])) {
        return false;
    }
    $expected = hash('sha512', $n['order_id'] . $n['status_code'] . $n['gross_amount'] . MIDTRANS_SERVER_KEY);
    return hash_equals($expected, (string) $n['signature_key']);
}

/** Label channel yang enak dibaca dari payload Midtrans. */
function midtransChannelLabel(array $n): ?string
{
    $type = $n['payment_type'] ?? '';
    if ($type === '') {
        return null;
    }
    switch ($type) {
        case 'bank_transfer':
            if (!empty($n['va_numbers'][0]['bank'])) {
                return 'VA ' . strtoupper($n['va_numbers'][0]['bank']);
            }
            if (!empty($n['permata_va_number'])) {
                return 'VA PERMATA';
            }
            return 'Transfer Bank';
        case 'echannel':      return 'VA MANDIRI';
        case 'qris':          return 'QRIS' . (!empty($n['acquirer']) ? ' (' . strtoupper($n['acquirer']) . ')' : '');
        case 'gopay':         return 'GoPay';
        case 'shopeepay':     return 'ShopeePay';
        case 'credit_card':   return 'Kartu Kredit/Debit';
        case 'cstore':        return !empty($n['store']) ? ucfirst($n['store']) : 'Gerai Retail';
        default:              return ucwords(str_replace('_', ' ', $type));
    }
}

/**
 * Terapkan status dari Midtrans ke database. Idempoten (aman dipanggil berulang).
 * Sumber $n: body webhook (sudah diverifikasi signature) ATAU response API status (dari server Midtrans).
 *
 * @return string paid | cancelled | pending | ignored | amount_mismatch | not_found | late_paid
 */
function midtransApplyStatus(PDO $pdo, array $n): string
{
    $orderNumber = (string) ($n['order_id'] ?? '');
    $ts          = (string) ($n['transaction_status'] ?? '');
    $fraud       = (string) ($n['fraud_status'] ?? 'accept');
    if ($orderNumber === '' || $ts === '') {
        return 'ignored';
    }

    tzEnsureOrderHistoryTable($pdo); // DDL di luar transaksi

    $result = 'ignored';
    $notifyUser = null;   // [userId, title, message]
    $notifyAdmin = null;  // [title, message]
    $notifyAdminLink = null;

    try {
        $pdo->beginTransaction();

        $st = $pdo->prepare("SELECT * FROM orders WHERE order_number = :n AND payment_method = 'midtrans' LIMIT 1 FOR UPDATE");
        $st->execute([':n' => $orderNumber]);
        $order = $st->fetch();
        if (!$order) {
            $pdo->rollBack();
            return 'not_found';
        }

        // Validasi nominal: tolak jika tidak cocok dengan total di database.
        if ((int) round((float) ($n['gross_amount'] ?? 0)) !== (int) round((float) $order['total'])) {
            $pdo->rollBack();
            error_log('[TechZone][Midtrans] Nominal tidak cocok untuk ' . $orderNumber . ': ' . ($n['gross_amount'] ?? '?') . ' vs ' . $order['total']);
            return 'amount_mismatch';
        }

        $channel = midtransChannelLabel($n);
        $payload = json_encode($n);

        $isPaid   = ($ts === 'settlement') || ($ts === 'capture' && $fraud === 'accept');
        $isCancel = in_array($ts, ['cancel', 'expire'], true);

        if ($isPaid) {
            if ($order['payment_status'] === 'paid') {
                $pdo->commit();
                return 'paid'; // sudah diproses sebelumnya
            }

            $pdo->prepare("UPDATE payments SET status = 'paid', verified_at = NOW(), gateway_payload = :p, updated_at = NOW() WHERE order_id = :id")
                ->execute([':p' => $payload, ':id' => $order['id']]);

            if ($order['order_status'] === 'waiting_payment') {
                $pdo->prepare("UPDATE orders SET payment_status = 'paid', order_status = 'processing', payment_channel = COALESCE(:ch, payment_channel), updated_at = NOW() WHERE id = :id")
                    ->execute([':ch' => $channel, ':id' => $order['id']]);
                recordOrderStatus($pdo, (int) $order['id'], 'processing', 'Pembayaran diterima via Midtrans' . ($channel ? ' (' . $channel . ')' : ''));
                $notifyUser = [(int) $order['user_id'], 'Pembayaran Berhasil', 'Pembayaran pesanan ' . $order['order_number'] . ' telah kami terima. Pesanan Anda sedang diproses.'];
                $notifyAdmin = ['Pembayaran Midtrans Diterima', 'Pesanan ' . $order['order_number'] . ' lunas via Midtrans' . ($channel ? ' (' . $channel . ')' : '') . '.'];
                $result = 'paid';
            } else {
                // Pembayaran masuk untuk pesanan yang sudah dibatalkan/kadaluarsa -> butuh refund manual.
                $pdo->prepare("UPDATE orders SET payment_status = 'paid', payment_channel = COALESCE(:ch, payment_channel), updated_at = NOW() WHERE id = :id")
                    ->execute([':ch' => $channel, ':id' => $order['id']]);
                $notifyAdmin = ['PERLU REFUND: pembayaran masuk di pesanan batal', 'Pesanan ' . $order['order_number'] . ' sudah berstatus ' . $order['order_status'] . ' tetapi pembayaran Midtrans masuk. Segera proses pengembalian dana lewat dashboard Midtrans.'];
                $result = 'late_paid';
            }
        } elseif ($isCancel) {
            if ($order['order_status'] === 'waiting_payment' && $order['payment_status'] === 'unpaid') {
                $items = $pdo->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = :id');
                $items->execute([':id' => $order['id']]);
                foreach ($items->fetchAll() as $it) {
                    if ($it['product_id']) {
                        $pdo->prepare('UPDATE products SET stock = stock + :q WHERE id = :id')
                            ->execute([':q' => (int) $it['quantity'], ':id' => $it['product_id']]);
                    }
                }
                $newPay = $ts === 'expire' ? 'expired' : 'failed';
                $reason = $ts === 'expire' ? 'Batas waktu pembayaran Midtrans habis.' : 'Transaksi dibatalkan di Midtrans.';
                $pdo->prepare("UPDATE orders SET order_status = 'cancelled', payment_status = :ps, cancel_reason = :r, updated_at = NOW() WHERE id = :id")
                    ->execute([':ps' => $newPay, ':r' => $reason, ':id' => $order['id']]);
                $pdo->prepare("UPDATE payments SET status = :ps, gateway_payload = :p, updated_at = NOW() WHERE order_id = :id AND status = 'unpaid'")
                    ->execute([':ps' => $newPay, ':p' => $payload, ':id' => $order['id']]);
                recordOrderStatus($pdo, (int) $order['id'], 'cancelled', $reason);
                $notifyUser = [(int) $order['user_id'], 'Pesanan Dibatalkan', 'Pesanan ' . $order['order_number'] . ' dibatalkan. ' . $reason . ' Stok sudah dikembalikan, Anda bisa memesan kembali.'];
                $result = 'cancelled';
            }
        } elseif ($ts === 'pending') {
            // Pembeli sudah memilih metode & transaksi terbentuk (VA/QR dibuat) -> otomatis masuk
            // antrean "Menunggu Verifikasi" admin; pembeli tidak perlu menyalin VA / memakai simulator.
            if ($order['payment_status'] === 'unpaid' && $order['order_status'] === 'waiting_payment') {
                $upd = $pdo->prepare("UPDATE payments SET status = 'waiting_verification', gateway_payload = :p, updated_at = NOW() WHERE order_id = :id AND status = 'unpaid'");
                $upd->execute([':p' => $payload, ':id' => $order['id']]);
                if ($upd->rowCount() > 0) {
                    $pdo->prepare("UPDATE orders SET payment_status = 'waiting_verification', payment_channel = COALESCE(:ch, payment_channel), updated_at = NOW() WHERE id = :id")
                        ->execute([':ch' => $channel, ':id' => $order['id']]);
                    $notifyAdmin = ['Pembayaran Masuk', 'Pembayaran Midtrans' . ($channel ? ' (' . $channel . ')' : '') . ' untuk pesanan ' . $order['order_number'] . ' (' . formatRupiah($order['total']) . ') menunggu konfirmasi Anda.'];
                    $notifyAdminLink = 'admin/payments.php?status=waiting_verification';
                }
            }
            $result = 'pending';
        } elseif (in_array($ts, ['refund', 'partial_refund', 'chargeback', 'partial_chargeback'], true)) {
            $notifyAdmin = ['Midtrans: ' . $ts, 'Pesanan ' . $order['order_number'] . ' menerima status "' . $ts . '" dari Midtrans. Periksa dashboard Midtrans.'];
        }
        // 'deny' / 'failure' sengaja tidak membatalkan pesanan: pembeli masih bisa mencoba metode lain
        // dengan Snap token yang sama sampai token kedaluwarsa.

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[TechZone][Midtrans Apply] ' . $e->getMessage());
        return 'ignored';
    }

    // Notifikasi dikirim setelah commit.
    if ($notifyUser) {
        createNotification($pdo, $notifyUser[0], $notifyUser[1], $notifyUser[2], 'order');
    }
    if ($notifyAdmin) {
        $isClaim = $notifyAdminLink !== null;
        notifyAdmins($pdo, $notifyAdmin[0], $notifyAdmin[1], $isClaim ? 'payment' : 'order', $notifyAdminLink);
    }
    return $result;
}

/**
 * Sinkronkan satu order dari Midtrans (tanya status langsung ke server Midtrans).
 * Berguna bila webhook terlambat / tidak terjangkau (mis. localhost).
 * @return string hasil midtransApplyStatus() atau 'none' bila Midtrans belum punya transaksi.
 */
function midtransSyncOrder(PDO $pdo, string $orderNumber): string
{
    if (!midtransIsConfigured()) {
        return 'none';
    }
    [$code, $res] = midtransGetStatus($orderNumber);
    if ($code === 200 && is_array($res) && !empty($res['transaction_status'])) {
        // Response status API untuk 404 juga berformat JSON dengan status_code "404"; di sini hanya 200 yang valid.
        if (($res['status_code'] ?? '') === '404') {
            return 'none';
        }
        return midtransApplyStatus($pdo, $res);
    }
    return 'none';
}
