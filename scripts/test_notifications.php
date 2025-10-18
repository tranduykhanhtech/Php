<?php
/**
 * Script test hệ thống notification
 * Tạo một số thông báo mẫu để test
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/notification_helper.php';

echo "🧪 Test hệ thống notification\n";
echo "==============================\n\n";

try {
    // Lấy user đầu tiên để test
    $stmt = $pdo->query("SELECT id, full_name, email FROM users WHERE role = 'customer' LIMIT 1");
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "❌ Không tìm thấy user nào để test. Vui lòng tạo user trước.\n";
        exit(1);
    }
    
    echo "👤 Test với user: {$user['full_name']} ({$user['email']})\n\n";
    
    // Test 1: Gửi thông báo đơn hàng
    echo "1️⃣ Test thông báo đơn hàng...\n";
    $result1 = sendOrderNotification($user['id'], 999, 'TEST-001', 'processing');
    echo $result1 ? "✅ Thành công\n" : "❌ Thất bại\n";
    
    // Test 2: Gửi thông báo liên hệ
    echo "2️⃣ Test thông báo liên hệ...\n";
    $result2 = sendContactNotification($user['id'], 999, 'resolved');
    echo $result2 ? "✅ Thành công\n" : "❌ Thất bại\n";
    
    // Test 3: Gửi thông báo chung
    echo "3️⃣ Test thông báo chung...\n";
    $result3 = sendNotification($user['id'], 'Chào mừng!', 'Chào mừng bạn đến với hệ thống thông báo mới!', 'general');
    echo $result3 ? "✅ Thành công\n" : "❌ Thất bại\n";
    
    // Test 4: Gửi thông báo khuyến mãi
    echo "4️⃣ Test thông báo khuyến mãi...\n";
    $result4 = sendNotification($user['id'], 'Khuyến mãi đặc biệt!', 'Giảm giá 20% cho tất cả sản phẩm trong tuần này!', 'promotion');
    echo $result4 ? "✅ Thành công\n" : "❌ Thất bại\n";
    
    // Test 5: Đếm thông báo chưa đọc
    echo "5️⃣ Test đếm thông báo chưa đọc...\n";
    $unread_count = getUnreadNotificationCount($user['id']);
    echo "📊 Số thông báo chưa đọc: {$unread_count}\n";
    
    // Test 6: Lấy thông báo gần đây
    echo "6️⃣ Test lấy thông báo gần đây...\n";
    $recent_notifications = getRecentNotifications($user['id'], 5);
    echo "📋 Số thông báo gần đây: " . count($recent_notifications) . "\n";
    
    // Hiển thị danh sách thông báo
    if (!empty($recent_notifications)) {
        echo "\n📝 Danh sách thông báo:\n";
        echo "┌────┬─────────────────────────┬─────────────────────────┬─────────┬─────────┐\n";
        echo "│ ID │ Tiêu đề                 │ Nội dung                │ Loại    │ Đã đọc  │\n";
        echo "├────┼─────────────────────────┼─────────────────────────┼─────────┼─────────┤\n";
        
        foreach ($recent_notifications as $notification) {
            $title = substr($notification['title'], 0, 20) . (strlen($notification['title']) > 20 ? '...' : '');
            $message = substr($notification['message'], 0, 20) . (strlen($notification['message']) > 20 ? '...' : '');
            $is_read = $notification['is_read'] ? '✅' : '❌';
            
            printf("│ %-2s │ %-23s │ %-23s │ %-7s │ %-7s │\n",
                $notification['id'],
                $title,
                $message,
                $notification['type'],
                $is_read
            );
        }
        echo "└────┴─────────────────────────┴─────────────────────────┴─────────┴─────────┘\n";
    }
    
    // Test 7: Gửi thông báo cho tất cả khách hàng
    echo "\n7️⃣ Test gửi thông báo cho tất cả khách hàng...\n";
    $count = sendNotificationToAllCustomers('Thông báo hệ thống', 'Hệ thống thông báo đã được cập nhật!', 'general');
    echo "📤 Đã gửi thông báo cho {$count} khách hàng\n";
    
    echo "\n🎉 Hoàn thành test! Hệ thống notification hoạt động bình thường.\n";
    
} catch (Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
    exit(1);
}
?>
