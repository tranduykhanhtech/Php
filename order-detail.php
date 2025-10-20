<?php
require_once 'config/database.php';

requireLoginChecked();

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($order_id <= 0) {
    redirect('orders.php');
}

// Lấy đơn hàng của chính user
$order_stmt = $pdo->prepare(
    "SELECT o.* FROM orders o WHERE o.id = ? AND o.user_id = ?"
);
$order_stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $order_stmt->fetch();

if (!$order) {
    $_SESSION['error'] = 'Không tìm thấy đơn hàng';
    redirect('orders.php');
}

// Lấy items
$items_stmt = $pdo->prepare(
    "SELECT oi.*, p.images FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ? ORDER BY oi.id"
);
$items_stmt->execute([$order_id]);
$items = $items_stmt->fetchAll();

$page_title = 'Chi tiết đơn hàng #' . htmlspecialchars($order['order_number']);
include 'includes/header.php';
?>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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
                    <a href="orders.php" class="text-gray-700 hover:text-primary">Đơn hàng của tôi</a>
                </div>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-500">#<?php echo htmlspecialchars($order['order_number']); ?></span>
                </div>
            </li>
        </ol>
    </nav>

    <div class="flex flex-col md:flex-row md:items-start md:justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Đơn hàng #<?php echo htmlspecialchars($order['order_number']); ?></h1>
        <div class="mt-3 md:mt-0">
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
            $class = $status_classes[$order['order_status']] ?? 'bg-gray-100 text-gray-800';
            $text = $status_text[$order['order_status']] ?? $order['order_status'];
            ?>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $class; ?>">
                <?php echo $text; ?>
            </span>
        </div>
    </div>

    <!-- Order Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Ngày đặt hàng</h3>
            <div class="text-gray-900 font-semibold"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></div>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Phương thức thanh toán</h3>
            <div class="text-gray-900 font-semibold">
                <?php
                $payment_text = [
                    'cod' => 'Thanh toán khi nhận hàng',
                    'bank_transfer' => 'Chuyển khoản ngân hàng'
                ];
                echo $payment_text[$order['payment_method']] ?? $order['payment_method'];
                ?>
            </div>
            <div class="mt-1 text-sm text-gray-600">
                Trạng thái: <span class="font-medium"><?php echo $order['payment_status']; ?></span>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Tổng tiền</h3>
            <div class="text-primary font-bold text-xl"><?php echo formatPrice($order['total_amount']); ?></div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Items -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Sản phẩm</h2>
                </div>
                <div class="divide-y divide-gray-200">
                    <?php foreach ($items as $item): ?>
                    <div class="p-6 flex items-center space-x-4">
                        <?php
                        $images = $item['images'] ? json_decode($item['images'], true) : [];
                        $img = !empty($images) ? $images[0] : 'https://via.placeholder.com/80x80?text=No+Image';
                        ?>
                        <img src="<?php echo $img; ?>" class="w-16 h-16 rounded object-cover" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($item['product_name']); ?></div>
                            <div class="text-sm text-gray-500">Số lượng: <?php echo $item['quantity']; ?></div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-gray-500"><?php echo formatPrice($item['product_price']); ?> × <?php echo $item['quantity']; ?></div>
                            <div class="font-semibold text-gray-900"><?php echo formatPrice($item['total_price']); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="px-6 py-4 bg-gray-50">
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Tạm tính</span>
                            <span class="font-medium"><?php echo formatPrice($order['total_amount'] - $order['shipping_fee'] + $order['discount_amount']); ?></span>
                        </div>
                        <?php if ($order['discount_amount'] > 0): ?>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Giảm giá</span>
                            <span class="font-medium text-green-600">-<?php echo formatPrice($order['discount_amount']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Phí vận chuyển</span>
                            <span class="font-medium"><?php echo $order['shipping_fee'] == 0 ? 'Miễn phí' : formatPrice($order['shipping_fee']); ?></span>
                        </div>
                        <div class="flex justify-between text-lg font-semibold border-t pt-2">
                            <span>Tổng cộng</span>
                            <span class="text-primary"><?php echo formatPrice($order['total_amount']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Shipping info -->
        <div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Thông tin giao hàng</h2>
                <div class="space-y-2 text-gray-700">
                    <div><span class="text-sm text-gray-500">Tên:</span> <span class="font-medium"><?php echo htmlspecialchars($order['customer_name']); ?></span></div>
                    <div><span class="text-sm text-gray-500">Email:</span> <span class="font-medium"><?php echo htmlspecialchars($order['customer_email']); ?></span></div>
                    <div><span class="text-sm text-gray-500">Điện thoại:</span> <span class="font-medium"><?php echo htmlspecialchars($order['customer_phone']); ?></span></div>
                    <div><span class="text-sm text-gray-500">Địa chỉ:</span> <span class="font-medium"><?php echo nl2br(htmlspecialchars($order['customer_address'])); ?></span></div>
                    <?php if (!empty($order['notes'])): ?>
                    <div class="pt-2 border-t"><span class="text-sm text-gray-500">Ghi chú:</span> <span class="font-medium"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></span></div>
                    <?php endif; ?>
                </div>
                <?php if ($order['order_status'] === 'pending'): ?>
                <form method="POST" action="cancel-order.php" class="mt-6" onsubmit="return confirm('Bạn có chắc muốn hủy đơn hàng này?')">
                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold px-4 py-2 rounded-md">
                        <i class="fas fa-times mr-2"></i>Hủy đơn hàng
                    </button>
                </form>
                <?php endif; ?>

                <?php if ($order['order_status'] === 'shipped'): ?>
                <form method="POST" action="confirm-receive.php" class="mt-4" onsubmit="return confirm('Xác nhận bạn đã nhận được hàng?')">
                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold px-4 py-2 rounded-md">
                        <i class="fas fa-check mr-2"></i>Xác nhận nhận hàng
                    </button>
                </form>
                <?php endif; ?>

                <?php if ($order['order_status'] === 'delivered'): ?>
                <form method="POST" action="request-return.php" class="mt-4" onsubmit="return confirm('Bạn muốn gửi yêu cầu hoàn hàng?')">
                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                    <button type="submit" class="w-full bg-yellow-600 hover:bg-yellow-700 text-white font-semibold px-4 py-2 rounded-md">
                        <i class="fas fa-undo mr-2"></i>Yêu cầu hoàn hàng
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="mt-8 flex justify-end space-x-3">
        <a href="orders.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i>Trở lại danh sách
        </a>
        <a href="products.php" class="px-4 py-2 bg-primary text-white rounded-md hover:bg-green-600">
            <i class="fas fa-shopping-bag mr-2"></i>Mua thêm sản phẩm
        </a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>


