<?php
require_once 'config/database.php';

requireLoginChecked();

$page_title = 'Thông tin cá nhân';

$error = '';
$success = '';

// Lấy thông tin người dùng hiện tại
$stmt = $pdo->prepare("SELECT username, email, full_name, phone, address FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = sanitize($_POST['full_name']);
        $phone = sanitize($_POST['phone']);
        $address = sanitize($_POST['address']);
        
        $upd = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, address = ?, updated_at = NOW() WHERE id = ?");
        if ($upd->execute([$full_name, $phone, $address, $_SESSION['user_id']])) {
            $_SESSION['user_name'] = $full_name;
            $success = 'Cập nhật thông tin thành công';
        } else {
            $error = 'Có lỗi xảy ra, vui lòng thử lại';
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (strlen($new_password) < 6) {
            $error = 'Mật khẩu mới phải có ít nhất 6 ký tự';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Xác nhận mật khẩu không khớp';
        } else {
            // Lấy password hash
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $row = $stmt->fetch();
            
            if (!$row || !password_verify($current_password, $row['password'])) {
                $error = 'Mật khẩu hiện tại không đúng';
            } else {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                if ($upd->execute([$new_hash, $_SESSION['user_id']])) {
                    $success = 'Đổi mật khẩu thành công';
                } else {
                    $error = 'Không thể đổi mật khẩu, vui lòng thử lại';
                }
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Thông tin cá nhân</h1>
    
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
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Profile form -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Cập nhật thông tin</h2>
            
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Họ và tên</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email (không thay đổi)</label>
                    <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled 
                           class="w-full px-3 py-2 border border-gray-200 rounded-md bg-gray-50">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Số điện thoại</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Địa chỉ</label>
                    <textarea name="address" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"><?php echo htmlspecialchars($user['address']); ?></textarea>
                </div>
                <div class="flex justify-end">
                    <button type="submit" name="update_profile" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-green-600">Lưu thay đổi</button>
                </div>
            </form>
        </div>
        
        <!-- Change password -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Đổi mật khẩu</h2>
            
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu hiện tại</label>
                    <input type="password" name="current_password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu mới</label>
                    <input type="password" name="new_password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Xác nhận mật khẩu</label>
                    <input type="password" name="confirm_password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div class="flex justify-end">
                    <button type="submit" name="change_password" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-green-600">Đổi mật khẩu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>


