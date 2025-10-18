<?php
// Test query với binding đúng cách
require_once __DIR__ . '/../config/database.php';

echo "Testing contacts query with proper parameter binding...\n\n";

$page = 1;
$limit = 20;
$offset = ($page - 1) * $limit;
$status_filter = '';

$where = "";
if ($status_filter) {
    $where = "WHERE status = ?";
}

$sql = "SELECT * FROM contacts $where ORDER BY 
        CASE 
            WHEN status = 'new' THEN 1
            WHEN status = 'in_progress' THEN 2
            WHEN status = 'resolved' THEN 3
            WHEN status = 'closed' THEN 4
        END,
        created_at DESC
        LIMIT ? OFFSET ?";

try {
    $stmt = $pdo->prepare($sql);
    
    $param_index = 1;
    if ($status_filter) {
        $stmt->bindValue($param_index++, $status_filter, PDO::PARAM_STR);
    }
    
    $stmt->bindValue($param_index++, $limit, PDO::PARAM_INT);
    $stmt->bindValue($param_index, $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $contacts = $stmt->fetchAll();
    
    echo "✓ Query executed successfully!\n";
    echo "Found " . count($contacts) . " contacts\n\n";
    
    if (count($contacts) > 0) {
        echo "First contact:\n";
        print_r($contacts[0]);
    }
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
