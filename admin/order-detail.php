<?php
require_once '../config/database.php';

requireAdmin();

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    $_SESSION['error'] = 'ID đơn hàng không hợp lệ.';
    redirect('admin/orders.php');
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $oid = (int)$_POST['order_id'];
    $new_status = $_POST['order_status'] ?? '';
    $new_payment_status = $_POST['payment_status'] ?? '';

    $stmt = $pdo->prepare("UPDATE orders SET order_status = ?, payment_status = ?, updated_at = NOW() WHERE id = ?");
    if ($stmt->execute([$new_status, $new_payment_status, $oid])) {
        $_SESSION['success'] = 'Cập nhật trạng thái đơn hàng thành công.';
    } else {
        $_SESSION['error'] = 'Có lỗi xảy ra khi cập nhật.';
    }

    redirect('admin/order-detail.php?id=' . $oid);
}

// Lấy thông tin đơn hàng
$stmt = $pdo->prepare("SELECT o.*, u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    $_SESSION['error'] = 'Không tìm thấy đơn hàng.';
    redirect('orders.php');
}

// Lấy items
$items_stmt = $pdo->prepare("SELECT oi.*, p.slug FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$items_stmt->execute([$order_id]);
$items = $items_stmt->fetchAll();

$page_title = 'Chi tiết đơn hàng #' . htmlspecialchars($order['order_number']);
include 'includes/header.php';
?>

<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-semibold text-gray-900">Chi tiết đơn hàng #<?php echo htmlspecialchars($order['order_number']); ?></h1>
    <a href="orders.php" class="px-4 py-2 bg-white border rounded-md text-sm hover:bg-gray-50">&larr; Quay lại danh sách</a>
</div>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert bg-green-50 text-green-800 px-4 py-2 rounded mb-4"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert bg-red-50 text-red-800 px-4 py-2 rounded mb-4"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold mb-4">Thông tin khách hàng</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <div class="text-sm text-gray-500">Họ tên</div>
                <div class="font-medium"><?php echo htmlspecialchars($order['customer_name']); ?></div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Email</div>
                <div class="font-medium"><?php echo htmlspecialchars($order['customer_email']); ?></div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Số điện thoại</div>
                <div class="font-medium"><?php echo htmlspecialchars($order['customer_phone']); ?></div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Địa chỉ</div>
                <div class="font-medium"><?php echo nl2br(htmlspecialchars($order['customer_address'])); ?></div>
            </div>
        </div>

        <h2 class="text-lg font-semibold mb-4">Sản phẩm</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 mb-4">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Sản phẩm</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Giá</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Số lượng</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tổng</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($items as $it): ?>
                    <tr>
                        <td class="px-4 py-3 text-sm">
                            <?php echo htmlspecialchars($it['product_name']); ?>
                        </td>
                        <td class="px-4 py-3 text-sm"><?php echo formatPrice($it['product_price']); ?></td>
                        <td class="px-4 py-3 text-sm"><?php echo (int)$it['quantity']; ?></td>
                        <td class="px-4 py-3 text-sm"><?php echo formatPrice($it['total_price']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex justify-end space-x-6">
            <div class="text-right">
                <div class="text-sm text-gray-500">Tạm tính</div>
                <div class="font-semibold text-lg"><?php echo formatPrice($order['total_amount'] - $order['shipping_fee'] + $order['discount_amount']); ?></div>
            </div>
            <div class="text-right">
                <div class="text-sm text-gray-500">Phí vận chuyển</div>
                <div class="font-medium"><?php echo formatPrice($order['shipping_fee']); ?></div>
            </div>
            <div class="text-right">
                <div class="text-sm text-gray-500">Giảm giá</div>
                <div class="font-medium"><?php echo formatPrice($order['discount_amount']); ?></div>
            </div>
            <div class="text-right">
                <div class="text-sm text-gray-500">Tổng</div>
                <div class="font-bold text-2xl text-primary"><?php echo formatPrice($order['total_amount']); ?></div>
            </div>
        </div>

        <?php if (!empty($order['notes'])): ?>
            <div class="mt-6 bg-gray-50 p-4 rounded">
                <h3 class="font-medium mb-2">Ghi chú</h3>
                <div class="text-sm text-gray-700"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></div>
            </div>
        <?php endif; ?>
    </div>

    <aside class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold mb-4">Trạng thái đơn hàng</h2>
        <?php
        $status_text = [
            'pending' => 'Chờ xử lý',
            'processing' => 'Đang xử lý',
            'shipped' => 'Đã giao hàng',
            'delivered' => 'Đã nhận hàng',
            'cancelled' => 'Đã hủy'
        ];
        $payment_text = [
            'pending' => 'Chờ thanh toán',
            'paid' => 'Đã thanh toán',
            'failed' => 'Thanh toán thất bại',
            'refunded' => 'Đã hoàn tiền'
        ];
        ?>

        <div class="mb-4">
            <div class="text-sm text-gray-500">Trạng thái hiện tại</div>
            <div class="font-medium mb-2"><?php echo $status_text[$order['order_status']] ?? htmlspecialchars($order['order_status']); ?></div>

            <div class="text-sm text-gray-500">Thanh toán</div>
            <div class="font-medium"><?php echo $payment_text[$order['payment_status']] ?? htmlspecialchars($order['payment_status']); ?></div>
        </div>

        <form method="POST">
            <div class="mb-3">
                <label class="block text-sm text-gray-600 mb-1">Cập nhật trạng thái đơn hàng</label>
                <select name="order_status" class="w-full px-3 py-2 border rounded-md">
                    <option value="pending" <?php echo $order['order_status'] == 'pending' ? 'selected' : ''; ?>>Chờ xử lý</option>
                    <option value="processing" <?php echo $order['order_status'] == 'processing' ? 'selected' : ''; ?>>Đang xử lý</option>
                    <option value="shipped" <?php echo $order['order_status'] == 'shipped' ? 'selected' : ''; ?>>Đã giao hàng</option>
                    <option value="delivered" <?php echo $order['order_status'] == 'delivered' ? 'selected' : ''; ?>>Đã nhận hàng</option>
                    <option value="cancelled" <?php echo $order['order_status'] == 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="block text-sm text-gray-600 mb-1">Trạng thái thanh toán</label>
                <select name="payment_status" class="w-full px-3 py-2 border rounded-md">
                    <option value="pending" <?php echo $order['payment_status'] == 'pending' ? 'selected' : ''; ?>>Chờ thanh toán</option>
                    <option value="paid" <?php echo $order['payment_status'] == 'paid' ? 'selected' : ''; ?>>Đã thanh toán</option>
                    <option value="failed" <?php echo $order['payment_status'] == 'failed' ? 'selected' : ''; ?>>Thanh toán thất bại</option>
                    <option value="refunded" <?php echo $order['payment_status'] == 'refunded' ? 'selected' : ''; ?>>Đã hoàn tiền</option>
                </select>
            </div>

            <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
            <div class="flex space-x-2">
                <button type="submit" name="update_status" class="px-4 py-2 bg-primary text-white rounded-md">Cập nhật</button>
                <a href="orders.php" class="px-4 py-2 bg-gray-100 rounded-md">Hủy</a>
            </div>
        </form>

        <div class="mt-6 text-sm text-gray-500">
            <div>Phương thức thanh toán: <span class="font-medium"><?php echo htmlspecialchars($order['payment_method']); ?></span></div>
            <div>Ngày tạo: <span class="font-medium"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></span></div>
        </div>
    </aside>
</div>

<?php include 'includes/footer.php'; ?>

?>
