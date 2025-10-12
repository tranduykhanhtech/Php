<?php
require_once __DIR__ . '/../config/database.php';

$stmt = $pdo->query("SELECT id, order_number, order_status, payment_status, created_at FROM orders ORDER BY id DESC LIMIT 50");
$rows = $stmt->fetchAll();
if (!$rows) {
    echo "No orders found\n";
    exit;
}
$counts = [];
foreach ($rows as $r) {
    $counts[$r['order_status']] = ($counts[$r['order_status']] ?? 0) + 1;
}
echo "Order status counts (latest 50):\n";
foreach ($counts as $k => $v) {
    echo "  $k: $v\n";
}

echo "\nSamples:\n";
foreach ($rows as $r) {
    echo sprintf("%d | %s | %s | %s | %s\n", $r['id'], $r['order_number'], $r['order_status'], $r['payment_status'], $r['created_at']);
}
