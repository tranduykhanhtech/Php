<?php
require_once 'config/database.php';

requireLoginChecked();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('orders.php');
}

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
if (!$order_id) {
    $_SESSION['error'] = 'Đơn hàng không hợp lệ';
    redirect('orders.php');
}

// Chỉ được hủy đơn hàng của chính mình và trạng thái pending
$stmt = $pdo->prepare("SELECT id, order_status FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    $_SESSION['error'] = 'Không tìm thấy đơn hàng';
    redirect('orders.php');
}

if ($order['order_status'] !== 'pending') {
    $_SESSION['error'] = 'Chỉ có thể hủy đơn hàng ở trạng thái chờ xử lý';
    redirect('orders.php');
}

$update = $pdo->prepare("UPDATE orders SET order_status = 'cancelled', updated_at = NOW() WHERE id = ?");
$update->execute([$order_id]);

$_SESSION['success'] = 'Đã hủy đơn hàng thành công';
redirect('orders.php');
?>


