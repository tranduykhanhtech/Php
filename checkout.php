<?php
require_once 'config/database.php';
require_once 'includes/csrf.php';

requireLoginChecked();

$page_title = 'Thanh toán';
$page_description = 'Hoàn tất đơn hàng của bạn';

// Xử lý đặt hàng
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF Protection
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Token không hợp lệ. Vui lòng thử lại.';
        redirect('checkout.php');
    }
    
    $customer_name = sanitize($_POST['customer_name']);
    $customer_email = sanitize($_POST['customer_email']);
    $customer_phone = sanitize($_POST['customer_phone']);
    $customer_address = sanitize($_POST['customer_address']);
    $payment_method = $_POST['payment_method'];
    $notes = sanitize($_POST['notes']);
    $voucher_code = isset($_POST['voucher_code']) ? sanitize($_POST['voucher_code']) : '';
    $transfer_code = isset($_POST['transfer_code']) ? sanitize($_POST['transfer_code']) : '';
    
    // Validation
    if (empty($customer_name) || empty($customer_email) || empty($customer_phone) || empty($customer_address)) {
        $_SESSION['error'] = 'Vui lòng nhập đầy đủ thông tin giao hàng';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Lấy sản phẩm trong giỏ hàng với LOCK để tránh race condition
            $cart_items = $pdo->prepare("
                SELECT c.*, p.name, p.price, p.sale_price, p.stock_quantity
                FROM cart c
                LEFT JOIN products p ON c.product_id = p.id
                WHERE c.user_id = ? AND p.is_active = 1
                FOR UPDATE
            ");
            $cart_items->execute([$_SESSION['user_id']]);
            $cart_items = $cart_items->fetchAll();
            
            if (empty($cart_items)) {
                throw new Exception('Giỏ hàng trống');
            }
            
            // Kiểm tra tồn kho với LOCK
            foreach ($cart_items as $item) {
                $check_stock = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ? FOR UPDATE");
                $check_stock->execute([$item['product_id']]);
                $current_stock = $check_stock->fetch()['stock_quantity'];
                
                if ($item['quantity'] > $current_stock) {
                    throw new Exception("Sản phẩm '{$item['name']}' không đủ tồn kho. Còn lại: {$current_stock}");
                }
            }
            
            // Xử lý voucher
            $voucher_discount = 0;
            $voucher_id = null;
            if (!empty($voucher_code)) {
                $voucher_stmt = $pdo->prepare("
                    SELECT * FROM vouchers 
                    WHERE code = ? AND is_active = 1 
                    AND (starts_at IS NULL OR starts_at <= NOW()) 
                    AND (expires_at IS NULL OR expires_at >= NOW())
                    AND (usage_limit IS NULL OR used_count < usage_limit)
                ");
                $voucher_stmt->execute([$voucher_code]);
                $voucher = $voucher_stmt->fetch();
                
                if (!$voucher) {
                    throw new Exception('Mã voucher không hợp lệ hoặc đã hết hạn');
                }
                
                // Kiểm tra đã sử dụng voucher này chưa
                $used_check = $pdo->prepare("SELECT COUNT(*) as count FROM voucher_usage WHERE voucher_id = ? AND user_id = ?");
                $used_check->execute([$voucher['id'], $_SESSION['user_id']]);
                if ($used_check->fetch()['count'] > 0) {
                    throw new Exception('Bạn đã sử dụng voucher này rồi');
                }
                
                $voucher_id = $voucher['id'];
            }
            
            // Tính tổng tiền
            $subtotal = 0;
            foreach ($cart_items as $item) {
                $price = $item['sale_price'] ?: $item['price'];
                $subtotal += $price * $item['quantity'];
            }
            
            // Tính discount từ voucher
            if ($voucher_id) {
                $voucher_stmt = $pdo->prepare("SELECT * FROM vouchers WHERE id = ?");
                $voucher_stmt->execute([$voucher_id]);
                $voucher = $voucher_stmt->fetch();
                
                if ($subtotal >= $voucher['min_order_amount']) {
                    if ($voucher['type'] == 'percentage') {
                        $voucher_discount = ($subtotal * $voucher['value']) / 100;
                        if ($voucher['max_discount'] && $voucher_discount > $voucher['max_discount']) {
                            $voucher_discount = $voucher['max_discount'];
                        }
                    } else {
                        $voucher_discount = $voucher['value'];
                    }
                } else {
                    throw new Exception("Đơn hàng phải tối thiểu " . formatPrice($voucher['min_order_amount']) . " để sử dụng voucher này");
                }
            }
            
            $shipping_fee = $subtotal >= 500000 ? 0 : 30000;
            $total_amount = $subtotal - $voucher_discount + $shipping_fee;
            
            // Tạo mã đơn hàng
            $order_number = 'ORD' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Tạo đơn hàng
            $stmt = $pdo->prepare("
                INSERT INTO orders (order_number, user_id, customer_name, customer_email, customer_phone, 
                                  customer_address, total_amount, shipping_fee, discount_amount, payment_method, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $order_number, $_SESSION['user_id'], $customer_name, $customer_email, 
                $customer_phone, $customer_address, $total_amount, $shipping_fee, 
                $voucher_discount, $payment_method, $notes
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
            
            // Lưu voucher usage nếu có
            if ($voucher_id) {
                $voucher_usage = $pdo->prepare("
                    INSERT INTO voucher_usage (voucher_id, user_id, order_id, discount_amount) 
                    VALUES (?, ?, ?, ?)
                ");
                $voucher_usage->execute([$voucher_id, $_SESSION['user_id'], $order_id, $voucher_discount]);
                
                // Cập nhật số lần sử dụng voucher
                $update_voucher = $pdo->prepare("UPDATE vouchers SET used_count = used_count + 1 WHERE id = ?");
                $update_voucher->execute([$voucher_id]);
            }
            
            // Tạo payment transaction (nếu là bank_transfer)
            if ($payment_method === 'bank_transfer') {
                // Use transfer_code from form if available, otherwise generate one
                if (empty($transfer_code)) {
                    $transfer_code = 'TXN' . date('YmdHis') . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
                }
                
                // Try to insert into payment_transactions if table exists
                try {
                    $txn_stmt = $pdo->prepare("
                        INSERT INTO payment_transactions (order_id, transaction_code, amount, payment_method, transaction_note, transaction_date)
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    $txn_stmt->execute([
                        $order_id,
                        $transfer_code, // Use the code from form
                        $total_amount,
                        $payment_method,
                        $transfer_code // Also save as transaction note for customer reference
                    ]);
                } catch (PDOException $e) {
                    // Table doesn't exist yet, skip transaction logging
                    // Order will still be created successfully
                }
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

    <form method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-8" id="checkoutForm">
        <?php echo csrfField(); ?>
        <input type="hidden" name="transfer_code" id="transfer_code_input" value="">
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

            <!-- Voucher -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">
                    <i class="fas fa-ticket-alt mr-2"></i>Mã giảm giá
                </h2>
                
                <div class="flex gap-4">
                    <div class="flex-1">
                        <input type="text" id="voucher_code" name="voucher_code" 
                               placeholder="Nhập mã giảm giá"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    <button type="button" id="apply_voucher" 
                            class="px-6 py-2 bg-primary text-white rounded-md hover:bg-primary-dark transition-colors">
                        Áp dụng
                    </button>
                </div>
                
                <div id="voucher_message" class="mt-2 text-sm"></div>
            </div>

            <!-- Payment Method -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">
                    <i class="fas fa-credit-card mr-2"></i>Phương thức thanh toán
                </h2>
                
                <div class="space-y-4">
                    <label class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                        <input type="radio" name="payment_method" value="cod" checked
                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300" onchange="toggleBankInfo()">
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
                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300" onchange="toggleBankInfo()">
                        <div class="ml-3">
                            <div class="flex items-center">
                                <i class="fas fa-university text-blue-600 mr-2"></i>
                                <span class="font-medium">Chuyển khoản ngân hàng</span>
                            </div>
                            <p class="text-sm text-gray-500">Chuyển khoản qua ngân hàng</p>
                        </div>
                    </label>
                    
                    <!-- Bank Transfer Info (Hidden by default) -->
                    <div id="bank_transfer_info" class="hidden p-6 bg-blue-50 border-2 border-blue-200 rounded-lg">
                        <h3 class="font-semibold text-blue-900 mb-4 flex items-center">
                            <i class="fas fa-info-circle mr-2"></i>Thông tin chuyển khoản
                        </h3>
                        
                        <div class="mb-4">
                            <img src="/img/payment.jpg" alt="QR Code thanh toán" class="w-full max-w-md mx-auto rounded-lg shadow-md" 
                                 onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'300\'%3E%3Crect fill=\'%23ddd\' width=\'400\' height=\'300\'/%3E%3Ctext fill=\'%23999\' x=\'50%25\' y=\'50%25\' dominant-baseline=\'middle\' text-anchor=\'middle\' font-family=\'sans-serif\' font-size=\'18\'%3EKhông tải được ảnh%3C/text%3E%3C/svg%3E';">
                        </div>
                        
                        <div class="bg-white p-4 rounded-lg space-y-2 text-sm">
                            <p><strong>Ngân hàng:</strong> Techcombank</p>
                            <p><strong>Số tài khoản:</strong> 19039760648013</p>
                            <p><strong>Chủ tài khoản:</strong> Trần Duy Khánh</p>
                            <p><strong>Nội dung:</strong> <span class="text-red-600 font-mono font-bold" id="transfer_note">Đang tạo mã...</span></p>
                        </div>
                        
                        <p class="text-xs text-gray-600 mt-4">
                            <i class="fas fa-exclamation-triangle text-yellow-600 mr-1"></i>
                            Sau khi chuyển khoản, vui lòng lưu lại mã giao dịch để admin xác nhận đơn hàng.
                        </p>
                    </div>
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
                    
                    <div id="voucher_discount_row" class="flex justify-between hidden">
                        <span class="text-gray-600">Giảm giá:</span>
                        <span class="font-medium text-green-600" id="voucher_discount_amount">-0 VNĐ</span>
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
                            <span class="text-primary" id="total_amount"><?php echo formatPrice($total); ?></span>
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
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const applyVoucherBtn = document.getElementById('apply_voucher');
    const voucherCodeInput = document.getElementById('voucher_code');
    const voucherMessage = document.getElementById('voucher_message');
    const voucherDiscountRow = document.getElementById('voucher_discount_row');
    const voucherDiscountAmount = document.getElementById('voucher_discount_amount');
    const totalAmount = document.getElementById('total_amount');
    
    const subtotal = <?php echo $subtotal; ?>;
    const shippingFee = <?php echo $shipping_fee; ?>;
    
    applyVoucherBtn.addEventListener('click', function() {
        const code = voucherCodeInput.value.trim();
        if (!code) {
            voucherMessage.innerHTML = '<span class="text-red-500">Vui lòng nhập mã voucher</span>';
            return;
        }
        
        // Gửi AJAX request để kiểm tra voucher
        fetch('api/check-voucher.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ code: code, subtotal: subtotal })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                voucherMessage.innerHTML = '<span class="text-green-500">✓ ' + data.message + '</span>';
                voucherDiscountRow.classList.remove('hidden');
                voucherDiscountAmount.textContent = '-' + formatPrice(data.discount);
                totalAmount.textContent = formatPrice(data.new_total);
            } else {
                voucherMessage.innerHTML = '<span class="text-red-500">✗ ' + data.message + '</span>';
                voucherDiscountRow.classList.add('hidden');
                totalAmount.textContent = formatPrice(subtotal + shippingFee);
            }
        })
        .catch(error => {
            voucherMessage.innerHTML = '<span class="text-red-500">Lỗi kết nối</span>';
        });
    });
    
    function formatPrice(price) {
        return new Intl.NumberFormat('vi-VN').format(price) + ' VNĐ';
    }
});

// Toggle bank transfer info (global function for inline handlers)
function toggleBankInfo() {
    const bankTransfer = document.querySelector('input[value="bank_transfer"]');
    const bankInfo = document.getElementById('bank_transfer_info');
    
    if (bankTransfer && bankTransfer.checked) {
        bankInfo.classList.remove('hidden');
        generateTransferCode(); // Generate code when showing bank info
    } else {
        bankInfo.classList.add('hidden');
    }
}

// Generate unique transfer code
function generateTransferCode() {
    const customerName = document.getElementById('customer_name').value || '';
    const customerEmail = document.getElementById('customer_email').value || '';
    const totalAmount = document.getElementById('total_amount').textContent.replace(/[^\d]/g, '') || '0';
    
    // Create hash from customer info
    const baseString = customerName + customerEmail + totalAmount + Date.now();
    
    // Simple hash function to create 20-character code
    let hash = 0;
    for (let i = 0; i < baseString.length; i++) {
        const char = baseString.charCodeAt(i);
        hash = ((hash << 5) - hash) + char;
        hash = hash & hash; // Convert to 32bit integer
    }
    
    // Generate 20-character alphanumeric code
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Removed confusing chars
    let code = 'GS'; // Prefix
    
    // Use hash and random to generate code
    const timestamp = Date.now().toString(36).toUpperCase();
    const randomPart = Math.random().toString(36).substring(2, 8).toUpperCase();
    const hashPart = Math.abs(hash).toString(36).substring(0, 6).toUpperCase();
    
    code = (code + timestamp + randomPart + hashPart).substring(0, 20).toUpperCase();
    
    // Pad to exactly 20 characters if needed
    while (code.length < 20) {
        code += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    
    // Display and store the code
    document.getElementById('transfer_note').textContent = code;
    document.getElementById('transfer_code_input').value = code;
    
    return code;
}

// Generate code when page loads (for bank_transfer pre-selected)
document.addEventListener('DOMContentLoaded', function() {
    const bankTransfer = document.querySelector('input[value="bank_transfer"]');
    if (bankTransfer && bankTransfer.checked) {
        generateTransferCode();
    }
    
    // Regenerate when customer info changes
    ['customer_name', 'customer_email'].forEach(id => {
        const elem = document.getElementById(id);
        if (elem) {
            elem.addEventListener('change', function() {
                const bankTransfer = document.querySelector('input[value="bank_transfer"]');
                if (bankTransfer && bankTransfer.checked) {
                    generateTransferCode();
                }
            });
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
