<?php
require_once __DIR__ . '/../config/database.php';

$page = 1;
$status_filter = $argv[1] ?? '';
$limit = 15;
$offset = ($page - 1) * $limit;

$where = '1=1';
$params = [];
if ($status_filter !== '') {
    $where = 'o.order_status = ?';
    $params[] = $status_filter;
}

$sql = "SELECT o.id, o.order_number, o.total_amount, o.order_status, o.payment_status, o.created_at, o.customer_name, o.customer_email,
        (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
        FROM orders o
        WHERE $where
        ORDER BY o.created_at DESC
        LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($sql);
$bindIndex = 1;
if ($status_filter !== '') {
    $stmt->bindValue($bindIndex++, $status_filter, PDO::PARAM_STR);
}
$stmt->bindValue($bindIndex++, (int)$limit, PDO::PARAM_INT);
$stmt->bindValue($bindIndex++, (int)$offset, PDO::PARAM_INT);

$stmt->execute();
$rows = $stmt->fetchAll();

if (!$rows) {
    echo "No orders returned by admin query (status='$status_filter')\n";
    exit(0);
}

foreach ($rows as $r) {
    echo sprintf("%d | %s | %s | %s | %s | items=%d\n", $r['id'], $r['order_number'], $r['order_status'], $r['payment_status'], $r['created_at'], $r['item_count']);
}

