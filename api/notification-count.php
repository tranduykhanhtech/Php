<?php
require_once '../config/database.php';
require_once '../includes/notification_helper.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['count' => 0]);
    exit;
}

$user_id = $_SESSION['user_id'];
$count = getUnreadNotificationCount($user_id);

echo json_encode(['count' => $count]);
?>
