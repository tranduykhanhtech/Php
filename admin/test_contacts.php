<?php
// Test file để debug lỗi contacts.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Step 1: Loading database config...\n";
try {
    require_once __DIR__ . '/../config/database.php';
    echo "✓ Database config loaded\n";
} catch (Exception $e) {
    die("✗ Error loading database: " . $e->getMessage() . "\n");
}

echo "\nStep 2: Checking admin auth...\n";
try {
    requireAdmin();
    echo "✓ Admin auth passed\n";
} catch (Exception $e) {
    die("✗ Error in requireAdmin: " . $e->getMessage() . "\n");
}

echo "\nStep 3: Checking contacts table...\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM contacts");
    $total = $stmt->fetch()['total'];
    echo "✓ Contacts table exists with {$total} records\n";
} catch (Exception $e) {
    die("✗ Error querying contacts: " . $e->getMessage() . "\n");
}

echo "\nStep 4: Testing status counts query...\n";
try {
    $status_counts = $pdo->query("
        SELECT 
            status,
            COUNT(*) as count
        FROM contacts
        GROUP BY status
    ")->fetchAll(PDO::FETCH_KEY_PAIR);
    echo "✓ Status counts query works\n";
    print_r($status_counts);
} catch (Exception $e) {
    die("✗ Error in status counts: " . $e->getMessage() . "\n");
}

echo "\nStep 5: Testing main contacts query...\n";
try {
    $limit = 20;
    $offset = 0;
    $stmt = $pdo->prepare("
        SELECT * FROM contacts 
        ORDER BY 
        CASE 
            WHEN status = 'new' THEN 1
            WHEN status = 'in_progress' THEN 2
            WHEN status = 'resolved' THEN 3
            WHEN status = 'closed' THEN 4
        END,
        created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$limit, $offset]);
    $contacts = $stmt->fetchAll();
    echo "✓ Main query works, found " . count($contacts) . " contacts\n";
} catch (Exception $e) {
    die("✗ Error in main query: " . $e->getMessage() . "\n");
}

echo "\n✅ All tests passed! The page should work.\n";
