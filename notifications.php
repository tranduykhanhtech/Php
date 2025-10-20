<?php
require_once 'config/database.php';
require_once 'includes/notification_helper.php';

requireLogin();

$page_title = 'Thông báo';

// Xử lý đánh dấu đã đọc
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $notification_id = (int)$_POST['notification_id'];
    if ($notification_id > 0) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$notification_id, $_SESSION['user_id']]);
        $_SESSION['success'] = 'Đã đánh dấu thông báo là đã đọc';
    }
    redirect('notifications.php');
}

// Xử lý đánh dấu tất cả đã đọc
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $_SESSION['success'] = 'Đã đánh dấu tất cả thông báo là đã đọc';
    redirect('notifications.php');
}

// Xử lý xóa thông báo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $notification_id = (int)$_POST['notification_id'];
    if ($notification_id > 0) {
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        $stmt->execute([$notification_id, $_SESSION['user_id']]);
        $_SESSION['success'] = 'Đã xóa thông báo';
    }
    redirect('notifications.php');
}

// Phân trang và filter
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;
$filter_type = $_GET['type'] ?? 'all';
$filter_status = $_GET['status'] ?? 'all';

// Build WHERE clause
$where_conditions = ["user_id = ?"];
$params = [$_SESSION['user_id']];

if ($filter_type !== 'all') {
    $where_conditions[] = "type = ?";
    $params[] = $filter_type;
}

if ($filter_status === 'unread') {
    $where_conditions[] = "is_read = 0";
} elseif ($filter_status === 'read') {
    $where_conditions[] = "is_read = 1";
}

$where_clause = implode(' AND ', $where_conditions);

// Lấy thông báo
$sql = "SELECT id, title, message, type, related_id, is_read, created_at
        FROM notifications 
        WHERE {$where_clause}
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($sql);
$param_index = 1;
foreach ($params as $param) {
    $stmt->bindValue($param_index++, $param);
}
$stmt->bindValue($param_index++, $limit, PDO::PARAM_INT);
$stmt->bindValue($param_index, $offset, PDO::PARAM_INT);
$stmt->execute();
$notifications = $stmt->fetchAll();

// Đếm tổng số thông báo theo filter
$count_sql = "SELECT COUNT(*) FROM notifications WHERE {$where_clause}";
$count_stmt = $pdo->prepare($count_sql);
foreach ($params as $idx => $param) {
    $count_stmt->bindValue($idx + 1, $param);
}
$count_stmt->execute();
$total = $count_stmt->fetchColumn();
$total_pages = ceil($total / $limit);

// Đếm theo loại
$type_counts = $pdo->prepare("
    SELECT type, COUNT(*) as count
    FROM notifications
    WHERE user_id = ?
    GROUP BY type
");
$type_counts->execute([$_SESSION['user_id']]);
$counts_by_type = $type_counts->fetchAll(PDO::FETCH_KEY_PAIR);

// Đếm theo trạng thái
$unread_count = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$unread_count->execute([$_SESSION['user_id']]);
$unread_total = $unread_count->fetchColumn();

$read_count = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 1");
$read_count->execute([$_SESSION['user_id']]);
$read_total = $read_count->fetchColumn();

$all_count = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
$all_count->execute([$_SESSION['user_id']]);
$all_total = $all_count->fetchColumn();

include 'includes/header.php';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Thông báo</h1>
                <p class="mt-2 text-gray-600">Quản lý thông báo của bạn</p>
            </div>
            <a href="notification-settings.php" 
               class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                <i class="fas fa-cog mr-2"></i>Cài đặt
            </a>
        </div>

        <?php if (!empty($_SESSION['success'])): ?>
            <div class="mb-6 p-4 bg-green-50 text-green-800 rounded-lg">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="mb-6 p-4 bg-red-50 text-red-800 rounded-lg">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="mb-6 bg-white rounded-lg shadow-sm p-4">
            <div class="mb-4">
                <h3 class="text-sm font-medium text-gray-700 mb-3">Loại hoạt động</h3>
                <div class="flex flex-wrap gap-2">
                    <a href="?type=all&status=<?php echo $filter_status; ?>" 
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $filter_type === 'all' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                        📊 Tất cả (<?php echo $all_total; ?>)
                    </a>
                    <a href="?type=order&status=<?php echo $filter_status; ?>" 
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $filter_type === 'order' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                        🛍️ Đơn hàng (<?php echo $counts_by_type['order'] ?? 0; ?>)
                    </a>
                    <a href="?type=contact&status=<?php echo $filter_status; ?>" 
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $filter_type === 'contact' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                        💬 Liên hệ (<?php echo $counts_by_type['contact'] ?? 0; ?>)
                    </a>
                    <a href="?type=promotion&status=<?php echo $filter_status; ?>" 
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $filter_type === 'promotion' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                        🎉 Khuyến mãi (<?php echo $counts_by_type['promotion'] ?? 0; ?>)
                    </a>
                    <a href="?type=general&status=<?php echo $filter_status; ?>" 
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $filter_type === 'general' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                        📢 Chung (<?php echo $counts_by_type['general'] ?? 0; ?>)
                    </a>
                </div>
            </div>
            
            <div>
                <h3 class="text-sm font-medium text-gray-700 mb-3">Trạng thái</h3>
                <div class="flex flex-wrap gap-2">
                    <a href="?type=<?php echo $filter_type; ?>&status=all" 
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $filter_status === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                        Tất cả (<?php echo $all_total; ?>)
                    </a>
                    <a href="?type=<?php echo $filter_type; ?>&status=unread" 
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $filter_status === 'unread' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                        <i class="fas fa-envelope"></i> Chưa đọc (<?php echo $unread_total; ?>)
                    </a>
                    <a href="?type=<?php echo $filter_type; ?>&status=read" 
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $filter_status === 'read' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                        <i class="fas fa-envelope-open"></i> Đã đọc (<?php echo $read_total; ?>)
                    </a>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-600">
                    <?php 
                    if ($filter_type !== 'all' || $filter_status !== 'all') {
                        echo "Hiển thị: {$total} thông báo";
                    } else {
                        echo "Tổng cộng: {$total} thông báo";
                    }
                    ?>
                </span>
            </div>
            
            <?php if ($unread_total > 0): ?>
                <form method="POST" class="inline">
                    <button type="submit" name="mark_all_read" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                            onclick="return confirm('Đánh dấu tất cả thông báo là đã đọc?')">
                        <i class="fas fa-check-double mr-2"></i>Đánh dấu tất cả đã đọc
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Notifications List -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <?php if (empty($notifications)): ?>
                <div class="p-8 text-center">
                    <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5-5-5h5v-5a7.5 7.5 0 1 0-15 0v5h5l-5 5-5-5h5V7a7.5 7.5 0 1 1 15 0v10z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Chưa có thông báo nào</h3>
                    <p class="text-gray-600">Bạn sẽ nhận được thông báo khi có cập nhật về đơn hàng hoặc tin tức mới.</p>
                </div>
            <?php else: ?>
                <div class="divide-y divide-gray-200">
                    <?php foreach ($notifications as $notification): ?>
                        <div class="p-6 <?php echo $notification['is_read'] ? 'bg-gray-50' : 'bg-white'; ?> hover:bg-gray-50 transition-colors">
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-3">
                                        <div class="flex-shrink-0">
                                            <?php
                                            $type_icons = [
                                                'order' => '🛍️',
                                                'contact' => '💬',
                                                'general' => '📢',
                                                'promotion' => '🎉'
                                            ];
                                            $icon = $type_icons[$notification['type']] ?? '📢';
                                            ?>
                                            <span class="text-2xl"><?php echo $icon; ?></span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center space-x-2">
                                                <h3 class="text-lg font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($notification['title']); ?>
                                                </h3>
                                                <?php if (!$notification['is_read']): ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        Mới
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="mt-1 text-gray-600">
                                                <?php echo nl2br(htmlspecialchars($notification['message'])); ?>
                                            </p>
                                            <p class="mt-2 text-sm text-gray-500">
                                                <?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-2 ml-4">
                                    <?php if (!$notification['is_read']): ?>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                            <button type="submit" name="mark_read" 
                                                    class="text-sm text-blue-600 hover:text-blue-800 transition-colors">
                                                Đánh dấu đã đọc
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                        <button type="submit" name="delete" 
                                                class="text-sm text-red-600 hover:text-red-800 transition-colors"
                                                onclick="return confirm('Xóa thông báo này?')">
                                            Xóa
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <?php
                    // Build query string để giữ filter
                    $query_params = [
                        'type' => $filter_type,
                        'status' => $filter_status
                    ];
                    $query_string = http_build_query($query_params);
                    ?>
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Hiển thị <?php echo $offset + 1; ?>-<?php echo min($offset + $limit, $total); ?> 
                                trong tổng số <?php echo $total; ?> thông báo
                            </div>
                            
                            <div class="flex items-center space-x-2">
                                <?php if ($page > 1): ?>
                                    <a href="?<?php echo $query_string; ?>&page=<?php echo $page - 1; ?>" 
                                       class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        <i class="fas fa-chevron-left"></i> Trước
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <a href="?<?php echo $query_string; ?>&page=<?php echo $i; ?>" 
                                       class="px-3 py-2 text-sm <?php echo $i === $page ? 'bg-blue-600 text-white' : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'; ?> rounded-md">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="?<?php echo $query_string; ?>&page=<?php echo $page + 1; ?>" 
                                       class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        Sau <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
