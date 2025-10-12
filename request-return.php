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

// Only allow the owner to request return and only when status is 'delivered'
$stmt = $pdo->prepare('SELECT id, order_status FROM orders WHERE id = ? AND user_id = ?');
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    $_SESSION['error'] = 'Không tìm thấy đơn hàng';
    redirect('orders.php');
}

if ($order['order_status'] !== 'delivered') {
    $_SESSION['error'] = 'Chỉ có thể yêu cầu hoàn hàng khi đơn đã nhận';
    redirect('order-detail.php?id=' . $order_id);
}

// For now, mark as cancelled and refunded. In a real app this should create a return request
$update = $pdo->prepare("UPDATE orders SET order_status = 'cancelled', payment_status = 'refunded', updated_at = NOW() WHERE id = ?");
$update->execute([$order_id]);

$_SESSION['success'] = 'Yêu cầu hoàn hàng đã được gửi. Tình trạng đơn sẽ được cập nhật.';
redirect('order-detail.php?id=' . $order_id);
?>
