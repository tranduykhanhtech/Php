<?php
require_once 'config/database.php';

requireLoginChecked();

$page_title = 'Thanh toán';
$page_description = 'Hoàn tất đơn hàng của bạn';

// Xử lý đặt hàng
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_name = sanitize($_POST['customer_name']);
    $customer_email = sanitize($_POST['customer_email']);
    $customer_phone = sanitize($_POST['customer_phone']);
    $customer_address = sanitize($_POST['customer_address']);
    $payment_method = $_POST['payment_method'];
    $notes = sanitize($_POST['notes']);
    
    // Validation
    if (empty($customer_name) || empty($customer_email) || empty($customer_phone) || empty($customer_address)) {
        $_SESSION['error'] = 'Vui lòng nhập đầy đủ thông tin giao hàng';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Lấy sản phẩm trong giỏ hàng
            $cart_items = $pdo->prepare("
                SELECT c.*, p.name, p.price, p.sale_price, p.stock_quantity
                FROM cart c
                LEFT JOIN products p ON c.product_id = p.id
                WHERE c.user_id = ? AND p.is_active = 1
            ");
            $cart_items->execute([$_SESSION['user_id']]);
            $cart_items = $cart_items->fetchAll();
            
            if (empty($cart_items)) {
                throw new Exception('Giỏ hàng trống');
            }
            
            // Kiểm tra tồn kho
            foreach ($cart_items as $item) {
                if ($item['quantity'] > $item['stock_quantity']) {
                    throw new Exception("Sản phẩm '{$item['name']}' không đủ tồn kho");
                }
            }
            
            // Tính tổng tiền
            $subtotal = 0;
            foreach ($cart_items as $item) {
                $price = $item['sale_price'] ?: $item['price'];
                $subtotal += $price * $item['quantity'];
            }
            
            $shipping_fee = $subtotal >= 500000 ? 0 : 30000;
            $total_amount = $subtotal + $shipping_fee;
            
            // Tạo mã đơn hàng
            $order_number = 'ORD' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Tạo đơn hàng
            $stmt = $pdo->prepare("
                INSERT INTO orders (order_number, user_id, customer_name, customer_email, customer_phone, 
                                  customer_address, total_amount, shipping_fee, payment_method, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $order_number, $_SESSION['user_id'], $customer_name, $customer_email, 
                $customer_phone, $customer_address, $total_amount, $shipping_fee, 
                $payment_method, $notes
            ]);
            
            $order_id = $pdo->lastInsertId();
            
            // Tạo chi tiết đơn hàng và cập nhật tồn kho
            foreach ($cart_items as $item) {
                $price = $item['sale_price'] ?: $item['price'];
                $total_price = $price * $item['quantity'];
                
                // Thêm chi tiết đơn hàng
                $stmt = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, product_name, product_price, quantity, total_price)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $order_id, $item['product_id'], $item['name'], $price, 
                    $item['quantity'], $total_price
                ]);
                
                // Cập nhật tồn kho
                $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }
            
            // Xóa giỏ hàng
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            
            $pdo->commit();
            
            $_SESSION['success'] = 'Đặt hàng thành công! Mã đơn hàng: ' . $order_number;
            redirect('order-success.php?order_id=' . $order_id);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = $e->getMessage();
        }
    }
}

// Lấy thông tin người dùng
$user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$_SESSION['user_id']]);
$user = $user_stmt->fetch();

// Lấy sản phẩm trong giỏ hàng
$cart_items = $pdo->prepare("
    SELECT c.*, p.name, p.price, p.sale_price, p.images, p.sku
    FROM cart c
    LEFT JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ? AND p.is_active = 1
    ORDER BY c.created_at DESC
");
$cart_items->execute([$_SESSION['user_id']]);
$cart_items = $cart_items->fetchAll();

if (empty($cart_items)) {
    $_SESSION['error'] = 'Giỏ hàng trống';
    redirect('cart.php');
}

// Tính tổng tiền
$subtotal = 0;
$total_items = 0;

foreach ($cart_items as $item) {
    $price = $item['sale_price'] ?: $item['price'];
    $subtotal += $price * $item['quantity'];
    $total_items += $item['quantity'];
}

$shipping_fee = $subtotal >= 500000 ? 0 : 30000;
$total = $subtotal + $shipping_fee;

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
                    <a href="cart.php" class="text-gray-700 hover:text-primary">Giỏ hàng</a>
                </div>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-500">Thanh toán</span>
                </div>
            </li>
        </ol>
    </nav>

    <h1 class="text-3xl font-bold text-gray-900 mb-8">Thanh toán</h1>

    <form method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Checkout Form -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Customer Information -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">
                    <i class="fas fa-user mr-2"></i>Thông tin giao hàng
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="customer_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Họ và tên *
                        </label>
                        <input type="text" id="customer_name" name="customer_name" required
                               value="<?php echo htmlspecialchars($user['full_name']); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="customer_email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email *
                        </label>
                        <input type="email" id="customer_email" name="customer_email" required
                               value="<?php echo htmlspecialchars($user['email']); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="customer_phone" class="block text-sm font-medium text-gray-700 mb-2">
                            Số điện thoại *
                        </label>
                        <input type="tel" id="customer_phone" name="customer_phone" required
                               value="<?php echo htmlspecialchars($user['phone']); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="customer_address" class="block text-sm font-medium text-gray-700 mb-2">
                            Địa chỉ giao hàng *
                        </label>
                        <textarea id="customer_address" name="customer_address" rows="3" required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"><?php echo htmlspecialchars($user['address']); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Payment Method -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">
                    <i class="fas fa-credit-card mr-2"></i>Phương thức thanh toán
                </h2>
                
                <div class="space-y-4">
                    <label class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                        <input type="radio" name="payment_method" value="cod" checked
                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300">
                        <div class="ml-3">
                            <div class="flex items-center">
                                <i class="fas fa-money-bill-wave text-green-600 mr-2"></i>
                                <span class="font-medium">Thanh toán khi nhận hàng (COD)</span>
                            </div>
                            <p class="text-sm text-gray-500">Thanh toán bằng tiền mặt khi nhận hàng</p>
                        </div>
                    </label>
                    
                    <label class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                        <input type="radio" name="payment_method" value="bank_transfer"
                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300">
                        <div class="ml-3">
                            <div class="flex items-center">
                                <i class="fas fa-university text-blue-600 mr-2"></i>
                                <span class="font-medium">Chuyển khoản ngân hàng</span>
                            </div>
                            <p class="text-sm text-gray-500">Chuyển khoản qua ngân hàng</p>
                        </div>
                    </label>
                    
                    <label class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                        <input type="radio" name="payment_method" value="momo"
                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300">
                        <div class="ml-3">
                            <div class="flex items-center">
                                <i class="fas fa-mobile-alt text-pink-600 mr-2"></i>
                                <span class="font-medium">Ví MoMo</span>
                            </div>
                            <p class="text-sm text-gray-500">Thanh toán qua ví điện tử MoMo</p>
                        </div>
                    </label>
                    
                    <label class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                        <input type="radio" name="payment_method" value="vnpay"
                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300">
                        <div class="ml-3">
                            <div class="flex items-center">
                                <i class="fas fa-credit-card text-blue-600 mr-2"></i>
                                <span class="font-medium">VNPay</span>
                            </div>
                            <p class="text-sm text-gray-500">Thanh toán qua VNPay</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Order Notes -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">
                    <i class="fas fa-sticky-note mr-2"></i>Ghi chú đơn hàng
                </h2>
                
                <textarea name="notes" rows="4" placeholder="Ghi chú thêm cho đơn hàng (không bắt buộc)..."
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"></textarea>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-md p-6 sticky top-24">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Tóm tắt đơn hàng</h2>
                
                <!-- Order Items -->
                <div class="space-y-4 mb-6">
                    <?php foreach ($cart_items as $item): ?>
                    <div class="flex items-center space-x-3">
                        <?php 
                        $images = json_decode($item['images'], true);
                        $main_image = !empty($images) ? $images[0] : 'https://via.placeholder.com/60x60?text=No+Image';
                        ?>
                        <img src="<?php echo $main_image; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" 
                             class="w-12 h-12 object-cover rounded">
                        <div class="flex-1 min-w-0">
                            <h4 class="text-sm font-medium text-gray-900 line-clamp-2">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </h4>
                            <p class="text-sm text-gray-500">Số lượng: <?php echo $item['quantity']; ?></p>
                        </div>
                        <div class="text-sm font-medium text-gray-900">
                            <?php 
                            $price = $item['sale_price'] ?: $item['price'];
                            echo formatPrice($price * $item['quantity']); 
                            ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Order Total -->
                <div class="space-y-3 mb-6">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Tạm tính:</span>
                        <span class="font-medium"><?php echo formatPrice($subtotal); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Phí vận chuyển:</span>
                        <span class="font-medium">
                            <?php if ($shipping_fee == 0): ?>
                                <span class="text-green-600">Miễn phí</span>
                            <?php else: ?>
                                <?php echo formatPrice($shipping_fee); ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="border-t pt-3">
                        <div class="flex justify-between text-lg font-semibold">
                            <span>Tổng cộng:</span>
                            <span class="text-primary"><?php echo formatPrice($total); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Place Order Button -->
                <button type="submit" 
                        class="w-full bg-primary text-white py-3 px-4 rounded-lg font-semibold hover:bg-green-600 transition-colors">
                    <i class="fas fa-check mr-2"></i>Đặt hàng
                </button>
                
                <!-- Security Info -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="flex items-center text-sm text-gray-500 mb-2">
                        <i class="fas fa-shield-alt text-primary mr-2"></i>
                        Thanh toán an toàn
                    </div>
                    <div class="flex items-center text-sm text-gray-500 mb-2">
                        <i class="fas fa-undo text-primary mr-2"></i>
                        Đổi trả trong 30 ngày
                    </div>
                    <div class="flex items-center text-sm text-gray-500">
                        <i class="fas fa-shipping-fast text-primary mr-2"></i>
                        Giao hàng nhanh chóng
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<?php include 'includes/footer.php'; ?>
