<?php
require_once 'config/database.php';

requireLoginChecked();

$page_title = 'Đơn hàng của tôi';
$page_description = 'Xem lịch sử đơn hàng của bạn';

// Lấy danh sách đơn hàng
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$orders_stmt = $pdo->prepare("
    SELECT o.*, COUNT(oi.id) as item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT $limit OFFSET $offset
");
$orders_stmt->execute([$_SESSION['user_id']]);
$orders = $orders_stmt->fetchAll();

// Đếm tổng số đơn hàng
$count_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE user_id = ?");
$count_stmt->execute([$_SESSION['user_id']]);
$total_orders = $count_stmt->fetch()['total'];
$total_pages = ceil($total_orders / $limit);

include 'includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Breadcrumb -->
    <nav class="flex mb-8" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="index.php" class="text-gray-700 hover:text-primary">
                    <i class="fas fa-home mr-1"></i>Trang chủ
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-500">Đơn hàng của tôi</span>
                </div>
            </li>
        </ol>
    </nav>

    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Đơn hàng của tôi</h1>
        <a href="products.php" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors">
            <i class="fas fa-shopping-bag mr-2"></i>Tiếp tục mua sắm
        </a>
    </div>

    <?php if (empty($orders)): ?>
        <div class="text-center py-12">
            <i class="fas fa-shopping-bag text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Chưa có đơn hàng nào</h3>
            <p class="text-gray-600 mb-6">Bạn chưa đặt đơn hàng nào</p>
            <a href="products.php" class="bg-primary text-white px-6 py-3 rounded-lg hover:bg-green-600 transition-colors">
                <i class="fas fa-shopping-bag mr-2"></i>Bắt đầu mua sắm
            </a>
        </div>
    <?php else: ?>
        <div class="space-y-6">
            <?php foreach ($orders as $order): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <!-- Order Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center space-x-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">
                                    Đơn hàng #<?php echo htmlspecialchars($order['order_number']); ?>
                                </h3>
                                <p class="text-sm text-gray-500">
                                    <i class="fas fa-calendar mr-1"></i>
                                    <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="mt-4 sm:mt-0 flex flex-col sm:items-end space-y-2">
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-gray-500">Trạng thái:</span>
                                <?php
                                $status_classes = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'processing' => 'bg-blue-100 text-blue-800',
                                    'shipped' => 'bg-purple-100 text-purple-800',
                                    'delivered' => 'bg-green-100 text-green-800',
                                    'cancelled' => 'bg-red-100 text-red-800'
                                ];
                                $status_text = [
                                    'pending' => 'Chờ xử lý',
                                    'processing' => 'Đang xử lý',
                                    'shipped' => 'Đã giao hàng',
                                    'delivered' => 'Đã nhận hàng',
                                    'cancelled' => 'Đã hủy'
                                ];
                                $status_class = $status_classes[$order['order_status']] ?? 'bg-gray-100 text-gray-800';
                                $status_text_display = $status_text[$order['order_status']] ?? $order['order_status'];
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status_class; ?>">
                                    <?php echo $status_text_display; ?>
                                </span>
                            </div>
                            
                            <div class="text-right">
                                <div class="text-lg font-semibold text-gray-900">
                                    <?php echo formatPrice($order['total_amount']); ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?php echo $order['item_count']; ?> sản phẩm
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Details -->
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <h4 class="font-medium text-gray-900 mb-1">Thông tin giao hàng</h4>
                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($order['customer_name']); ?></p>
                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($order['customer_phone']); ?></p>
                        </div>
                        
                        <div>
                            <h4 class="font-medium text-gray-900 mb-1">Địa chỉ giao hàng</h4>
                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($order['customer_address']); ?></p>
                        </div>
                        
                        <div>
                            <h4 class="font-medium text-gray-900 mb-1">Phương thức thanh toán</h4>
                            <p class="text-sm text-gray-600">
                                <?php
                                $payment_text = [
                                    'cod' => 'Thanh toán khi nhận hàng',
                                    'bank_transfer' => 'Chuyển khoản ngân hàng',
                                    'momo' => 'Ví MoMo',
                                    'vnpay' => 'VNPay'
                                ];
                                echo $payment_text[$order['payment_method']] ?? $order['payment_method'];
                                ?>
                            </p>
                        </div>
                    </div>

                    <!-- Order Items Preview -->
                    <?php
                    $order_items = $pdo->prepare("
                        SELECT oi.*, p.images
                        FROM order_items oi
                        LEFT JOIN products p ON oi.product_id = p.id
                        WHERE oi.order_id = ?
                        ORDER BY oi.id
                        LIMIT 3
                    ");
                    $order_items->execute([$order['id']]);
                    $order_items = $order_items->fetchAll();
                    ?>
                    
                    <div class="border-t pt-4">
                        <h4 class="font-medium text-gray-900 mb-3">Sản phẩm đã đặt</h4>
                        <div class="space-y-2">
                            <?php foreach ($order_items as $item): ?>
                            <div class="flex items-center space-x-3">
                                <?php 
                                $images = json_decode($item['images'], true);
                                $main_image = !empty($images) ? $images[0] : 'https://via.placeholder.com/40x40?text=No+Image';
                                ?>
                                <img src="<?php echo $main_image; ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                     class="w-10 h-10 object-cover rounded">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 line-clamp-1">
                                        <?php echo htmlspecialchars($item['product_name']); ?>
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        <?php echo formatPrice($item['product_price']); ?> × <?php echo $item['quantity']; ?>
                                    </p>
                                </div>
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo formatPrice($item['total_price']); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php if ($order['item_count'] > 3): ?>
                            <div class="text-sm text-gray-500 text-center pt-2">
                                Và <?php echo $order['item_count'] - 3; ?> sản phẩm khác...
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Order Actions -->
                    <div class="flex justify-end space-x-3 mt-4 pt-4 border-t">
                        <a href="order-detail.php?id=<?php echo $order['id']; ?>" 
                           class="text-primary hover:text-green-600 font-medium text-sm">
                            <i class="fas fa-eye mr-1"></i>Xem chi tiết
                        </a>
                        
                        <?php if ($order['order_status'] == 'pending'): ?>
                        <form method="POST" action="/cancel-order.php" onsubmit="return confirm('Bạn có chắc muốn hủy đơn hàng này?')" class="inline">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <button type="submit" class="text-red-600 hover:text-red-800 font-medium text-sm">
                                <i class="fas fa-times mr-1"></i>Hủy đơn hàng
                            </button>
                        </form>
                        <?php endif; ?>
                        
                        <?php if ($order['order_status'] == 'delivered'): ?>
                        <button onclick="reorder(<?php echo $order['id']; ?>)" 
                                class="text-primary hover:text-green-600 font-medium text-sm">
                            <i class="fas fa-redo mr-1"></i>Đặt lại
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav class="flex justify-center mt-8">
            <ul class="flex space-x-2">
                <?php if ($page > 1): ?>
                    <li>
                        <a href="?page=<?php echo $page - 1; ?>" 
                           class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <li>
                        <a href="?page=<?php echo $i; ?>" 
                           class="px-3 py-2 text-sm font-medium <?php echo $i == $page ? 'text-white bg-primary border-primary' : 'text-gray-500 bg-white border-gray-300 hover:bg-gray-50'; ?> border rounded-md">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <li>
                        <a href="?page=<?php echo $page + 1; ?>" 
                           class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function cancelOrder(orderId) {
    if (confirm('Bạn có chắc muốn hủy đơn hàng này?')) {
        // Implement cancel order functionality
        alert('Tính năng hủy đơn hàng sẽ được triển khai');
    }
}

function reorder(orderId) {
    if (confirm('Bạn có muốn đặt lại đơn hàng này?')) {
        // Implement reorder functionality
        alert('Tính năng đặt lại sẽ được triển khai');
    }
}
</script>

<style>
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<?php include 'includes/footer.php'; ?>
