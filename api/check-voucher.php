<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$code = sanitize($input['code'] ?? '');
$subtotal = floatval($input['subtotal'] ?? 0);

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập mã voucher']);
    exit;
}

try {
    // Kiểm tra voucher
    $voucher_stmt = $pdo->prepare("
        SELECT * FROM vouchers 
        WHERE code = ? AND is_active = 1 
        AND (starts_at IS NULL OR starts_at <= NOW()) 
        AND (expires_at IS NULL OR expires_at >= NOW())
        AND (usage_limit IS NULL OR used_count < usage_limit)
    ");
    $voucher_stmt->execute([$code]);
    $voucher = $voucher_stmt->fetch();
    
    if (!$voucher) {
        echo json_encode(['success' => false, 'message' => 'Mã voucher không hợp lệ hoặc đã hết hạn']);
        exit;
    }
    
    // Kiểm tra đã sử dụng voucher này chưa
    if (isLoggedIn()) {
        $used_check = $pdo->prepare("SELECT COUNT(*) as count FROM voucher_usage WHERE voucher_id = ? AND user_id = ?");
        $used_check->execute([$voucher['id'], $_SESSION['user_id']]);
        if ($used_check->fetch()['count'] > 0) {
            echo json_encode(['success' => false, 'message' => 'Bạn đã sử dụng voucher này rồi']);
            exit;
        }
    }
    
    // Kiểm tra điều kiện đơn hàng tối thiểu
    if ($subtotal < $voucher['min_order_amount']) {
        echo json_encode([
            'success' => false, 
            'message' => 'Đơn hàng phải tối thiểu ' . formatPrice($voucher['min_order_amount']) . ' để sử dụng voucher này'
        ]);
        exit;
    }
    
    // Tính discount
    $discount = 0;
    if ($voucher['type'] == 'percentage') {
        $discount = ($subtotal * $voucher['value']) / 100;
        if ($voucher['max_discount'] && $discount > $voucher['max_discount']) {
            $discount = $voucher['max_discount'];
        }
    } else {
        $discount = $voucher['value'];
    }
    
    $shipping_fee = $subtotal >= 500000 ? 0 : 30000;
    $new_total = $subtotal - $discount + $shipping_fee;
    
    echo json_encode([
        'success' => true,
        'message' => 'Áp dụng voucher thành công',
        'discount' => $discount,
        'new_total' => $new_total,
        'voucher_name' => $voucher['name']
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
?>
