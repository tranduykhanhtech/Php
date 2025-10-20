<?php
/**
 * Migration script: Tạo bảng password_resets để lưu OTP reset mật khẩu
 */

require_once __DIR__ . '/../config/database.php';

try {
    echo "Bắt đầu migration: Tạo bảng password_resets...\n";
    
    // Kiểm tra xem bảng đã tồn tại chưa
    $check = $pdo->query("SHOW TABLES LIKE 'password_resets'")->rowCount();
    
    if ($check > 0) {
        echo "Bảng password_resets đã tồn tại. Bỏ qua migration.\n";
        exit(0);
    }
    
    // Tạo bảng password_resets
    $pdo->exec("
        CREATE TABLE password_resets (
            id INT PRIMARY KEY AUTO_INCREMENT,
            email VARCHAR(100) NOT NULL,
            otp VARCHAR(6) NOT NULL,
            expires_at DATETIME NOT NULL,
            is_used TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_otp (otp),
            INDEX idx_expires_at (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    echo "✓ Đã tạo bảng password_resets\n";
    echo "\n✅ Migration hoàn tất thành công!\n";
    
} catch (PDOException $e) {
    echo "❌ Lỗi migration: " . $e->getMessage() . "\n";
    exit(1);
}
