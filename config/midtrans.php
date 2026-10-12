<?php
/**
 * TechZone - Konfigurasi Midtrans (Snap)
 *
 * Urutan pengambilan key:
 *   1. Environment variable  MIDTRANS_SERVER_KEY / MIDTRANS_CLIENT_KEY
 *   2. File config/midtrans.local.php (JANGAN di-commit ke Git)
 *
 * Mode ditentukan EKSPLISIT (default: SANDBOX, aman):
 *   - env MIDTRANS_ENV atau 'environment' di midtrans.local.php: 'sandbox' | 'production'
 *   - Key sandbox dan production harus berpasangan dengan mode ini.
 */

$tzMidtransLocal = [];
if (is_file(__DIR__ . '/midtrans.local.php')) {
    $tzMidtransLocal = (array) include __DIR__ . '/midtrans.local.php';
}

define('MIDTRANS_SERVER_KEY', getenv('MIDTRANS_SERVER_KEY') ?: ($tzMidtransLocal['server_key'] ?? ''));
define('MIDTRANS_CLIENT_KEY', getenv('MIDTRANS_CLIENT_KEY') ?: ($tzMidtransLocal['client_key'] ?? ''));
define('MIDTRANS_MERCHANT_ID', getenv('MIDTRANS_MERCHANT_ID') ?: ($tzMidtransLocal['merchant_id'] ?? ''));

define('MIDTRANS_ENV', strtolower((string) (getenv('MIDTRANS_ENV') ?: ($tzMidtransLocal['environment'] ?? 'sandbox'))));
define('MIDTRANS_IS_PRODUCTION', MIDTRANS_ENV === 'production');

define('MIDTRANS_SNAP_URL', MIDTRANS_IS_PRODUCTION
    ? 'https://app.midtrans.com/snap/v1/transactions'
    : 'https://app.sandbox.midtrans.com/snap/v1/transactions');
define('MIDTRANS_API_URL', MIDTRANS_IS_PRODUCTION
    ? 'https://api.midtrans.com'
    : 'https://api.sandbox.midtrans.com');
define('MIDTRANS_SNAP_JS', MIDTRANS_IS_PRODUCTION
    ? 'https://app.midtrans.com/snap/snap.js'
    : 'https://app.sandbox.midtrans.com/snap/snap.js');

/** True bila kedua key sudah terisi. */
function midtransIsConfigured(): bool
{
    return MIDTRANS_SERVER_KEY !== '' && MIDTRANS_CLIENT_KEY !== '';
}
