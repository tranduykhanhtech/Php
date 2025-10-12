<?php
// File test kết nối database
require_once 'config/database.php';

echo "<h2>🔍 Kiểm tra kết nối database</h2>";

// Test kết nối
$result = $conn->query("SELECT VERSION() as version");
$version = $result->fetch_assoc();

echo "<p style='color: green;'>✅ Kết nối database thành công!</p>";
echo "<p><strong>MySQL Version:</strong> " . $version['version'] . "</p>";

// Test bảng users
$result = $conn->query("SELECT COUNT(*) as count FROM users");
$userCount = $result->fetch_assoc();
echo "<p><strong>Số lượng users:</strong> " . $userCount['count'] . "</p>";

// Test bảng products
$result = $conn->query("SELECT COUNT(*) as count FROM products");
$productCount = $result->fetch_assoc();
echo "<p><strong>Số lượng sản phẩm:</strong> " . $productCount['count'] . "</p>";

echo "<p style='color: green;'>🎉 Database đã sẵn sàng sử dụng!</p>";
?>
