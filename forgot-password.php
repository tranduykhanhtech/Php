<?php
require_once 'config/database.php';
require_once 'includes/email_helper.php';

$page_title = 'Quên mật khẩu';
$page_description = 'Đặt lại mật khẩu tài khoản của bạn';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize($_POST['email']);
    
    if (empty($email)) {
        $error = 'Vui lòng nhập địa chỉ email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ';
    } else {
        // Kiểm tra email có tồn tại trong hệ thống không
        $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $error = 'Email này chưa được đăng ký trong hệ thống';
        } else {
            // Generate OTP
            $otp = generateOTP();
            
            // Lưu OTP vào database
            if (savePasswordResetOTP($email, $otp, 10)) {
                // Gửi email
                if (sendPasswordResetOTP($email, $otp, $user['full_name'])) {
                    $success = 'Mã OTP đã được gửi đến email của bạn. Vui lòng kiểm tra hộp thư.';
                    // Lưu email vào session để dùng ở trang reset
                    $_SESSION['reset_email'] = $email;
                    // Chuyển hướng sau 2 giây
                    header('refresh:2;url=reset-password.php');
                } else {
                    $error = 'Không thể gửi email. Vui lòng thử lại sau.';
                }
            } else {
                $error = 'Có lỗi xảy ra. Vui lòng thử lại.';
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center bg-gray-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="flex justify-center">
                <i class="fas fa-leaf text-primary text-5xl"></i>
            </div>
            <h2 class="mt-6 text-center text-3xl font-bold text-gray-900">
                Quên mật khẩu
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Nhập email đăng ký để nhận mã OTP đặt lại mật khẩu
            </p>
        </div>
        
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-md">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-600 px-4 py-3 rounded-md">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo $success; ?>
                <p class="mt-2 text-sm">Đang chuyển hướng...</p>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="mt-8 space-y-6">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                    Địa chỉ Email
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-envelope text-gray-400"></i>
                    </div>
                    <input type="email" id="email" name="email" required
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                           class="appearance-none relative block w-full px-3 py-3 pl-10 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary focus:z-10 sm:text-sm"
                           placeholder="your@email.com">
                </div>
                <p class="mt-2 text-xs text-gray-500">
                    <i class="fas fa-info-circle mr-1"></i>
                    Chúng tôi sẽ gửi mã OTP 6 số đến email này
                </p>
            </div>
            
            <div>
                <button type="submit"
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-primary hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-paper-plane text-white group-hover:text-green-100"></i>
                    </span>
                    Gửi mã OTP
                </button>
            </div>
            
            <div class="flex items-center justify-between">
                <div class="text-sm">
                    <a href="login.php" class="font-medium text-primary hover:text-green-600">
                        <i class="fas fa-arrow-left mr-1"></i>
                        Quay lại đăng nhập
                    </a>
                </div>
                <div class="text-sm">
                    <a href="register.php" class="font-medium text-primary hover:text-green-600">
                        Đăng ký tài khoản
                        <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
        </form>
        
        <!-- Thông tin hỗ trợ -->
        <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-question-circle text-blue-600 text-xl"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">Cần hỗ trợ?</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <p>Nếu bạn không nhận được email, vui lòng:</p>
                        <ul class="list-disc list-inside mt-1 space-y-1">
                            <li>Kiểm tra thư mục Spam/Junk</li>
                            <li>Đợi vài phút và thử lại</li>
                            <li>Liên hệ: <?php echo ADMIN_EMAIL; ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
