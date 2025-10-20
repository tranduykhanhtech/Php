<?php
/**
 * Notification Helper Functions
 * Các hàm hỗ trợ gửi và quản lý thông báo
 */

require_once __DIR__ . '/../config/database.php'; //changed: sửa đường dẫn

/**
 * Gửi thông báo cho người dùng
 * 
 * @param int $user_id ID của người dùng
 * @param string $title Tiêu đề thông báo
 * @param string $message Nội dung thông báo
 * @param string $type Loại thông báo (order, contact, general, promotion)
 * @param int|null $related_id ID liên quan (order_id, contact_id, etc.)
 * @return bool True nếu thành công, false nếu thất bại
 */
function sendNotification($user_id, $title, $message, $type = 'general', $related_id = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type, related_id) 
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$user_id, $title, $message, $type, $related_id]);
    } catch (Exception $e) {
        error_log('Send notification error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Gửi thông báo cho tất cả khách hàng
 * 
 * @param string $title Tiêu đề thông báo
 * @param string $message Nội dung thông báo
 * @param string $type Loại thông báo
 * @return int Số lượng thông báo đã gửi
 */
function sendNotificationToAllCustomers($title, $message, $type = 'general') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'customer'");
        $stmt->execute();
        $customers = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $count = 0;
        foreach ($customers as $user_id) {
            if (sendNotification($user_id, $title, $message, $type)) {
                $count++;
            }
        }
        
        return $count;
    } catch (Exception $e) {
        error_log('Send notification to all customers error: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Lấy số lượng thông báo chưa đọc của người dùng
 * 
 * @param int $user_id ID của người dùng
 * @return int Số lượng thông báo chưa đọc
 */
function getUnreadNotificationCount($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        error_log('Get unread notification count error: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Lấy thông báo gần đây của người dùng
 * 
 * @param int $user_id ID của người dùng
 * @param int $limit Số lượng thông báo tối đa
 * @return array Danh sách thông báo
 */
function getRecentNotifications($user_id, $limit = 5) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, title, message, type, related_id, is_read, created_at
            FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Get recent notifications error: ' . $e->getMessage());
        return [];
    }
}

/**
 * Tạo thông báo cho đơn hàng
 * 
 * @param int $user_id ID của người dùng
 * @param int $order_id ID của đơn hàng
 * @param string $order_number Mã đơn hàng
 * @param string $status Trạng thái mới của đơn hàng
 * @return bool True nếu thành công
 */
function sendOrderNotification($user_id, $order_id, $order_number, $status) {
    $status_messages = [
        'pending' => 'Đơn hàng #' . $order_number . ' đã được đặt thành công và đang chờ xử lý.',
        'processing' => 'Đơn hàng #' . $order_number . ' đang được xử lý và chuẩn bị giao hàng.',
        'shipped' => 'Đơn hàng #' . $order_number . ' đã được giao cho đơn vị vận chuyển.',
        'delivered' => 'Đơn hàng #' . $order_number . ' đã được giao thành công. Cảm ơn bạn đã mua hàng!',
        'cancelled' => 'Đơn hàng #' . $order_number . ' đã bị hủy.'
    ];
    
    $status_titles = [
        'pending' => 'Đơn hàng mới',
        'processing' => 'Đang xử lý đơn hàng',
        'shipped' => 'Đơn hàng đã giao',
        'delivered' => 'Giao hàng thành công',
        'cancelled' => 'Đơn hàng bị hủy'
    ];
    
    $title = $status_titles[$status] ?? 'Cập nhật đơn hàng';
    $message = $status_messages[$status] ?? 'Đơn hàng #' . $order_number . ' đã được cập nhật.';
    
    return sendNotification($user_id, $title, $message, 'order', $order_id);
}

/**
 * Tạo thông báo cho liên hệ
 * 
 * @param int $user_id ID của người dùng
 * @param int $contact_id ID của liên hệ
 * @param string $status Trạng thái mới của liên hệ
 * @return bool True nếu thành công
 */
function sendContactNotification($user_id, $contact_id, $status) {
    $status_messages = [
        'in_progress' => 'Yêu cầu hỗ trợ của bạn đang được xử lý.',
        'resolved' => 'Yêu cầu hỗ trợ của bạn đã được giải quyết.',
        'closed' => 'Yêu cầu hỗ trợ của bạn đã được đóng.'
    ];
    
    $status_titles = [
        'in_progress' => 'Đang xử lý yêu cầu',
        'resolved' => 'Yêu cầu đã được giải quyết',
        'closed' => 'Yêu cầu đã đóng'
    ];
    
    $title = $status_titles[$status] ?? 'Cập nhật yêu cầu hỗ trợ';
    $message = $status_messages[$status] ?? 'Yêu cầu hỗ trợ của bạn đã được cập nhật.';
    
    return sendNotification($user_id, $title, $message, 'contact', $contact_id);
}
?>
