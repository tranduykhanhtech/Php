<?php
require_once 'config/database.php';
require_once 'includes/email_helper.php';

$page_title = 'Đặt lại mật khẩu';
$page_description = 'Nhập mã OTP và mật khẩu mới';

// Kiểm tra session có email không (phải từ trang forgot-password)
if (!isset($_SESSION['reset_email'])) {
    $_SESSION['error'] = 'Vui lòng yêu cầu đặt lại mật khẩu trước';
    redirect('forgot-password.php');
}

$email = $_SESSION['reset_email'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $otp = sanitize($_POST['otp']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($otp) || empty($new_password) || empty($confirm_password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin';
    } elseif (strlen($otp) != 6 || !ctype_digit($otp)) {
        $error = 'Mã OTP phải là 6 chữ số';
    } elseif (strlen($new_password) < 6) {
        $error = 'Mật khẩu mới phải có ít nhất 6 ký tự';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp';
    } else {
        // Xác thực OTP
        if (verifyPasswordResetOTP($email, $otp)) {
            // Cập nhật mật khẩu
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            
            if ($stmt->execute([$hashed_password, $email])) {
                // Đánh dấu OTP đã sử dụng
                markOTPAsUsed($email, $otp);
                
                // Xóa session reset
                unset($_SESSION['reset_email']);
                
                $success = 'Đặt lại mật khẩu thành công! Đang chuyển hướng đến trang đăng nhập...';
                header('refresh:2;url=login.php');
            } else {
                $error = 'Có lỗi xảy ra. Vui lòng thử lại.';
            }
        } else {
            $error = 'Mã OTP không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu mã mới.';
        }
    }
}

include 'includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center bg-gray-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="flex justify-center">
                <i class="fas fa-key text-primary text-5xl"></i>
            </div>
            <h2 class="mt-6 text-center text-3xl font-bold text-gray-900">
                Đặt lại mật khẩu
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Email: <strong><?php echo htmlspecialchars($email); ?></strong>
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
            </div>
        <?php endif; ?>
        
        <form method="POST" class="mt-8 space-y-6">
            <!-- OTP Input -->
            <div>
                <label for="otp" class="block text-sm font-medium text-gray-700 mb-2">
                    Mã OTP (6 chữ số)
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-shield-alt text-gray-400"></i>
                    </div>
                    <input type="text" id="otp" name="otp" required
                           maxlength="6" pattern="[0-9]{6}"
                           value="<?php echo isset($_POST['otp']) ? htmlspecialchars($_POST['otp']) : ''; ?>"
                           class="appearance-none relative block w-full px-3 py-3 pl-10 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary focus:z-10 sm:text-sm tracking-widest text-center text-2xl font-bold"
                           placeholder="000000">
                </div>
                <p class="mt-2 text-xs text-gray-500">
                    <i class="fas fa-clock mr-1"></i>
                    Mã OTP có hiệu lực trong 10 phút
                </p>
            </div>
            
            <!-- New Password -->
            <div>
                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">
                    Mật khẩu mới
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-lock text-gray-400"></i>
                    </div>
                    <input type="password" id="new_password" name="new_password" required
                           minlength="6"
                           class="appearance-none relative block w-full px-3 py-3 pl-10 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary focus:z-10 sm:text-sm"
                           placeholder="Ít nhất 6 ký tự">
                </div>
            </div>
            
            <!-- Confirm Password -->
            <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                    Xác nhận mật khẩu mới
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-lock text-gray-400"></i>
                    </div>
                    <input type="password" id="confirm_password" name="confirm_password" required
                           minlength="6"
                           class="appearance-none relative block w-full px-3 py-3 pl-10 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary focus:z-10 sm:text-sm"
                           placeholder="Nhập lại mật khẩu">
                </div>
            </div>
            
            <div>
                <button type="submit"
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-primary hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-check text-white group-hover:text-green-100"></i>
                    </span>
                    Đặt lại mật khẩu
                </button>
            </div>
            
            <div class="flex items-center justify-between">
                <div class="text-sm">
                    <a href="forgot-password.php" class="font-medium text-primary hover:text-green-600">
                        <i class="fas fa-redo mr-1"></i>
                        Gửi lại mã OTP
                    </a>
                </div>
                <div class="text-sm">
                    <a href="login.php" class="font-medium text-primary hover:text-green-600">
                        Đăng nhập
                        <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
        </form>
        
        <!-- Password requirements -->
        <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-yellow-600 text-xl"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">Yêu cầu mật khẩu</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <ul class="list-disc list-inside space-y-1">
                            <li>Tối thiểu 6 ký tự</li>
                            <li>Nên kết hợp chữ hoa, chữ thường và số</li>
                            <li>Không sử dụng mật khẩu dễ đoán</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto format OTP input
document.getElementById('otp').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
});

// Password match validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('new_password').value;
    const confirm = this.value;
    
    if (confirm && password !== confirm) {
        this.setCustomValidity('Mật khẩu không khớp');
    } else {
        this.setCustomValidity('');
    }
});
</script>

<?php include 'includes/footer.php'; ?>
