<?php
// Load .env file if exists (simple parser)
$envFile = __DIR__ . '/../.env';
if (is_readable($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($k, $v) = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v);
        if ($k && getenv($k) === false) putenv("$k=$v");
    }
}

// Cấu hình cơ sở dữ liệu (prefer environment variables)
define('DB_HOST', getenv('DB_HOST') ?: 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com');
define('DB_NAME', getenv('DB_NAME') ?: 'test');
define('DB_USER', getenv('DB_USER') ?: 'MjfP2EMHgHGt8hY.root');
define('DB_PASS', getenv('DB_PASS') ?: 'Hd88K8d4ypAiX1b0');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');
define('DB_PORT', (int)(getenv('DB_PORT') ?: 4000));

// Cấu hình website
define('SITE_URL', getenv('SITE_URL') ?: 'http://localhost:8000');
define('SITE_NAME', 'Gecko Shop');
define('ADMIN_EMAIL', 'admin@gecko.io.vn');

// Cấu hình bảo mật
define('APP_KEY', 'your-secret-app-key-change-this-in-production');

// Cấu hình upload
define('UPLOAD_PATH', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Cấu hình session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
// Enable cookie_secure only when running over HTTPS. On local http (e.g. http://localhost) this would prevent
// the session cookie from being set, causing login/permission checks to fail.
$is_https = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
    (defined('SITE_URL') && strpos(SITE_URL, 'https://') === 0)
);
ini_set('session.cookie_secure', $is_https ? 1 : 0);
ini_set('session.cookie_samesite', 'Strict'); // Bảo mật CSRF

// Khởi tạo session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kết nối database (TLS bắt buộc với TiDB Cloud)
try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET . ";sslMode=VERIFY_IDENTITY";

    // Thử tìm CA bundle hệ thống
    $sslOptions = [];
    foreach ([
        '/etc/ssl/certs/ca-certificates.crt',           // Debian/Ubuntu
        '/etc/pki/tls/certs/ca-bundle.crt',             // CentOS/RHEL/Amazon Linux
        '/usr/local/etc/openssl/cert.pem'               // macOS/Homebrew
    ] as $caPath) {
        if (is_readable($caPath)) {
            $sslOptions[PDO::MYSQL_ATTR_SSL_CA] = $caPath;
            break;
        }
    }

    $pdo = new PDO(
        $dsn,
        DB_USER,
        DB_PASS,
        array_replace([
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ], $sslOptions)
    );
} catch (PDOException $e) {
    die("Kết nối database thất bại: " . $e->getMessage());
}

    // --- Remember-me token support -------------------------------------------------
    // Simple table to store long-lived remember tokens. We create it if missing so
    // developers don't need a migration step in local dev.
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS remember_tokens (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            token_hash VARCHAR(128) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (user_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    } catch (Exception $e) {
        // ignore creation errors on managed DBs; functionality will still work if table exists
    }

    // Application key for HMAC; use DB_PASS as fallback but you can override by defining APP_KEY.
    if (!defined('APP_KEY')) define('APP_KEY', hash('sha256', DB_PASS));

// Cloudinary config (read from env if present)
define('CLOUDINARY_CLOUD_NAME', getenv('CLOUDINARY_CLOUD_NAME') ?: '');
define('CLOUDINARY_API_KEY', getenv('CLOUDINARY_API_KEY') ?: '');
define('CLOUDINARY_API_SECRET', getenv('CLOUDINARY_API_SECRET') ?: '');

// SMTP config (read from env if present)
define('SMTP_HOST', getenv('SMTP_HOST') ?: '');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: 'noreply@gecko.io.vn');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'Gecko Shop');

    // If not logged in but a remember cookie exists, try to rehydrate the session.
    if (!isset($_SESSION['user_id']) && !empty($_COOKIE['remember_token'])) {
        try {
            $raw = $_COOKIE['remember_token'];
            $token_hash = hash_hmac('sha256', $raw, APP_KEY);
            $stmt = $pdo->prepare('SELECT rt.user_id, u.full_name, u.role, u.is_locked, u.suspension_until FROM remember_tokens rt JOIN users u ON rt.user_id = u.id WHERE rt.token_hash = ? AND rt.expires_at > NOW() LIMIT 1');
            $stmt->execute([$token_hash]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                // restore minimal session info
                // Check lock/suspension
                if (!empty($row['is_locked']) || (!empty($row['suspension_until']) && strtotime($row['suspension_until']) > time())) {
                    // token valid but account suspended/locked: clear cookie and do not restore
                    setcookie('remember_token', '', time() - 3600, '/');
                } else {
                    $_SESSION['user_id'] = $row['user_id'];
                    $_SESSION['user_name'] = $row['full_name'];
                    $_SESSION['user_role'] = $row['role'];
                }
            } else {
                // token invalid/expired: clear cookie
                setcookie('remember_token', '', time() - 3600, '/');
            }
        } catch (Exception $e) {
            // don't break the site on token errors
            error_log('Remember-me error: ' . $e->getMessage());
        }
    }

// Hàm helper
function redirect($url) {
    header("Location: " . SITE_URL . "/" . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

// Enhanced requireLogin: also check if account is locked/suspended and invalidate session if so
function requireLoginChecked() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }

    global $pdo;
    try {
        $stmt = $pdo->prepare('SELECT is_locked, suspension_until FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$_SESSION['user_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            if (!empty($row['is_locked'])) {
                // destroy session and redirect with message
                session_unset(); session_destroy();
                session_start();
                $_SESSION['error'] = 'Tài khoản của bạn đã bị khóa vĩnh viễn.';
                redirect('login.php');
            }
            if (!empty($row['suspension_until']) && strtotime($row['suspension_until']) > time()) {
                session_unset(); session_destroy();
                session_start();
                $remaining = strtotime($row['suspension_until']) - time();
                $days = floor($remaining / 86400);
                $hours = floor(($remaining % 86400) / 3600);
                $mins = floor(($remaining % 3600) / 60);
                $parts = [];
                if ($days) $parts[] = $days . ' ngày';
                if ($hours) $parts[] = $hours . ' giờ';
                if ($mins) $parts[] = $mins . ' phút';
                $_SESSION['error'] = 'Tài khoản đang bị đình chỉ. Còn lại: ' . implode(' ', $parts);
                redirect('login.php');
            }
        }
    } catch (Exception $e) {
        // ignore and allow access (fail-open)
    }
}

function requireAdmin() {
    requireLoginChecked();
    if (!isAdmin()) {
        // Non-admin users should be sent to the public homepage (not the admin index)
        // Check if we're in admin directory
        $current_dir = dirname($_SERVER['PHP_SELF']);
        if (strpos($current_dir, '/admin') !== false) {
            redirect('../index.php');
        } else {
            redirect('index.php');
        }
    }
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

function formatPrice($price) {
    return number_format($price, 0, ',', '.') . ' VNĐ';
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'vừa xong';
    if ($time < 3600) return floor($time/60) . ' phút trước';
    if ($time < 86400) return floor($time/3600) . ' giờ trước';
    if ($time < 2592000) return floor($time/86400) . ' ngày trước';
    if ($time < 31536000) return floor($time/2592000) . ' tháng trước';
    return floor($time/31536000) . ' năm trước';
}
?>
