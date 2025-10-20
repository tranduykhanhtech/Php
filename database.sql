-- Database cho website bán mỹ phẩm thiên nhiên
CREATE DATABASE IF NOT EXISTS test;
USE test;

-- Bảng người dùng
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role ENUM('customer', 'admin') DEFAULT 'customer',
    is_locked BOOLEAN DEFAULT FALSE,
    suspension_until TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng danh mục sản phẩm
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    image VARCHAR(255),
    parent_id INT NULL,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Bảng sản phẩm
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    description TEXT,
    short_description TEXT,
    price DECIMAL(10,2) NOT NULL,
    sale_price DECIMAL(10,2) NULL,
    sku VARCHAR(100) UNIQUE,
    stock_quantity INT DEFAULT 0,
    category_id INT NOT NULL,
    images JSON,
    ingredients TEXT,
    usage_instructions TEXT,
    weight VARCHAR(50),
    origin VARCHAR(100),
    is_featured BOOLEAN DEFAULT FALSE,
    is_bestseller BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    meta_title VARCHAR(200),
    meta_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Bảng giỏ hàng
CREATE TABLE cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product (user_id, product_id)
);

-- Bảng đơn hàng
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    customer_address TEXT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    shipping_fee DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    payment_method ENUM('cod', 'bank_transfer') NOT NULL,
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    order_status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Bảng chi tiết đơn hàng
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(200) NOT NULL,
    product_price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Bảng giao dịch thanh toán (Payment Transactions)
CREATE TABLE payment_transactions (
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
);

-- Bảng bài viết/blog
CREATE TABLE posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    content LONGTEXT NOT NULL,
    excerpt TEXT,
    featured_image VARCHAR(255),
    author_id INT NOT NULL,
    category VARCHAR(100),
    tags JSON,
    is_published BOOLEAN DEFAULT FALSE,
    meta_title VARCHAR(200),
    meta_description TEXT,
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Bảng voucher
CREATE TABLE vouchers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    type ENUM('percentage', 'fixed') NOT NULL,
    value DECIMAL(10,2) NOT NULL,
    min_order_amount DECIMAL(10,2) DEFAULT 0,
    max_discount DECIMAL(10,2) NULL,
    usage_limit INT NULL,
    used_count INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    starts_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng sử dụng voucher
CREATE TABLE voucher_usage (
    id INT PRIMARY KEY AUTO_INCREMENT,
    voucher_id INT NOT NULL,
    user_id INT NOT NULL,
    order_id INT NOT NULL,
    discount_amount DECIMAL(10,2) NOT NULL,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (voucher_id) REFERENCES vouchers(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    UNIQUE KEY unique_voucher_user_order (voucher_id, user_id, order_id)
);

-- Bảng remember tokens cho chức năng "Remember Me"
CREATE TABLE remember_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token_hash (token_hash),
    INDEX idx_expires_at (expires_at)
);

-- Bảng liên hệ từ khách hàng
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
);

-- Bảng thông báo
CREATE TABLE notifications (
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
);

-- Bảng cài đặt website
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Chèn dữ liệu mẫu
INSERT INTO users (username, email, password, full_name, role) VALUES 
('admin', 'admin@naturalcosmetics.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin');

INSERT INTO categories (name, slug, description) VALUES 
('Chăm sóc da mặt', 'cham-soc-da-mat', 'Các sản phẩm chăm sóc da mặt từ thiên nhiên'),
('Chăm sóc tóc', 'cham-soc-toc', 'Sản phẩm chăm sóc tóc tự nhiên'),
('Mỹ phẩm trang điểm', 'my-pham-trang-diem', 'Mỹ phẩm trang điểm từ thiên nhiên'),
('Chăm sóc cơ thể', 'cham-soc-co-the', 'Sản phẩm chăm sóc toàn thân'),
('Tinh dầu', 'tinh-dau', 'Các loại tinh dầu thiên nhiên');

-- Sản phẩm mẫu
INSERT INTO products (name, slug, description, short_description, price, sale_price, sku, stock_quantity, category_id, images, ingredients, usage_instructions, weight, origin, is_featured, is_bestseller, is_active, meta_title, meta_description)
VALUES
('Sữa rửa mặt trà xanh', 'sua-rua-mat-tra-xanh', 'Sữa rửa mặt chiết xuất trà xanh giúp làm sạch dịu nhẹ, kiểm soát dầu và giảm mụn.', 'Làm sạch dịu nhẹ, giảm nhờn và mụn.', 159000, 129000, 'SRM-TX-100', 120, 1, '["https://picsum.photos/seed/srm1/600/600","https://picsum.photos/seed/srm1b/600/600"]', 'Trà xanh, Glycerin, Vitamin B5', 'Làm ướt mặt, lấy lượng vừa đủ, massage 30s và rửa sạch.', '150ml', 'Việt Nam', 1, 1, 1, 'Sữa rửa mặt trà xanh', 'Sữa rửa mặt thiên nhiên chiết xuất trà xanh'),
('Toner hoa hồng', 'toner-hoa-hong', 'Toner cân bằng da chiết xuất hoa hồng phù hợp mọi loại da.', 'Cân bằng pH, cấp ẩm tức thì.', 179000, NULL, 'TON-HH-200', 80, 1, '["https://picsum.photos/seed/toner1/600/600"]', 'Nước hoa hồng, Hyaluronic Acid', 'Sau rửa mặt, thấm toner lên bông lau nhẹ toàn mặt.', '200ml', 'Hàn Quốc', 1, 0, 1, 'Toner hoa hồng', 'Toner hoa hồng cấp ẩm dịu nhẹ'),
('Serum Vitamin C 10%', 'serum-vitamin-c-10', 'Serum Vitamin C giúp sáng da và mờ thâm hiệu quả.', 'Sáng da, đều màu, giảm thâm.', 299000, 259000, 'SER-VC10-30', 60, 1, '["https://picsum.photos/seed/serum1/600/600"]', 'Vitamin C 10%, Niacinamide', 'Nhỏ 2-3 giọt, vỗ đều sau toner, dùng sáng/tối.', '30ml', 'Nhật Bản', 1, 1, 1, 'Serum Vitamin C', 'Serum Vitamin C 10% mờ thâm, sáng da'),
('Dầu gội bưởi', 'dau-goi-buoi', 'Dầu gội chiết xuất vỏ bưởi giúp giảm rụng tóc và kích thích mọc tóc.', 'Giảm rụng, tóc chắc khỏe.', 189000, 159000, 'DG-BUOI-300', 150, 2, '["https://picsum.photos/seed/dg1/600/600"]', 'Tinh dầu bưởi, Biotin', 'Làm ướt tóc, thoa dầu gội, massage và xả sạch.', '300ml', 'Việt Nam', 1, 0, 1, 'Dầu gội bưởi', 'Dầu gội thiên nhiên chiết xuất bưởi'),
('Dầu xả bưởi', 'dau-xa-buoi', 'Dầu xả dưỡng tóc mềm mượt từ tinh dầu bưởi.', 'Mềm mượt, giảm xơ rối.', 179000, NULL, 'DX-BUOI-300', 140, 2, '["https://picsum.photos/seed/dx1/600/600"]', 'Tinh dầu bưởi, Argan Oil', 'Sau gội, thoa dầu xả 3 phút và xả sạch.', '300ml', 'Việt Nam', 0, 0, 1, 'Dầu xả bưởi', 'Dầu xả bưởi dưỡng tóc mềm mượt'),
('Son dưỡng thiên nhiên', 'son-duong-thien-nhien', 'Son dưỡng môi từ bơ hạt mỡ và sáp ong giúp môi mềm mịn.', 'Dưỡng ẩm, mềm môi.', 99000, NULL, 'SON-DUONG-01', 300, 3, '["https://picsum.photos/seed/son1/600/600"]', 'Shea Butter, Beeswax, Vitamin E', 'Thoa trực tiếp lên môi khi khô.', '5g', 'Hàn Quốc', 1, 1, 1, 'Son dưỡng thiên nhiên', 'Son dưỡng môi mềm mịn từ thiên nhiên'),
('Phấn nước thiên nhiên', 'phan-nuoc-thien-nhien', 'Cushion che phủ tự nhiên, thoáng nhẹ, phù hợp da nhạy cảm.', 'Che phủ nhẹ, tự nhiên.', 359000, 319000, 'CUSH-NAT-01', 90, 3, '["https://picsum.photos/seed/cush1/600/600"]', 'Chiết xuất trà xanh, Bột khoáng', 'Dặm nhẹ lên da sau skincare.', '12g', 'Hàn Quốc', 0, 1, 1, 'Phấn nước thiên nhiên', 'Cushion thiên nhiên che phủ tự nhiên'),
('Sữa tắm yến mạch', 'sua-tam-yen-mach', 'Sữa tắm dịu nhẹ chiết xuất yến mạch cho da nhạy cảm.', 'Làm sạch dịu nhẹ, dưỡng ẩm.', 169000, NULL, 'ST-YM-500', 110, 4, '["https://picsum.photos/seed/suatam1/600/600"]', 'Yến mạch, Glycerin', 'Làm ướt da, tạo bọt và tắm sạch.', '500ml', 'Việt Nam', 1, 0, 1, 'Sữa tắm yến mạch', 'Sữa tắm thiên nhiên cho da nhạy cảm'),
('Kem dưỡng body bơ hạt mỡ', 'kem-duong-body-bo-hat-mo', 'Kem dưỡng thể chiết xuất bơ hạt mỡ giúp da mềm mịn.', 'Dưỡng ẩm sâu, mịn da.', 189000, 159000, 'KDB-BHM-250', 100, 4, '["https://picsum.photos/seed/body1/600/600"]', 'Shea Butter, Vitamin E', 'Thoa đều toàn thân sau khi tắm.', '250ml', 'Thái Lan', 0, 0, 1, 'Kem dưỡng body bơ hạt mỡ', 'Kem dưỡng thể mềm mịn da'),
('Tinh dầu oải hương', 'tinh-dau-oai-huong', 'Tinh dầu Lavender thư giãn, hỗ trợ giấc ngủ.', 'Thư giãn, ngủ ngon.', 129000, NULL, 'TD-LAV-10', 200, 5, '["https://picsum.photos/seed/td1/600/600"]', 'Tinh dầu oải hương nguyên chất', 'Khuếch tán 3-5 giọt hoặc pha loãng dùng ngoài da.', '10ml', 'Pháp', 1, 1, 1, 'Tinh dầu oải hương', 'Tinh dầu lavender thư giãn, nguyên chất');

INSERT INTO settings (setting_key, setting_value, description) VALUES 
('site_name', 'Natural Cosmetics Shop', 'Tên website'),
('site_description', 'Cửa hàng mỹ phẩm thiên nhiên chất lượng cao', 'Mô tả website'),
('contact_email', 'info@naturalcosmetics.com', 'Email liên hệ'),
('contact_phone', '0123456789', 'Số điện thoại liên hệ'),
('shipping_fee', '30000', 'Phí vận chuyển'),
('free_shipping_threshold', '500000', 'Ngưỡng miễn phí vận chuyển');

-- Voucher mẫu
INSERT INTO vouchers (code, name, description, type, value, min_order_amount, max_discount, usage_limit, is_active, starts_at, expires_at) VALUES 
('WELCOME10', 'Chào mừng 10%', 'Giảm 10% cho đơn hàng đầu tiên', 'percentage', 10, 200000, 50000, 100, 1, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY)),
('SAVE50K', 'Tiết kiệm 50K', 'Giảm 50,000 VNĐ cho đơn hàng từ 500K', 'fixed', 50000, 500000, NULL, 50, 1, NOW(), DATE_ADD(NOW(), INTERVAL 15 DAY)),
('VIP20', 'VIP 20%', 'Giảm 20% cho khách VIP', 'percentage', 20, 1000000, 200000, 20, 1, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY)),
('NEWYEAR15', 'Năm mới 15%', 'Chào năm mới với ưu đãi 15%', 'percentage', 15, 300000, 100000, 200, 1, NOW(), DATE_ADD(NOW(), INTERVAL 60 DAY));

-- Thêm indexes để cải thiện performance
CREATE INDEX idx_products_category_active ON products(category_id, is_active);
CREATE INDEX idx_products_name_search ON products(name);
CREATE INDEX idx_products_price ON products(price);
CREATE INDEX idx_products_created_at ON products(created_at);
CREATE INDEX idx_products_is_bestseller ON products(is_bestseller);
CREATE INDEX idx_products_stock ON products(stock_quantity);

CREATE INDEX idx_orders_user_id ON orders(user_id);
CREATE INDEX idx_orders_status ON orders(order_status);
CREATE INDEX idx_orders_created_at ON orders(created_at);
CREATE INDEX idx_orders_payment_status ON orders(payment_status);

CREATE INDEX idx_cart_user_id ON cart(user_id);
CREATE INDEX idx_cart_product_id ON cart(product_id);

CREATE INDEX idx_order_items_order_id ON order_items(order_id);
CREATE INDEX idx_order_items_product_id ON order_items(product_id);

CREATE INDEX idx_vouchers_code ON vouchers(code);
CREATE INDEX idx_vouchers_active ON vouchers(is_active);
CREATE INDEX idx_vouchers_expires ON vouchers(expires_at);

CREATE INDEX idx_voucher_usage_user_id ON voucher_usage(user_id);
CREATE INDEX idx_voucher_usage_voucher_id ON voucher_usage(voucher_id);

CREATE INDEX idx_posts_author ON posts(author_id);
CREATE INDEX idx_posts_status ON posts(is_published);
CREATE INDEX idx_posts_created_at ON posts(created_at);

CREATE INDEX idx_contacts_status ON contacts(status);
CREATE INDEX idx_contacts_created_at ON contacts(created_at);
CREATE INDEX idx_contacts_email ON contacts(email);
