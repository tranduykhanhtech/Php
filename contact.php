<?php
require_once 'config/database.php';
require_once 'includes/email_helper.php';

$page_title = 'Liên hệ';
$page_description = 'Liên hệ với Natural Cosmetics Shop để được tư vấn và hỗ trợ';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $subject = sanitize($_POST['subject']);
    $message = sanitize($_POST['message']);
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Vui lòng nhập đầy đủ thông tin bắt buộc';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ';
    } else {
        try {
            // Lưu thông tin liên hệ vào database
            $stmt = $pdo->prepare("INSERT INTO contacts (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, $subject, $message]);
            
            // Gửi email thông báo đến contact@gecko.io.vn (tùy chọn - có thể bật/tắt)
            // sendContactNotification($name, $email, $phone, $subject, $message);

            $success = 'Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi sớm nhất có thể. Nếu bạn cần hỗ trợ gấp, vui lòng gửi email đến contact@gecko.io.vn.';

            // Clear form data after successful submission
            $_POST = array();
        } catch (Exception $e) {
            $error = 'Đã có lỗi xảy ra. Vui lòng thử lại sau.';
            error_log('Contact form error: ' . $e->getMessage());
        }
    }
}

include 'includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Header -->
    <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">Liên hệ với chúng tôi</h1>
        <p class="text-xl text-gray-600">Chúng tôi luôn sẵn sàng hỗ trợ và tư vấn cho bạn</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        <!-- Contact Form -->
        <div class="bg-white rounded-lg shadow-md p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Gửi tin nhắn</h2>
            
            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-md mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-50 border border-green-200 text-green-600 px-4 py-3 rounded-md mb-6">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Họ và tên *</label>
                        <input type="text" id="name" name="name" required
                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                        <input type="email" id="email" name="email" required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                </div>
                
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Số điện thoại</label>
                    <input type="tel" id="phone" name="phone"
                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                
                <div>
                    <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">Chủ đề *</label>
                    <select id="subject" name="subject" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">Chọn chủ đề</option>
                        <option value="product_inquiry" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'product_inquiry') ? 'selected' : ''; ?>>Tư vấn sản phẩm</option>
                        <option value="order_support" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'order_support') ? 'selected' : ''; ?>>Hỗ trợ đơn hàng</option>
                        <option value="complaint" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'complaint') ? 'selected' : ''; ?>>Khiếu nại</option>
                        <option value="partnership" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'partnership') ? 'selected' : ''; ?>>Hợp tác</option>
                        <option value="other" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'other') ? 'selected' : ''; ?>>Khác</option>
                    </select>
                </div>
                
                <div>
                    <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Nội dung tin nhắn *</label>
                    <textarea id="message" name="message" rows="6" required
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                </div>
                
                <button type="submit" 
                        class="w-full bg-primary text-white py-3 px-4 rounded-md font-semibold hover:bg-green-600 transition-colors">
                    <i class="fas fa-paper-plane mr-2"></i>Gửi tin nhắn
                </button>
            </form>
        </div>

        <!-- Contact Information -->
        <div class="space-y-8">
            <!-- Contact Details -->
            <div class="bg-white rounded-lg shadow-md p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Thông tin liên hệ</h2>
                
                <div class="space-y-6">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-primary bg-opacity-10 rounded-full flex items-center justify-center">
                                <i class="fas fa-map-marker-alt text-primary"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900">Địa chỉ</h3>
                            <p class="text-gray-600">123 Đường ABC, Quận XYZ, TP.HCM</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-primary bg-opacity-10 rounded-full flex items-center justify-center">
                                <i class="fas fa-phone text-primary"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900">Điện thoại</h3>
                            <p class="text-gray-600">0123 456 789</p>
                            <p class="text-sm text-gray-500">Thứ 2 - Chủ nhật: 8:00 - 22:00</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-primary bg-opacity-10 rounded-full flex items-center justify-center">
                                <i class="fas fa-envelope text-primary"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900">Email</h3>
                            <p class="text-gray-600">info@naturalcosmetics.com</p>
                            <p class="text-sm text-gray-500">Phản hồi trong vòng 24 giờ</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Social Media -->
            <div class="bg-white rounded-lg shadow-md p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Theo dõi chúng tôi</h2>
                
                <div class="flex space-x-4">
                    <a href="#" class="w-12 h-12 bg-blue-600 text-white rounded-full flex items-center justify-center hover:bg-blue-700 transition-colors">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="w-12 h-12 bg-pink-600 text-white rounded-full flex items-center justify-center hover:bg-pink-700 transition-colors">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="w-12 h-12 bg-red-600 text-white rounded-full flex items-center justify-center hover:bg-red-700 transition-colors">
                        <i class="fab fa-youtube"></i>
                    </a>
                    <a href="#" class="w-12 h-12 bg-gray-800 text-white rounded-full flex items-center justify-center hover:bg-gray-900 transition-colors">
                        <i class="fab fa-tiktok"></i>
                    </a>
                </div>
            </div>

            <!-- FAQ -->
            <div class="bg-white rounded-lg shadow-md p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Câu hỏi thường gặp</h2>
                
                <div class="space-y-4">
                    <div>
                        <h3 class="font-semibold text-gray-900 mb-2">Làm thế nào để đặt hàng?</h3>
                        <p class="text-sm text-gray-600">Bạn có thể đặt hàng trực tuyến trên website hoặc gọi điện đến hotline của chúng tôi.</p>
                    </div>
                    
                    <div>
                        <h3 class="font-semibold text-gray-900 mb-2">Thời gian giao hàng là bao lâu?</h3>
                        <p class="text-sm text-gray-600">Chúng tôi giao hàng trong vòng 1-3 ngày làm việc tại TP.HCM và 3-5 ngày tại các tỉnh thành khác.</p>
                    </div>
                    
                    <div>
                        <h3 class="font-semibold text-gray-900 mb-2">Có chính sách đổi trả không?</h3>
                        <p class="text-sm text-gray-600">Chúng tôi có chính sách đổi trả trong vòng 30 ngày nếu sản phẩm còn nguyên vẹn.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
