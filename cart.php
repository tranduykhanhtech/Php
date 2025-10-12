<?php
require_once 'config/database.php';

requireLoginChecked();

$page_title = 'Giỏ hàng';
$page_description = 'Xem và quản lý sản phẩm trong giỏ hàng của bạn';

// Xử lý cập nhật giỏ hàng
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantities'] as $cart_id => $quantity) {
            $quantity = (int)$quantity;
            if ($quantity > 0) {
                $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$quantity, $cart_id, $_SESSION['user_id']]);
            } else {
                $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
                $stmt->execute([$cart_id, $_SESSION['user_id']]);
            }
        }
        $_SESSION['success'] = 'Đã cập nhật giỏ hàng';
    } elseif (isset($_POST['remove_item'])) {
        // Hỗ trợ cả 2 cách: nút submit name="remove_item" hoặc hidden input cart_id
        $cart_id = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : (int)$_POST['remove_item'];
        $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $stmt->execute([$cart_id, $_SESSION['user_id']]);
        $_SESSION['success'] = 'Đã xóa sản phẩm khỏi giỏ hàng';
    } elseif (isset($_POST['clear_cart'])) {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $_SESSION['success'] = 'Đã xóa tất cả sản phẩm khỏi giỏ hàng';
    }
    
    redirect('cart.php');
}

// Lấy sản phẩm trong giỏ hàng
$cart_items = $pdo->prepare("
    SELECT c.*, p.name, p.price, p.sale_price, p.images, p.stock_quantity, p.sku
    FROM cart c
    LEFT JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ? AND p.is_active = 1
    ORDER BY c.created_at DESC
");
$cart_items->execute([$_SESSION['user_id']]);
$cart_items = $cart_items->fetchAll();

// Tính tổng tiền
$subtotal = 0;
$total_items = 0;

foreach ($cart_items as $item) {
    $price = $item['sale_price'] ?: $item['price'];
    $subtotal += $price * $item['quantity'];
    $total_items += $item['quantity'];
}

$shipping_fee = $subtotal >= 500000 ? 0 : 30000; // Miễn phí ship từ 500k
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
                    <span class="text-gray-500">Giỏ hàng</span>
                </div>
            </li>
        </ol>
    </nav>

    <h1 class="text-3xl font-bold text-gray-900 mb-8">Giỏ hàng của bạn</h1>

    <?php if (empty($cart_items)): ?>
        <div class="text-center py-12">
            <i class="fas fa-shopping-cart text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Giỏ hàng trống</h3>
            <p class="text-gray-600 mb-6">Bạn chưa có sản phẩm nào trong giỏ hàng</p>
            <a href="products.php" class="bg-primary text-white px-6 py-3 rounded-lg hover:bg-green-600 transition-colors">
                <i class="fas fa-shopping-bag mr-2"></i>Tiếp tục mua sắm
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Cart Items -->
            <div class="lg:col-span-2">
                <form method="POST">
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-semibold text-gray-900">
                                    Sản phẩm (<?php echo $total_items; ?>)
                                </h2>
                                <button type="submit" name="update_cart" 
                                        class="text-primary hover:text-green-600 font-medium">
                                    <i class="fas fa-sync-alt mr-1"></i>Cập nhật
                                </button>
                            </div>
                        </div>
                        
                        <div class="divide-y divide-gray-200">
                            <?php foreach ($cart_items as $item): ?>
                            <div class="p-6">
                                <div class="flex items-center space-x-4">
                                    <!-- Product Image -->
                                    <div class="flex-shrink-0">
                                        <?php 
                                        $images = json_decode($item['images'], true);
                                        $main_image = !empty($images) ? $images[0] : 'https://via.placeholder.com/150x150?text=No+Image';
                                        ?>
                                        <img src="<?php echo $main_image; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                             class="w-20 h-20 object-cover rounded-lg">
                                    </div>
                                    
                                    <!-- Product Info -->
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-lg font-medium text-gray-900 mb-1">
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </h3>
                                        <?php if ($item['sku']): ?>
                                        <p class="text-sm text-gray-500">SKU: <?php echo htmlspecialchars($item['sku']); ?></p>
                                        <?php endif; ?>
                                        <p class="text-sm text-gray-500">Còn lại: <?php echo $item['stock_quantity']; ?> sản phẩm</p>
                                        
                                        <div class="flex items-center space-x-4 mt-2">
                                            <!-- Quantity -->
                                            <div class="flex items-center border border-gray-300 rounded-lg">
                                                <button type="button" onclick="decreaseQuantity(<?php echo $item['id']; ?>)" 
                                                        class="px-3 py-2 text-gray-600 hover:text-gray-800">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                                <input type="number" name="quantities[<?php echo $item['id']; ?>]" 
                                                       value="<?php echo $item['quantity']; ?>" 
                                                       min="1" max="<?php echo $item['stock_quantity']; ?>"
                                                       class="w-16 text-center border-0 focus:outline-none focus:ring-0">
                                                <button type="button" onclick="increaseQuantity(<?php echo $item['id']; ?>)" 
                                                        class="px-3 py-2 text-gray-600 hover:text-gray-800">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                            
                                            <!-- Remove Button -->
                                            <button type="submit" name="remove_item" value="<?php echo $item['id']; ?>" 
                                                    class="text-red-600 hover:text-red-800 text-sm"
                                                    onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này?')">
                                                <i class="fas fa-trash mr-1"></i>Xóa
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Price -->
                                    <div class="text-right">
                                        <?php 
                                        $price = $item['sale_price'] ?: $item['price'];
                                        $item_total = $price * $item['quantity'];
                                        ?>
                                        <div class="text-lg font-semibold text-gray-900">
                                            <?php echo formatPrice($item_total); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?php echo formatPrice($price); ?> × <?php echo $item['quantity']; ?>
                                        </div>
                                        <?php if ($item['sale_price']): ?>
                                        <div class="text-sm text-gray-400 line-through">
                                            <?php echo formatPrice($item['price'] * $item['quantity']); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="px-6 py-4 bg-gray-50">
                            <button type="submit" name="clear_cart" 
                                    class="text-red-600 hover:text-red-800 font-medium"
                                    onclick="return confirm('Bạn có chắc muốn xóa tất cả sản phẩm?')">
                                <i class="fas fa-trash mr-1"></i>Xóa tất cả
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Order Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-24">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Tóm tắt đơn hàng</h2>
                    
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
                        <?php if ($shipping_fee > 0): ?>
                        <div class="text-sm text-gray-500">
                            <i class="fas fa-info-circle mr-1"></i>
                            Mua thêm <?php echo formatPrice(500000 - $subtotal); ?> để được miễn phí vận chuyển
                        </div>
                        <?php endif; ?>
                        <div class="border-t pt-3">
                            <div class="flex justify-between text-lg font-semibold">
                                <span>Tổng cộng:</span>
                                <span class="text-primary"><?php echo formatPrice($total); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        <a href="checkout.php" 
                           class="w-full bg-primary text-white py-3 px-4 rounded-lg font-semibold hover:bg-green-600 transition-colors text-center block">
                            <i class="fas fa-credit-card mr-2"></i>Thanh toán
                        </a>
                        <a href="products.php" 
                           class="w-full border border-gray-300 text-gray-700 py-3 px-4 rounded-lg font-semibold hover:bg-gray-50 transition-colors text-center block">
                            <i class="fas fa-arrow-left mr-2"></i>Tiếp tục mua sắm
                        </a>
                    </div>
                    
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
        </div>
    <?php endif; ?>
</div>

<script>
function increaseQuantity(cartId) {
    const input = document.querySelector(`input[name="quantities[${cartId}]"]`);
    const max = parseInt(input.getAttribute('max'));
    const current = parseInt(input.value);
    if (current < max) {
        input.value = current + 1;
    }
}

function decreaseQuantity(cartId) {
    const input = document.querySelector(`input[name="quantities[${cartId}]"]`);
    const current = parseInt(input.value);
    if (current > 1) {
        input.value = current - 1;
    }
}
</script>

<?php include 'includes/footer.php'; ?>
