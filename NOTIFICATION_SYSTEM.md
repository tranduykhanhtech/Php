# Hệ thống Thông báo (Notification System)

## Tổng quan
Hệ thống thông báo cho phép gửi thông báo tự động đến khách hàng khi có các sự kiện quan trọng như:
- Cập nhật trạng thái đơn hàng
- Xử lý yêu cầu hỗ trợ
- Thông báo khuyến mãi
- Thông báo chung

## Cài đặt

### 1. Tạo bảng notifications
```bash
php scripts/create_notifications_table.php
```

### 2. Test hệ thống
```bash
php scripts/test_notifications.php
```

## Cấu trúc Database

### Bảng notifications
```sql
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('order', 'contact', 'general', 'promotion') DEFAULT 'general',
    related_id INT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## API Endpoints

### 1. Lấy danh sách thông báo
```
GET /api/notifications.php?page=1
```

### 2. Lấy số lượng thông báo chưa đọc
```
GET /api/notification-count.php
```

### 3. Đánh dấu thông báo đã đọc
```
POST /api/notifications.php
{
    "action": "mark_read",
    "id": 123
}
```

### 4. Đánh dấu tất cả đã đọc
```
POST /api/notifications.php
{
    "action": "mark_all_read"
}
```

### 5. Xóa thông báo
```
DELETE /api/notifications.php?id=123
```

## Sử dụng trong Code

### 1. Gửi thông báo cho một user
```php
require_once 'includes/notification_helper.php';

sendNotification($user_id, $title, $message, $type, $related_id);
```

### 2. Gửi thông báo đơn hàng
```php
sendOrderNotification($user_id, $order_id, $order_number, $status);
```

### 3. Gửi thông báo liên hệ
```php
sendContactNotification($user_id, $contact_id, $status);
```

### 4. Gửi thông báo cho tất cả khách hàng
```php
sendNotificationToAllCustomers($title, $message, $type);
```

### 5. Lấy số thông báo chưa đọc
```php
$count = getUnreadNotificationCount($user_id);
```

### 6. Lấy thông báo gần đây
```php
$notifications = getRecentNotifications($user_id, $limit);
```

## Giao diện Người dùng

### 1. Icon chuông thông báo
- Hiển thị trong header
- Hiển thị số lượng thông báo chưa đọc
- Dropdown hiển thị 5 thông báo gần nhất

### 2. Trang thông báo
- URL: `/notifications.php`
- Hiển thị tất cả thông báo với phân trang
- Chức năng đánh dấu đã đọc/xóa

### 3. Menu người dùng
- Link "Thông báo" trong dropdown menu

## Tích hợp Tự động

### 1. Đơn hàng
- Tự động gửi thông báo khi admin cập nhật trạng thái đơn hàng
- Các trạng thái: pending, processing, shipped, delivered, cancelled

### 2. Liên hệ
- Tự động gửi thông báo khi admin cập nhật trạng thái yêu cầu hỗ trợ
- Các trạng thái: in_progress, resolved, closed

## Loại Thông báo

### 1. Order (Đơn hàng)
- Icon: 🛍️
- Màu: Xanh dương
- Tự động gửi khi cập nhật trạng thái đơn hàng

### 2. Contact (Liên hệ)
- Icon: 💬
- Màu: Xanh lá
- Tự động gửi khi cập nhật trạng thái yêu cầu hỗ trợ

### 3. General (Chung)
- Icon: 📢
- Màu: Xám
- Thông báo chung của hệ thống

### 4. Promotion (Khuyến mãi)
- Icon: 🎉
- Màu: Vàng
- Thông báo khuyến mãi, ưu đãi

## Tính năng

### 1. Real-time Updates
- Cập nhật số lượng thông báo chưa đọc tự động
- Không cần refresh trang

### 2. Responsive Design
- Giao diện thân thiện với mobile
- Dropdown thông báo responsive

### 3. Pagination
- Phân trang cho danh sách thông báo
- Hiển thị 20 thông báo mỗi trang

### 4. Search & Filter
- Có thể mở rộng thêm tính năng tìm kiếm và lọc

## Bảo mật

### 1. Authentication
- Tất cả API yêu cầu đăng nhập
- Kiểm tra quyền truy cập

### 2. Data Validation
- Validate input data
- Sanitize user input

### 3. Error Handling
- Xử lý lỗi gracefully
- Log lỗi để debug

## Mở rộng

### 1. Email Notifications
- Có thể tích hợp gửi email kèm thông báo
- Sử dụng PHPMailer hoặc SendGrid

### 2. Push Notifications
- Tích hợp Service Worker cho push notifications
- Sử dụng Web Push API

### 3. SMS Notifications
- Tích hợp gửi SMS cho thông báo quan trọng
- Sử dụng Twilio hoặc Viettel SMS

### 4. WebSocket
- Real-time notifications với WebSocket
- Cập nhật tức thì không cần refresh

## Troubleshooting

### 1. Thông báo không hiển thị
- Kiểm tra database connection
- Kiểm tra user_id có đúng không
- Kiểm tra JavaScript console có lỗi không

### 2. API không hoạt động
- Kiểm tra file permissions
- Kiểm tra PHP error logs
- Kiểm tra database permissions

### 3. Performance
- Thêm index cho các trường thường query
- Xóa thông báo cũ định kỳ
- Sử dụng caching nếu cần

## Changelog

### Version 1.0.0
- ✅ Tạo bảng notifications
- ✅ API quản lý thông báo
- ✅ Giao diện hiển thị thông báo
- ✅ Tích hợp với đơn hàng và liên hệ
- ✅ Icon chuông thông báo trong header
- ✅ Real-time updates
