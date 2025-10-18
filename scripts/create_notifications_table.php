<?php
/**
 * Script tạo bảng notifications
 * Chạy script này để tạo bảng notifications trong database
 */

require_once __DIR__ . '/../config/database.php';

try {
    // Tạo bảng notifications
    $sql = "
    CREATE TABLE IF NOT EXISTS notifications (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('order', 'contact', 'general', 'promotion') DEFAULT 'general',
        related_id INT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_is_read (is_read),
        INDEX idx_created_at (created_at)
    )";
    
    $pdo->exec($sql);
    echo "✅ Tạo bảng notifications thành công!\n";
    
    // Kiểm tra bảng đã được tạo chưa
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Bảng notifications đã tồn tại trong database.\n";
        
        // Hiển thị cấu trúc bảng
        $stmt = $pdo->query("DESCRIBE notifications");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\n📋 Cấu trúc bảng notifications:\n";
        echo "┌─────────────┬─────────────────┬──────┬─────┬─────────┬─────┐\n";
        echo "│ Field       │ Type            │ Null │ Key │ Default │ Extra│\n";
        echo "├─────────────┼─────────────────┼──────┼─────┼─────────┼─────┤\n";
        
        foreach ($columns as $column) {
            printf("│ %-11s │ %-15s │ %-4s │ %-3s │ %-7s │ %-3s │\n",
                $column['Field'],
                $column['Type'],
                $column['Null'],
                $column['Key'],
                $column['Default'] ?? 'NULL',
                $column['Extra']
            );
        }
        echo "└─────────────┴─────────────────┴──────┴─────┴─────────┴─────┘\n";
        
    } else {
        echo "❌ Có lỗi khi tạo bảng notifications.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🎉 Hoàn thành! Hệ thống notification đã sẵn sàng sử dụng.\n";
?>
