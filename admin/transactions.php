<?php
require_once '../config/database.php';

requireAdmin();

$page_title = 'Quản lý giao dịch thanh toán';

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Search filter
$search = $_GET['search'] ?? '';

// Build query
$where = [];
$params = [];

if ($search) {
    $where[] = "pt.transaction_code LIKE ?";
    $search_term = "%{$search}%";
    $params[] = $search_term;
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Fetch transactions
try {
    $query = "
        SELECT pt.*, o.order_number, o.customer_name, o.customer_email, o.payment_status, o.order_status
        FROM payment_transactions pt
        LEFT JOIN orders o ON pt.order_id = o.id
        $where_clause
        ORDER BY pt.transaction_date DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $pdo->prepare($query);
    
    // Bind WHERE parameters first
    $param_index = 1;
    foreach ($params as $value) {
        $stmt->bindValue($param_index++, $value);
    }
    
    // Then bind LIMIT and OFFSET
    $stmt->bindValue($param_index++, $limit, PDO::PARAM_INT);
    $stmt->bindValue($param_index, $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $transactions = $stmt->fetchAll();
    
    // Count total
    $count_query = "SELECT COUNT(*) FROM payment_transactions pt LEFT JOIN orders o ON pt.order_id = o.id $where_clause";
    $count_stmt = $pdo->prepare($count_query);
    
    $param_index = 1;
    foreach ($params as $value) {
        $count_stmt->bindValue($param_index++, $value);
    }
    
    $count_stmt->execute();
    $total_count = $count_stmt->fetchColumn();
    $total_pages = ceil($total_count / $limit);
    
} catch (Exception $e) {
    error_log('Transactions fetch error: ' . $e->getMessage());
    $transactions = [];
    $total_pages = 1;
}

include 'includes/header.php';
?>

<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-semibold text-gray-900">
        <i class="fas fa-receipt mr-2"></i>Quản lý giao dịch thanh toán
    </h1>
    <div class="flex items-center space-x-3">
        <span class="text-sm text-gray-600">
            <i class="fas fa-database mr-1"></i>
            Tổng: <strong><?php echo number_format($total_count); ?></strong> giao dịch
        </span>
    </div>
</div>

<!-- Search Filter -->
<div class="bg-white rounded-lg shadow-md p-4 mb-6">
    <form method="GET" class="flex items-center gap-3">
        <div class="flex-1">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Tìm kiếm theo mã giao dịch..." 
                   class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
        </div>
        <button type="submit" class="bg-primary text-white px-6 py-2 rounded-md hover:bg-green-600 transition">
            <i class="fas fa-search mr-2"></i>Tìm kiếm
        </button>
        <?php if ($search): ?>
        <a href="transactions.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-300 transition">
            <i class="fas fa-times mr-2"></i>Xóa
        </a>
        <?php endif; ?>
    </form>
</div>

<!-- Info Notice -->
<div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
    <div class="flex items-start">
        <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
        <div>
            <h3 class="text-blue-800 font-semibold mb-1">Lưu ý quan trọng</h3>
            <p class="text-sm text-blue-700">
                Dữ liệu giao dịch chỉ được xem, <strong>không thể sửa hoặc xóa</strong> để đảm bảo tính minh bạch và an toàn. 
                Mọi giao dịch đều được ghi nhận vĩnh viễn vào hệ thống.
            </p>
        </div>
    </div>
</div>

<!-- Transactions Table -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mã GD</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Đơn hàng</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Khách hàng</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số tiền</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PT thanh toán</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nội dung</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-3"></i>
                            <p>Chưa có giao dịch nào</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transactions as $txn): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-mono text-sm bg-gray-100 px-2 py-1 rounded">
                                    <?php echo htmlspecialchars($txn['transaction_code']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="order-detail.php?id=<?php echo $txn['order_id']; ?>" 
                                   class="text-blue-600 hover:text-blue-800 font-medium">
                                    <?php echo htmlspecialchars($txn['order_number']); ?>
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm">
                                    <div class="font-medium text-gray-900"><?php echo htmlspecialchars($txn['customer_name']); ?></div>
                                    <div class="text-gray-500"><?php echo htmlspecialchars($txn['customer_email']); ?></div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-green-600 font-semibold">
                                    <?php echo number_format($txn['amount'], 0, ',', '.'); ?> VNĐ
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($txn['payment_method'] === 'bank_transfer'): ?>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        <i class="fas fa-university mr-1"></i> Chuyển khoản
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                        <i class="fas fa-money-bill-wave mr-1"></i> COD
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600">
                                    <?php echo htmlspecialchars($txn['transaction_note'] ?? 'N/A'); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('d/m/Y H:i:s', strtotime($txn['transaction_date'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $status_classes = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'paid' => 'bg-green-100 text-green-800',
                                    'failed' => 'bg-red-100 text-red-800',
                                    'refunded' => 'bg-gray-100 text-gray-800'
                                ];
                                $status_text = [
                                    'pending' => 'Chờ xử lý',
                                    'paid' => 'Đã thanh toán',
                                    'failed' => 'Thất bại',
                                    'refunded' => 'Đã hoàn tiền'
                                ];
                                $class = $status_classes[$txn['payment_status']] ?? 'bg-gray-100 text-gray-800';
                                $text = $status_text[$txn['payment_status']] ?? $txn['payment_status'];
                                ?>
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $class; ?>">
                                    <?php echo $text; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<nav class="flex justify-center mt-6">
    <ul class="flex space-x-2">
        <?php if ($page > 1): ?>
            <li>
                <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                   class="px-3 py-2 bg-white border rounded hover:bg-gray-50">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
        <?php endif; ?>
        
        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
            <li>
                <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                   class="px-3 py-2 <?php echo $i == $page ? 'bg-primary text-white' : 'bg-white hover:bg-gray-50'; ?> border rounded">
                    <?php echo $i; ?>
                </a>
            </li>
        <?php endfor; ?>
        
        <?php if ($page < $total_pages): ?>
            <li>
                <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                   class="px-3 py-2 bg-white border rounded hover:bg-gray-50">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
