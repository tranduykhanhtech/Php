<?php
/**
 * Migration script: Thêm bảng contacts để quản lý liên hệ từ khách hàng
 * Chạy script này một lần để tạo bảng contacts trong database
 */

require_once __DIR__ . '/../config/database.php';

try {
    echo "Bắt đầu migration: Tạo bảng contacts...\n";
    
    // Kiểm tra xem bảng contacts đã tồn tại chưa
    $check = $pdo->query("SHOW TABLES LIKE 'contacts'")->rowCount();
    
    if ($check > 0) {
        echo "Bảng contacts đã tồn tại. Bỏ qua migration.\n";
        exit(0);
    }
    
    // Tạo bảng contacts
    $pdo->exec("
        CREATE TABLE contacts (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(20),
            subject VARCHAR(100) NOT NULL,
            message TEXT NOT NULL,
            status ENUM('new', 'in_progress', 'resolved', 'closed') DEFAULT 'new',
            admin_note TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    echo "✓ Đã tạo bảng contacts\n";
    
    // Thêm indexes
    $pdo->exec("CREATE INDEX idx_contacts_status ON contacts(status)");
    echo "✓ Đã tạo index idx_contacts_status\n";
    
    $pdo->exec("CREATE INDEX idx_contacts_created_at ON contacts(created_at)");
    echo "✓ Đã tạo index idx_contacts_created_at\n";
    
    $pdo->exec("CREATE INDEX idx_contacts_email ON contacts(email)");
    echo "✓ Đã tạo index idx_contacts_email\n";
    
    echo "\n✅ Migration hoàn tất thành công!\n";
    echo "Bảng contacts đã được tạo và sẵn sàng sử dụng.\n";
    
} catch (PDOException $e) {
    echo "❌ Lỗi migration: " . $e->getMessage() . "\n";
    exit(1);
}
