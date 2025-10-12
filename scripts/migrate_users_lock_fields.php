<?php
require_once __DIR__ . '/../config/database.php';

try {
    // Add suspension_until DATETIME NULL and is_locked TINYINT(1) DEFAULT 0
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS suspension_until DATETIME NULL AFTER updated_at");
} catch (Exception $e) {
    // Some MySQL versions don't support IF NOT EXISTS on ADD COLUMN. Try to check column exists.
    $colCheck = $pdo->query("SHOW COLUMNS FROM users LIKE 'suspension_until'")->fetch();
    if (!$colCheck) {
        $pdo->exec("ALTER TABLE users ADD COLUMN suspension_until DATETIME NULL AFTER updated_at");
    }
}

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_locked TINYINT(1) DEFAULT 0 AFTER suspension_until");
} catch (Exception $e) {
    $colCheck = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_locked'")->fetch();
    if (!$colCheck) {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_locked TINYINT(1) DEFAULT 0 AFTER suspension_until");
    }
}

echo "Migration complete\n";
