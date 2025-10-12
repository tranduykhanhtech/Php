<?php
require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin';
    } else {
        $stmt = $pdo->prepare("SELECT id, username, email, password, full_name, role, is_locked, suspension_until FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Check account lock/suspension
            if (!empty($user['is_locked'])) {
                $error = 'Tài khoản của bạn đã bị khóa vĩnh viễn. Vui lòng liên hệ quản trị viên.';
            } elseif (!empty($user['suspension_until']) && strtotime($user['suspension_until']) > time()) {
                $remaining = strtotime($user['suspension_until']) - time();
                $days = floor($remaining / 86400);
                $hours = floor(($remaining % 86400) / 3600);
                $mins = floor(($remaining % 3600) / 60);
                $parts = [];
                if ($days) $parts[] = $days . ' ngày';
                if ($hours) $parts[] = $hours . ' giờ';
                if ($mins) $parts[] = $mins . ' phút';
                $error = 'Tài khoản đang bị đình chỉ. Còn lại: ' . implode(' ', $parts) . ' (đến ' . date('d/m/Y H:i', strtotime($user['suspension_until'])) . ')';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['username'] = $user['username'];
            // If "remember me" checked, create a long-lived token stored hashed in DB
            if (!empty($_POST['remember-me'])) {
                $raw = bin2hex(random_bytes(32));
                $token_hash = hash_hmac('sha256', $raw, defined('APP_KEY') ? APP_KEY : hash('sha256', DB_PASS));
                $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                try {
                    $stmt = $pdo->prepare('INSERT INTO remember_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)');
                    $stmt->execute([$user['id'], $token_hash, $expires]);
                    // set cookie (HttpOnly, SameSite=Strict). cookie_secure already handled in config
                    setcookie('remember_token', $raw, time() + 60*60*24*30, '/', '', ini_get('session.cookie_secure'), true);
                } catch (Exception $e) {
                    error_log('Remember token insert failed: ' . $e->getMessage());
                }
            }

                redirect('index.php');
            }
        } else {
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng';
        }
    }
}

$page_title = 'Đăng nhập';
include 'includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-primary">
                <i class="fas fa-user text-white text-xl"></i>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Đăng nhập tài khoản
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Hoặc
                <a href="register.php" class="font-medium text-primary hover:text-green-600">
                    tạo tài khoản mới
                </a>
            </p>
        </div>
        
        <form class="mt-8 space-y-6" method="POST">
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
            
            <div class="rounded-md shadow-sm -space-y-px">
                <div>
                    <label for="username" class="sr-only">Tên đăng nhập hoặc Email</label>
                    <input id="username" name="username" type="text" required 
                           class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm" 
                           placeholder="Tên đăng nhập hoặc Email"
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                <div>
                    <label for="password" class="sr-only">Mật khẩu</label>
                    <input id="password" name="password" type="password" required 
                           class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm" 
                           placeholder="Mật khẩu">
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input id="remember-me" name="remember-me" type="checkbox" 
                           class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                    <label for="remember-me" class="ml-2 block text-sm text-gray-900">
                        Ghi nhớ đăng nhập
                    </label>
                </div>

                <div class="text-sm">
                    <a href="forgot-password.php" class="font-medium text-primary hover:text-green-600">
                        Quên mật khẩu?
                    </a>
                </div>
            </div>

            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-primary hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-lock text-green-500 group-hover:text-green-400"></i>
                    </span>
                    Đăng nhập
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
