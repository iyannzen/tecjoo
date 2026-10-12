<?php
/**
 * TechZone - Saran Pencarian (autocomplete)
 * GET ?q=kata  ->  JSON {products:[...], categories:[...]}
 * Hanya data produk aktif yang publik; semua query memakai prepared statement.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/product_helpers.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$q = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($q) < 2 || mb_strlen($q) > 80) {
    echo json_encode(['products' => [], 'categories' => []]);
    exit;
}

try {
    $pdo = getDbConnection();
    [$searchSql, $params] = tzSearchWhere($q);
    if ($searchSql === '') {
        echo json_encode(['products' => [], 'categories' => []]);
        exit;
    }

    $first = tzSearchTerms($q)[0] ?? $q;
    $stmt  = $pdo->prepare(
        "SELECT p.slug, p.name, p.brand, p.main_image, p.price, p.discount_price, p.stock, c.name AS category_name
         FROM products p JOIN categories c ON c.id = p.category_id
         WHERE p.status = 'active' AND $searchSql
         ORDER BY (p.name LIKE :rel) DESC, (p.stock > 0) DESC, p.review_count DESC, p.name ASC
         LIMIT 6"
    );
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':rel', $first . '%');
    $stmt->execute();

    $products = [];
    foreach ($stmt->fetchAll() as $row) {
        $products[] = [
            'name'     => $row['name'],
            'category' => $row['category_name'],
            'price'    => formatRupiah(effectivePrice($row)),
            'image'    => productImageUrl($row),
            'url'      => techzoneBaseUrl() . '/product-detail.php?slug=' . rawurlencode($row['slug']),
            'stock'    => (int) $row['stock'] > 0,
        ];
    }

    $catStmt = $pdo->prepare("SELECT name, slug FROM categories WHERE status = 'active' AND name LIKE :q LIMIT 3");
    $catStmt->execute([':q' => '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%']);
    $categories = [];
    foreach ($catStmt->fetchAll() as $c) {
        $categories[] = ['name' => $c['name'], 'url' => techzoneBaseUrl() . '/products.php?kategori=' . rawurlencode($c['slug'])];
    }

    echo json_encode(['products' => $products, 'categories' => $categories], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('[TechZone][Suggest] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['products' => [], 'categories' => []]);
}
