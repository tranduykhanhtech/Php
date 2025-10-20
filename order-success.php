<?php
require_once 'config/database.php';

requireLoginChecked();

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if (!$order_id) {
    redirect('index.php');
}

// Lấy thông tin đơn hàng
$order_stmt = $pdo->prepare("
    SELECT o.*, u.full_name as user_name 
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.id = ? AND o.user_id = ?
");
$order_stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $order_stmt->fetch();

if (!$order) {
    redirect('index.php');
}

// Lấy chi tiết đơn hàng
$order_items = $pdo->prepare("
    SELECT oi.*, p.images
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
    ORDER BY oi.id
");
$order_items->execute([$order_id]);
$order_items = $order_items->fetchAll();

$page_title = 'Đặt hàng thành công';
$page_description = 'Cảm ơn bạn đã đặt hàng';

include 'includes/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Success Message -->
    <div class="text-center mb-8">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
            <i class="fas fa-check text-2xl text-green-600"></i>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Đặt hàng thành công!</h1>
        <p class="text-lg text-gray-600">Cảm ơn bạn đã tin tưởng và đặt hàng tại <?php echo SITE_NAME; ?></p>
    </div>

    <!-- Order Information -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Thông tin đơn hàng</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="font-medium text-gray-900 mb-2">Mã đơn hàng</h3>
                <p class="text-lg font-semibold text-primary"><?php echo htmlspecialchars($order['order_number']); ?></p>
            </div>
            
            <div>
                <h3 class="font-medium text-gray-900 mb-2">Ngày đặt hàng</h3>
                <p class="text-gray-600"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
            </div>
            
            <div>
                <h3 class="font-medium text-gray-900 mb-2">Trạng thái</h3>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                    <i class="fas fa-clock mr-1"></i>
                    <?php
                    $status_text = [
                        'pending' => 'Chờ xử lý',
                        'processing' => 'Đang xử lý',
                        'shipped' => 'Đã giao hàng',
                        'delivered' => 'Đã nhận hàng',
                        'cancelled' => 'Đã hủy'
                    ];
                    echo $status_text[$order['order_status']] ?? $order['order_status'];
                    ?>
                </span>
            </div>
            
            <div>
                <h3 class="font-medium text-gray-900 mb-2">Phương thức thanh toán</h3>
                <p class="text-gray-600">
                    <?php
                    $payment_text = [
                        'cod' => 'Thanh toán khi nhận hàng',
                        'bank_transfer' => 'Chuyển khoản ngân hàng'
                    ];
                    echo $payment_text[$order['payment_method']] ?? $order['payment_method'];
                    ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Order Items -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Sản phẩm đã đặt</h2>
        
        <div class="space-y-4">
            <?php foreach ($order_items as $item): ?>
            <div class="flex items-center space-x-4 p-4 border border-gray-200 rounded-lg">
                <?php 
                $images = json_decode($item['images'], true);
                $main_image = !empty($images) ? $images[0] : 'https://via.placeholder.com/80x80?text=No+Image';
                ?>
                <img src="<?php echo $main_image; ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                     class="w-16 h-16 object-cover rounded">
                
                <div class="flex-1 min-w-0">
                    <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($item['product_name']); ?></h3>
                    <p class="text-sm text-gray-500">Số lượng: <?php echo $item['quantity']; ?></p>
                </div>
                
                <div class="text-right">
                    <p class="font-medium text-gray-900"><?php echo formatPrice($item['total_price']); ?></p>
                    <p class="text-sm text-gray-500"><?php echo formatPrice($item['product_price']); ?> × <?php echo $item['quantity']; ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Order Total -->
        <div class="mt-6 pt-6 border-t border-gray-200">
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-600">Tạm tính:</span>
                    <span class="font-medium"><?php echo formatPrice($order['total_amount'] - $order['shipping_fee']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Phí vận chuyển:</span>
                    <span class="font-medium">
                        <?php if ($order['shipping_fee'] == 0): ?>
                            <span class="text-green-600">Miễn phí</span>
                        <?php else: ?>
                            <?php echo formatPrice($order['shipping_fee']); ?>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="flex justify-between text-lg font-semibold border-t pt-2">
                    <span>Tổng cộng:</span>
                    <span class="text-primary"><?php echo formatPrice($order['total_amount']); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Delivery Information -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Thông tin giao hàng</h2>
        
        <div class="space-y-2">
            <div class="flex">
                <span class="font-medium text-gray-900 w-24">Tên:</span>
                <span class="text-gray-600"><?php echo htmlspecialchars($order['customer_name']); ?></span>
            </div>
            <div class="flex">
                <span class="font-medium text-gray-900 w-24">Email:</span>
                <span class="text-gray-600"><?php echo htmlspecialchars($order['customer_email']); ?></span>
            </div>
            <div class="flex">
                <span class="font-medium text-gray-900 w-24">Điện thoại:</span>
                <span class="text-gray-600"><?php echo htmlspecialchars($order['customer_phone']); ?></span>
            </div>
            <div class="flex">
                <span class="font-medium text-gray-900 w-24">Địa chỉ:</span>
                <span class="text-gray-600"><?php echo nl2br(htmlspecialchars($order['customer_address'])); ?></span>
            </div>
            <?php if ($order['notes']): ?>
            <div class="flex">
                <span class="font-medium text-gray-900 w-24">Ghi chú:</span>
                <span class="text-gray-600"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Next Steps -->
    <div class="bg-blue-50 rounded-lg p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Bước tiếp theo</h2>
        
        <div class="space-y-3">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-envelope text-blue-600 mt-1"></i>
                </div>
                <div class="ml-3">
                    <p class="text-gray-700">
                        <strong>Xác nhận đơn hàng:</strong> Chúng tôi sẽ gửi email xác nhận đến 
                        <span class="font-medium"><?php echo htmlspecialchars($order['customer_email']); ?></span>
                    </p>
                </div>
            </div>
            
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-phone text-blue-600 mt-1"></i>
                </div>
                <div class="ml-3">
                    <p class="text-gray-700">
                        <strong>Liên hệ:</strong> Chúng tôi sẽ gọi điện xác nhận đơn hàng trong vòng 30 phút
                    </p>
                </div>
            </div>
            
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-truck text-blue-600 mt-1"></i>
                </div>
                <div class="ml-3">
                    <p class="text-gray-700">
                        <strong>Giao hàng:</strong> Đơn hàng sẽ được giao trong 1-3 ngày làm việc
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <a href="orders.php" class="bg-primary text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-600 transition-colors text-center">
            <i class="fas fa-list mr-2"></i>Xem đơn hàng của tôi
        </a>
        <a href="products.php" class="border border-gray-300 text-gray-700 px-6 py-3 rounded-lg font-semibold hover:bg-gray-50 transition-colors text-center">
            <i class="fas fa-shopping-bag mr-2"></i>Tiếp tục mua sắm
        </a>
        <a href="index.php" class="border border-gray-300 text-gray-700 px-6 py-3 rounded-lg font-semibold hover:bg-gray-50 transition-colors text-center">
            <i class="fas fa-home mr-2"></i>Về trang chủ
        </a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
