<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h1>Debug Contacts Page</h1>";

try {
    echo "<p>1. Loading database config...</p>";
    require_once __DIR__ . '/../config/database.php';
    echo "<p style='color:green'>✓ Database loaded</p>";
    
    echo "<p>2. Checking if logged in...</p>";
    if (!isLoggedIn()) {
        die("<p style='color:red'>✗ Not logged in. Please login first.</p>");
    }
    echo "<p style='color:green'>✓ Logged in as: " . htmlspecialchars($_SESSION['user_name']) . "</p>";
    
    echo "<p>3. Checking if admin...</p>";
    if (!isAdmin()) {
        die("<p style='color:red'>✗ Not admin. Role: " . htmlspecialchars($_SESSION['user_role']) . "</p>");
    }
    echo "<p style='color:green'>✓ Is admin</p>";
    
    echo "<p>4. Checking contacts table...</p>";
    $stmt = $pdo->query("DESCRIBE contacts");
    $columns = $stmt->fetchAll();
    echo "<p style='color:green'>✓ Table exists with " . count($columns) . " columns</p>";
    
    echo "<p>5. Counting contacts...</p>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM contacts");
    $total = $stmt->fetch()['total'];
    echo "<p style='color:green'>✓ Found {$total} contacts</p>";
    
    echo "<p>6. Testing main query with LIMIT binding...</p>";
    $limit = 20;
    $offset = 0;
    
    // Test với bindValue thay vì execute array
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
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $contacts = $stmt->fetchAll();
    echo "<p style='color:green'>✓ Query executed, found " . count($contacts) . " contacts</p>";
    
    echo "<h2 style='color:green'>All checks passed! ✅</h2>";
    echo "<p><a href='contacts.php'>Try opening contacts.php now</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
