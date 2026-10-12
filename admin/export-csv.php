<?php
/**
 * TechZone Admin - Export CSV Riwayat Transaksi
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../config/payment.php';

$admin = currentAdmin();
$pdo   = getDbConnection();

$dateFrom  = trim($_GET['date_from'] ?? '');
$dateTo    = trim($_GET['date_to'] ?? '');
$userQ     = trim($_GET['user'] ?? '');
$methodQ   = $_GET['method'] ?? '';
$statusQ   = $_GET['status'] ?? '';
$orderNumQ = trim($_GET['order_number'] ?? '');

$where  = [];
$params = [];
if ($dateFrom !== '') { $where[] = 'DATE(p.created_at) >= :df'; $params[':df'] = $dateFrom; }
if ($dateTo !== '') { $where[] = 'DATE(p.created_at) <= :dt'; $params[':dt'] = $dateTo; }
if ($userQ !== '') { $where[] = '(u.name LIKE :user OR u.email LIKE :user2)'; $params[':user'] = '%'.$userQ.'%'; $params[':user2'] = '%'.$userQ.'%'; }
if (in_array($methodQ, getValidPaymentMethods(), true)) { $where[] = 'p.payment_method = :method'; $params[':method'] = $methodQ; }
if (in_array($statusQ, getValidPaymentStatuses(), true)) { $where[] = 'p.status = :status'; $params[':status'] = $statusQ; }
if ($orderNumQ !== '') { $where[] = 'o.order_number LIKE :onum'; $params[':onum'] = '%'.$orderNumQ.'%'; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare(
    "SELECT p.transaction_id, o.order_number, u.name AS user_name, u.email AS user_email, p.created_at,
            p.amount, p.payment_method, p.status, o.order_status
     FROM payments p JOIN orders o ON o.id = p.order_id JOIN users u ON u.id = o.user_id
     $whereSql ORDER BY p.created_at DESC"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

logActivity($admin['id'], 'export_csv', 'Export CSV riwayat transaksi (' . count($rows) . ' baris).');

$filename = 'techzone-transaksi-' . date('Ymd-His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID Transaksi', 'No. Order', 'Nama User', 'Email User', 'Tanggal', 'Jumlah', 'Metode Pembayaran', 'Status Pembayaran', 'Status Pesanan']);

foreach ($rows as $row) {
    fputcsv($output, [
        $row['transaction_id'],
        $row['order_number'],
        $row['user_name'],
        $row['user_email'],
        $row['created_at'],
        $row['amount'],
        strtoupper($row['payment_method']),
        paymentStatusLabel($row['status'], $row['payment_method']),
        orderStatusLabel($row['order_status']),
    ]);
}

fclose($output);
exit;
