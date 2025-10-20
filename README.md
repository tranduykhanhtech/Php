# Natural Cosmetics Shop

Website bán mỹ phẩm thiên nhiên được xây dựng bằng PHP và MySQL với giao diện hiện đại sử dụng TailwindCSS.

## Khóa và ko cho sửa URL dù có là admin, check unit số điện thoại trùng nhau, regex cho mật khẩu và số điện thoại, email

## Tính năng chính

### 🛍️ Cửa hàng trực tuyến
- Trang chủ với sản phẩm nổi bật và bán chạy
- Danh sách sản phẩm với bộ lọc và tìm kiếm
- Chi tiết sản phẩm với hình ảnh và thông tin đầy đủ
- Phân loại sản phẩm theo danh mục
- Giỏ hàng và thanh toán trực tuyến

### 👤 Hệ thống người dùng
- Đăng ký/Đăng nhập
- Quản lý thông tin cá nhân
- Lịch sử đơn hàng
- Phân quyền admin/customer

### 📝 Hệ thống blog
- Đăng bài viết về làm đẹp tự nhiên
- Phân loại bài viết theo chủ đề
- Tìm kiếm bài viết
- Chia sẻ mạng xã hội

### 🔧 Trang quản trị
- Dashboard với thống kê tổng quan
- Quản lý sản phẩm (thêm/sửa/xóa)
- Quản lý đơn hàng và cập nhật trạng thái
- Quản lý danh mục sản phẩm
- Quản lý bài viết blog
- Quản lý khách hàng

## Công nghệ sử dụng

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript, TailwindCSS
- **Icons**: Font Awesome
- **Charts**: Chart.js

## Cài đặt

### Yêu cầu hệ thống
- PHP 7.4 hoặc cao hơn
- MySQL 5.7 hoặc cao hơn
- Web server (Apache/Nginx)
- Composer (tùy chọn)

### Hướng dẫn cài đặt

1. **Clone repository**
```bash
git clone <repository-url>
cd shop
```

2. **Cấu hình database**
- Tạo database mới trong MySQL
- Import file `database.sql` để tạo cấu trúc database
- Cập nhật thông tin kết nối trong `config/database.php`

3. **Cấu hình web server**
- Đặt thư mục dự án vào thư mục web root
- Cấu hình virtual host (nếu cần)
- Đảm bảo PHP có quyền ghi vào thư mục `uploads/`

4. **Cấu hình website**
- Cập nhật `SITE_URL` trong `config/database.php`
- Tạo thư mục `uploads/` và cấp quyền ghi
- Cấu hình email (nếu cần gửi email)

5. **Truy cập website**
- Mở trình duyệt và truy cập URL của website
- Đăng nhập admin với tài khoản mặc định:
  - Username: admin
  - Password: password

## Cấu trúc thư mục

```
shop/
├── admin/                 # Trang quản trị
│   ├── includes/         # Header/footer admin
│   ├── index.php         # Dashboard
│   ├── products.php      # Quản lý sản phẩm
│   ├── orders.php        # Quản lý đơn hàng
│   └── ...
├── api/                  # API endpoints
├── config/               # Cấu hình
│   └── database.php      # Kết nối database
├── includes/             # Header/footer chung
├── uploads/              # Thư mục upload (tự tạo)
├── index.php             # Trang chủ
├── products.php          # Danh sách sản phẩm
├── product.php           # Chi tiết sản phẩm
├── cart.php              # Giỏ hàng
├── checkout.php          # Thanh toán
├── blog.php              # Danh sách bài viết
├── post.php              # Chi tiết bài viết
├── about.php             # Giới thiệu
├── contact.php           # Liên hệ
├── login.php             # Đăng nhập
├── register.php          # Đăng ký
├── database.sql          # Cấu trúc database
└── README.md             # Hướng dẫn
```

## Tính năng chi tiết

### 🏠 Trang chủ
- Hero section với thông điệp chính
- Danh mục sản phẩm
- Sản phẩm nổi bật
- Sản phẩm bán chạy
- Bài viết mới nhất
- Newsletter đăng ký

### 🛒 Hệ thống mua sắm
- Tìm kiếm sản phẩm
- Lọc theo danh mục, giá, trạng thái
- Sắp xếp sản phẩm
- Giỏ hàng với cập nhật real-time
- Thanh toán với nhiều phương thức
- Quản lý đơn hàng

### 📱 Responsive Design
- Giao diện thân thiện với mobile
- Menu responsive
- Grid layout linh hoạt
- Touch-friendly interface

### 🔐 Bảo mật
- Xác thực người dùng
- Bảo vệ CSRF
- Sanitize input data
- Prepared statements
- Session security

## Tùy chỉnh

### Thay đổi màu sắc
Chỉnh sửa cấu hình TailwindCSS trong file header:
```javascript
tailwind.config = {
    theme: {
        extend: {
            colors: {
                primary: '#10B981',    // Màu chính
                secondary: '#F59E0B',  // Màu phụ
                accent: '#EC4899'      // Màu nhấn
            }
        }
    }
}
```

### Thêm phương thức thanh toán
1. Cập nhật enum trong database
2. Thêm option trong form checkout
3. Xử lý logic thanh toán tương ứng

### Tùy chỉnh email
Cập nhật cấu hình email trong `config/database.php` và thêm logic gửi email.

## Hỗ trợ

Nếu bạn gặp vấn đề hoặc có câu hỏi, vui lòng:
1. Kiểm tra log lỗi của web server
2. Đảm bảo cấu hình database đúng
3. Kiểm tra quyền ghi file
4. Liên hệ qua email: support@naturalcosmetics.com

## License

Dự án này được phát hành dưới giấy phép MIT. Xem file LICENSE để biết thêm chi tiết.

## Đóng góp

Chúng tôi hoan nghênh mọi đóng góp! Vui lòng:
1. Fork repository
2. Tạo feature branch
3. Commit changes
4. Push to branch
5. Tạo Pull Request

---

**Natural Cosmetics Shop** - Mỹ phẩm thiên nhiên chất lượng cao
