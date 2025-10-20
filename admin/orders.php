<?php
require_once '../config/database.php';
require_once '../includes/notification_helper.php';

requireAdmin();

$page_title = 'Quản lý đơn hàng';

// Handle status update (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    $new_status = $_POST['order_status'] ?? '';
    $new_payment_status = $_POST['payment_status'] ?? '';

    if ($order_id > 0) {
        try {
            // Lấy thông tin đơn hàng trước khi cập nhật
            $order_stmt = $pdo->prepare("SELECT order_number, user_id, order_status FROM orders WHERE id = ?");
            $order_stmt->execute([$order_id]);
            $order = $order_stmt->fetch();
            
            if ($order) {
                $old_status = $order['order_status'];
                
                // Cập nhật trạng thái đơn hàng
                $stmt = $pdo->prepare("UPDATE orders SET order_status = ?, payment_status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$new_status, $new_payment_status, $order_id]);
                
                // Gửi thông báo nếu trạng thái thay đổi và có user_id
                if ($old_status !== $new_status && $order['user_id']) {
                    sendOrderNotification($order['user_id'], $order_id, $order['order_number'], $new_status);
                }
                
                $_SESSION['success'] = 'Cập nhật trạng thái đơn hàng thành công';
            } else {
                $_SESSION['error'] = 'Không tìm thấy đơn hàng';
            }
        } catch (Exception $e) {
            error_log('Order update error: ' . $e->getMessage());
            $_SESSION['error'] = 'Có lỗi xảy ra khi cập nhật trạng thái';
        }
    } else {
        $_SESSION['error'] = 'ID đơn hàng không hợp lệ';
    }

    redirect('admin/orders.php'); //changed: sửa lại đường dẫn để trang orders có thể reload 1 cách chính xác
}

// Pagination & filters
$page = max(1, (int)($_GET['page'] ?? 1));
$status_filter = $_GET['status'] ?? '';
$limit = 15;
$offset = ($page - 1) * $limit;

$where = '1=1';
$params = [];
if ($status_filter !== '') {
    $where = 'o.order_status = ?';
    $params[] = $status_filter;
}

// Fetch orders with correlated subquery for item_count
try {
    $sql = "SELECT o.id, o.order_number, o.total_amount, o.order_status, o.payment_status, o.created_at, o.user_id, o.customer_name, o.customer_email,
            (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
            FROM orders o
            WHERE $where
            ORDER BY o.created_at DESC
            LIMIT ? OFFSET ?";

    $stmt = $pdo->prepare($sql);

    // Bind parameters explicitly. When ATTR_EMULATE_PREPARES is false some drivers
    // require LIMIT/OFFSET to be bound as integers using PDO::PARAM_INT.
    $bindIndex = 1;
    if ($status_filter !== '') {
        $stmt->bindValue($bindIndex++, $status_filter, PDO::PARAM_STR);
    }
    $stmt->bindValue($bindIndex++, (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue($bindIndex++, (int)$offset, PDO::PARAM_INT);

    $stmt->execute();
    $orders = $stmt->fetchAll();

    $countSql = "SELECT COUNT(*) as total FROM orders o WHERE " . ($status_filter !== '' ? 'o.order_status = ?' : '1=1');
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($status_filter !== '' ? [$status_filter] : []);
    $total_orders = (int)$countStmt->fetchColumn();
    $total_pages = max(1, (int)ceil($total_orders / $limit));
} catch (Exception $e) {
    error_log('Orders fetch error: ' . $e->getMessage());
    // Keep user-facing silence but ensure variables are set so page renders
    $orders = [];
    $total_pages = 1;
}

include 'includes/header.php';
?>

<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-semibold text-gray-900">Quản lý đơn hàng</h1>
    <div class="flex items-center space-x-4">
        <select onchange="location.href='?status='+this.value" class="px-3 py-2 border rounded">
            <option value="">Tất cả trạng thái</option>
            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Chờ xử lý</option>
            <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Đang xử lý</option>
            <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Đã giao hàng</option>
            <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Đã nhận hàng</option>
            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
        </select>
        <a href="orders.php" class="px-3 py-2 bg-white border rounded">Làm mới</a>
    </div>
</div>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="mb-4 p-3 bg-green-50 text-green-800 rounded"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
    <div class="mb-4 p-3 bg-red-50 text-red-800 rounded"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>

<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mã</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Khách hàng</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tổng</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thanh toán</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hành động</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($orders)): ?>
                    <tr><td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">Không có đơn hàng</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?php echo htmlspecialchars($order['order_number']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="font-medium"><?php echo htmlspecialchars($order['customer_name'] ?? 'Khách vãng lai'); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($order['customer_email'] ?? ''); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo formatPrice($order['total_amount']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php
                                $status_map = [
                                    'pending' => 'Chờ xử lý',
                                    'processing' => 'Đang xử lý',
                                    'shipped' => 'Đã giao hàng',
                                    'delivered' => 'Đã nhận hàng',
                                    'cancelled' => 'Đã hủy'
                                ];
                                echo $status_map[$order['order_status']] ?? htmlspecialchars($order['order_status']);
                                ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php
                                $pay_map = ['pending' => 'Chờ thanh toán', 'paid' => 'Đã thanh toán', 'failed' => 'Thất bại', 'refunded' => 'Hoàn tiền'];
                                echo $pay_map[$order['payment_status']] ?? htmlspecialchars($order['payment_status']);
                                ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-3">
                                    <a href="order-detail.php?id=<?php echo (int)$order['id']; ?>" class="text-primary hover:text-green-600"><i class="fas fa-eye"></i></a>
                                    <button onclick="openEdit(<?php echo (int)$order['id']; ?>, '<?php echo htmlspecialchars($order['order_status']); ?>', '<?php echo htmlspecialchars($order['payment_status']); ?>')" class="text-blue-600 hover:text-blue-800"><i class="fas fa-edit"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($total_pages > 1): ?>
    <nav class="flex justify-center mt-6">
        <ul class="flex space-x-2">
            <?php if ($page > 1): ?><li><a href="?page=<?php echo $page-1; ?>&status=<?php echo urlencode($status_filter); ?>" class="px-3 py-2 bg-white border rounded">&laquo;</a></li><?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?><li><a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>" class="px-3 py-2 <?php echo $i == $page ? 'bg-primary text-white' : 'bg-white'; ?> border rounded"><?php echo $i; ?></a></li><?php endfor; ?>
            <?php if ($page < $total_pages): ?><li><a href="?page=<?php echo $page+1; ?>&status=<?php echo urlencode($status_filter); ?>" class="px-3 py-2 bg-white border rounded">&raquo;</a></li><?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>

<!-- Edit modal -->
<div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <form method="POST" class="">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold">Cập nhật trạng thái đơn hàng</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm">Trạng thái đơn hàng</label>
                        <select name="order_status" class="w-full px-3 py-2 border rounded">
                            <option value="pending">Chờ xử lý</option>
                            <option value="processing">Đang xử lý</option>
                            <option value="shipped">Đã giao hàng</option>
                            <option value="delivered">Đã nhận hàng</option>
                            <option value="cancelled">Đã hủy</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm">Trạng thái thanh toán</label>
                        <select name="payment_status" class="w-full px-3 py-2 border rounded">
                            <option value="pending">Chờ thanh toán</option>
                            <option value="paid">Đã thanh toán</option>
                            <option value="failed">Thanh toán thất bại</option>
                            <option value="refunded">Đã hoàn tiền</option>
                        </select>
                    </div>
                </div>
                <div class="px-6 py-4 border-t flex justify-end space-x-2">
                    <button type="button" onclick="closeEdit()" class="px-4 py-2 bg-gray-100 rounded">Hủy</button>
                    <button type="submit" name="update_status" class="px-4 py-2 bg-primary text-white rounded">Cập nhật</button>
                </div>
                <input type="hidden" name="order_id" id="editOrderId">
            </form>
        </div>
    </div>
</div>

<script>
function openEdit(id, ostatus, pstatus) {
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('editOrderId').value = id;
    document.querySelector('select[name=\'order_status\']').value = ostatus;
    document.querySelector('select[name=\'payment_status\']').value = pstatus;
}
function closeEdit() {
    document.getElementById('editModal').classList.add('hidden');
}
</script>

<?php include 'includes/footer.php'; ?>
