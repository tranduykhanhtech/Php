<?php
require_once '../config/database.php';

header('Content-Type: application/json');

// Kiểm tra quyền admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

// Rate limiting: tối đa 10 uploads/phút
$user_id = $_SESSION['user_id'];
$cache_key = "upload_rate_limit_$user_id";
$current_time = time();
$rate_limit_window = 60; // 60 giây
$max_uploads = 10;

// Kiểm tra rate limit (sử dụng file cache đơn giản)
$cache_file = sys_get_temp_dir() . "/$cache_key";
if (file_exists($cache_file)) {
    $data = json_decode(file_get_contents($cache_file), true);
    if ($data && ($current_time - $data['start_time']) < $rate_limit_window) {
        if ($data['count'] >= $max_uploads) {
            http_response_code(429);
            echo json_encode(['success' => false, 'message' => 'Quá nhiều uploads. Vui lòng thử lại sau.']);
            exit;
        }
        $data['count']++;
    } else {
        $data = ['start_time' => $current_time, 'count' => 1];
    }
} else {
    $data = ['start_time' => $current_time, 'count' => 1];
}

file_put_contents($cache_file, json_encode($data));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Không có file được upload hoặc có lỗi']);
    exit;
}

$file = $_FILES['image'];
$upload_dir = '../uploads/products/';

// Kiểm tra kích thước file
if ($file['size'] > MAX_FILE_SIZE) {
    echo json_encode(['success' => false, 'message' => 'File quá lớn. Kích thước tối đa: ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB']);
    exit;
}

// Kiểm tra loại file
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$file_type = mime_content_type($file['tmp_name']);

if (!in_array($file_type, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Chỉ chấp nhận file ảnh (JPG, PNG, GIF, WebP). File hiện tại: ' . $file_type]);
    exit;
}

// Kiểm tra file có phải là ảnh thật không
$image_info = getimagesize($file['tmp_name']);
if ($image_info === false) {
    echo json_encode(['success' => false, 'message' => 'File không phải là hình ảnh hợp lệ']);
    exit;
}

// Tạo tên file unique
$file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$file_name = uniqid() . '_' . time() . '.' . $file_extension;
$file_path = $upload_dir . $file_name;

// Tạo thư mục nếu chưa có
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Di chuyển file
if (move_uploaded_file($file['tmp_name'], $file_path)) {
    // Tạo URL cho file
    $file_url = SITE_URL . '/uploads/products/' . $file_name;
    
    // Tạo thumbnail nếu cần
    $thumbnail_path = createThumbnail($file_path, $upload_dir . 'thumb_' . $file_name, 300, 300);
    $thumbnail_url = $thumbnail_path ? SITE_URL . '/uploads/products/thumb_' . $file_name : null;
    
    echo json_encode([
        'success' => true,
        'message' => 'Upload thành công',
        'file_name' => $file_name,
        'file_url' => $file_url,
        'thumbnail_url' => $thumbnail_url,
        'file_size' => $file['size']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Không thể lưu file']);
}

// Hàm tạo thumbnail
function createThumbnail($source_path, $dest_path, $width, $height) {
    $image_info = getimagesize($source_path);
    if (!$image_info) return false;
    
    $source_width = $image_info[0];
    $source_height = $image_info[1];
    $mime_type = $image_info['mime'];
    
    // Tạo image resource từ file gốc
    switch ($mime_type) {
        case 'image/jpeg':
            $source_image = imagecreatefromjpeg($source_path);
            break;
        case 'image/png':
            $source_image = imagecreatefrompng($source_path);
            break;
        case 'image/gif':
            $source_image = imagecreatefromgif($source_path);
            break;
        case 'image/webp':
            $source_image = imagecreatefromwebp($source_path);
            break;
        default:
            return false;
    }
    
    if (!$source_image) return false;
    
    // Tính toán kích thước thumbnail (giữ tỷ lệ)
    $ratio = min($width / $source_width, $height / $source_height);
    $new_width = intval($source_width * $ratio);
    $new_height = intval($source_height * $ratio);
    
    // Tạo thumbnail
    $thumbnail = imagecreatetruecolor($new_width, $new_height);
    
    // Giữ trong suốt cho PNG
    if ($mime_type === 'image/png') {
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
        $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
        imagefilledrectangle($thumbnail, 0, 0, $new_width, $new_height, $transparent);
    }
    
    // Resize
    imagecopyresampled($thumbnail, $source_image, 0, 0, 0, 0, $new_width, $new_height, $source_width, $source_height);
    
    // Lưu thumbnail
    $success = false;
    switch ($mime_type) {
        case 'image/jpeg':
            $success = imagejpeg($thumbnail, $dest_path, 85);
            break;
        case 'image/png':
            $success = imagepng($thumbnail, $dest_path, 8);
            break;
        case 'image/gif':
            $success = imagegif($thumbnail, $dest_path);
            break;
        case 'image/webp':
            $success = imagewebp($thumbnail, $dest_path, 85);
            break;
    }
    
    // Giải phóng memory
    imagedestroy($source_image);
    imagedestroy($thumbnail);
    
    return $success ? $dest_path : false;
}
?>
