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

// Only allow the owner to confirm receipt and only when status is 'shipped'
$stmt = $pdo->prepare('SELECT id, order_status FROM orders WHERE id = ? AND user_id = ?');
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    $_SESSION['error'] = 'Không tìm thấy đơn hàng';
    redirect('orders.php');
}

if ($order['order_status'] !== 'shipped') {
    $_SESSION['error'] = 'Chỉ có thể xác nhận nhận hàng khi đơn ở trạng thái Đã giao hàng';
    redirect('order-detail.php?id=' . $order_id);
}

$update = $pdo->prepare("UPDATE orders SET order_status = 'delivered', updated_at = NOW() WHERE id = ?");
$update->execute([$order_id]);

$_SESSION['success'] = 'Cảm ơn! Đơn hàng đã được đánh dấu là đã nhận.';
redirect('order-detail.php?id=' . $order_id);
?>
