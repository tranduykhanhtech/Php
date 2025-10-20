# 🔔 Browser Push Notifications - Hướng Dẫn

## Tính năng đã thêm chưa real-time

Hệ thống thông báo trình duyệt (Browser Push Notifications) cho phép người dùng nhận thông báo ngay trên trình duyệt khi có cập nhật mới, ngay cả khi không đang xem trang thông báo.

---

## 📋 Các tính năng chính

### 1. **Thông báo tự động**
- Tự động kiểm tra thông báo mới mỗi 10 giây
- Hiển thị popup thông báo ngay trên màn hình
- Có âm thanh và rung (vibrate) khi có thông báo mới
- Click vào thông báo để xem chi tiết

### 2. **Các loại thông báo**
- 🛍️ **Đơn hàng**: Xác nhận, vận chuyển, giao hàng
- 💬 **Liên hệ**: Phản hồi hỗ trợ
- 🎉 **Khuyến mãi**: Flash sale, voucher, điểm thưởng
- 📢 **Chung**: Tin tức, cập nhật chính sách

### 3. **Cài đặt linh hoạt**
- Trang cài đặt riêng: `/notification-settings.php`
- Bật/tắt thông báo dễ dàng
- Gửi thông báo thử để kiểm tra
- Hiển thị trạng thái quyền thông báo

---

## 🚀 Cách sử dụng

### **Đối với người dùng:**

1. **Đăng nhập vào website**
2. **Cấp quyền thông báo:**
   - Sau 2 giây, trình duyệt sẽ hỏi "Cho phép gecko.io.vn gửi thông báo?"
   - Click **"Cho phép"** (Allow)
3. **Nhận thông báo:**
   - Khi có thông báo mới, popup sẽ hiển thị
   - Click vào popup để xem chi tiết
   - Thông báo tự động đóng sau 10 giây

### **Quản lý thông báo:**

1. Vào **Cài đặt thông báo**: `https://gecko.io.vn/notification-settings.php`
2. **Bật/Tắt** thông báo
3. **Gửi thông báo thử** để kiểm tra

---

## 🔧 Cấu trúc kỹ thuật

### **File đã tạo:**

```
/home/tranduykhanh/Khanh/shop/
├── assets/
│   └── js/
│       └── browser-notification.js    # Core notification logic
├── notification-settings.php          # Trang cài đặt
└── notifications.php                  # Đã thêm nút "Cài đặt"
```

### **File đã chỉnh sửa:**

```
includes/header.php                    # Thêm script browser-notification.js
api/notifications.php                  # Thêm filter unread_only
```

---

## 📱 Trình duyệt hỗ trợ

✅ **Hỗ trợ đầy đủ:**
- Chrome/Edge (Desktop & Mobile)
- Firefox (Desktop & Mobile)
- Safari (Desktop & Mobile từ iOS 16.4+)
- Opera
- Brave

❌ **Không hỗ trợ:**
- IE (đã ngừng hỗ trợ)
- Safari iOS < 16.4

---

## 🎯 Cách hoạt động

### **Flow thông báo:**

```
1. User đăng nhập
   ↓
2. Sau 2s, yêu cầu quyền notification
   ↓
3. User cho phép
   ↓
4. Bắt đầu polling (10s/lần)
   ↓
5. API trả về notification mới (unread)
   ↓
6. So sánh với lastNotificationId
   ↓
7. Nếu có mới → Hiển thị popup + âm thanh
   ↓
8. User click → Chuyển đến trang chi tiết
```

### **API Endpoints:**

```javascript
// Lấy tất cả notifications
GET /api/notifications.php?page=1&limit=20

// Lấy chỉ unread notifications
GET /api/notifications.php?unread_only=1&page=1&limit=5

// Đánh dấu đã đọc
POST /api/notifications.php
Body: { notification_id: 123 }

// Xóa notification
DELETE /api/notifications.php?id=123
```

---

## 💻 Code Example

### **Khởi tạo notification:**

```javascript
// Auto init (đã có trong browser-notification.js)
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('notification-button')) {
        setTimeout(() => {
            window.browserNotification.init();
        }, 2000);
    }
});
```

### **Gửi notification thủ công:**

```javascript
// Gửi notification đơn giản
window.browserNotification.show('Tiêu đề', {
    body: 'Nội dung thông báo',
    icon: '/favicon-32x32.png',
    url: '/notifications.php'
});
```

### **Tắt notification:**

```javascript
window.browserNotification.stopPolling();
```

---

## 🔒 Bảo mật & Privacy

✅ **An toàn:**
- Chỉ hoạt động khi user đã đăng nhập
- Chỉ gửi notification cho user có quyền
- Không lưu trữ token hay thông tin nhạy cảm
- User có thể tắt bất cứ lúc nào

✅ **Không spam:**
- Chỉ thông báo quan trọng (đơn hàng, hỗ trợ)
- Giới hạn tần suất (10s polling)
- User kiểm soát hoàn toàn

---

## 🐛 Troubleshooting

### **Không nhận được thông báo?**

1. **Kiểm tra quyền:**
   - Chrome: Settings → Privacy → Site Settings → Notifications
   - Firefox: Preferences → Privacy → Permissions → Notifications
   - Safari: Preferences → Websites → Notifications

2. **Kiểm tra trạng thái:**
   - Vào `/notification-settings.php`
   - Xem trạng thái hiện tại
   - Gửi thông báo thử

3. **Hard refresh:**
   - Ctrl+Shift+R (Windows/Linux)
   - Cmd+Shift+R (Mac)

4. **Xóa cache:**
   - Ctrl+Shift+Delete
   - Xóa cache và reload

### **Permission bị denied?**

Hướng dẫn bật lại:

**Chrome:**
1. Click icon khóa/thông tin (trái URL)
2. Site settings
3. Notifications → Allow

**Firefox:**
1. Click icon khóa
2. More information
3. Permissions → Notifications → Allow

**Safari:**
1. Safari → Settings
2. Websites → Notifications
3. gecko.io.vn → Allow

---

## 📊 Testing

### **Test notification:**

1. Đăng nhập website
2. Vào `/notification-settings.php`
3. Click "Gửi thông báo thử"
4. Kiểm tra popup có hiển thị không

### **Test polling:**

```javascript
// Trong console trình duyệt
console.log(window.browserNotification.lastNotificationId);

// Test ngay
window.browserNotification.fetchNewNotifications();
```

---

## 🎨 Customization

### **Thay đổi tần suất check:**

```javascript
// Trong browser-notification.js
this.checkInterval = 10000; // 10 giây (default)
// Đổi thành 30000 = 30 giây
```

### **Thay đổi icon:**

```javascript
// Trong browser-notification.js, method show()
const defaultOptions = {
    icon: '/your-custom-icon.png',  // Đổi icon
    badge: '/your-badge.png',
    ...
};
```

### **Tắt âm thanh:**

```javascript
// Comment dòng này trong showNotificationFromData()
// this.playNotificationSound();
```

---

## 📈 Analytics (Tương lai)

Có thể thêm tracking:
- Số notification đã gửi
- Click-through rate
- Conversion rate
- A/B testing notification styles

---

## ✅ Checklist triển khai

- [x] Tạo file `browser-notification.js`
- [x] Thêm script vào header
- [x] Cập nhật API notifications.php
- [x] Tạo trang notification-settings.php
- [x] Thêm nút cài đặt trong notifications.php
- [x] Test trên Chrome/Firefox/Safari
- [x] Viết documentation

---

## 🚀 Deployment

```bash
# Upload files
cd /home/tranduykhanh/Khanh/shop

# Check files exist
ls -la assets/js/browser-notification.js
ls -la notification-settings.php

# Test syntax
php -l notification-settings.php
php -l api/notifications.php
php -l notifications.php

# Set permissions
chmod 644 assets/js/browser-notification.js
chmod 644 notification-settings.php

# Test on browser
# Visit: https://gecko.io.vn/notification-settings.php
```

---

## 🌐 URLs

- **Trang thông báo:** https://gecko.io.vn/notifications.php
- **Cài đặt:** https://gecko.io.vn/notification-settings.php
- **API:** https://gecko.io.vn/api/notifications.php

---

✅ **Hoàn tất!** User giờ sẽ nhận thông báo realtime trên trình duyệt! 🎉
