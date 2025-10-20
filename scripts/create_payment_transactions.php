<?php
require_once __DIR__ . '/../config/database.php';

echo "Creating payment_transactions table...\n";

try {
    $sql = "CREATE TABLE IF NOT EXISTS payment_transactions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        order_id INT NOT NULL,
        transaction_code VARCHAR(50) UNIQUE NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_method ENUM('cod', 'bank_transfer') NOT NULL,
        transaction_note TEXT,
        transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        INDEX idx_order (order_id),
        INDEX idx_transaction_code (transaction_code),
        INDEX idx_transaction_date (transaction_date DESC)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✓ Table payment_transactions created successfully\n";
    
    // Verify table structure
    $result = $pdo->query("DESCRIBE payment_transactions");
    echo "\nTable structure:\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-20s %-20s %-10s %-10s\n", "Field", "Type", "Null", "Key");
    echo str_repeat("-", 80) . "\n";
    
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        printf("%-20s %-20s %-10s %-10s\n", 
            $row['Field'], 
            $row['Type'], 
            $row['Null'], 
            $row['Key']
        );
    }
    
    echo "\n✓ Migration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
