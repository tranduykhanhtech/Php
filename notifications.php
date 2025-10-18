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

// Phân trang
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Lấy thông báo
$stmt = $pdo->prepare("
    SELECT id, title, message, type, related_id, is_read, created_at
    FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->execute([$_SESSION['user_id'], $limit, $offset]);
$notifications = $stmt->fetchAll();

// Đếm tổng số thông báo
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
$count_stmt->execute([$_SESSION['user_id']]);
$total = $count_stmt->fetchColumn();
$total_pages = ceil($total / $limit);

include 'includes/header.php';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Thông báo</h1>
            <p class="mt-2 text-gray-600">Quản lý thông báo của bạn</p>
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

        <!-- Actions -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-600">
                    Tổng cộng: <?php echo $total; ?> thông báo
                </span>
            </div>
            
            <?php if ($total > 0): ?>
                <form method="POST" class="inline">
                    <button type="submit" name="mark_all_read" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                            onclick="return confirm('Đánh dấu tất cả thông báo là đã đọc?')">
                        Đánh dấu tất cả đã đọc
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
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Hiển thị <?php echo $offset + 1; ?>-<?php echo min($offset + $limit, $total); ?> 
                                trong tổng số <?php echo $total; ?> thông báo
                            </div>
                            
                            <div class="flex items-center space-x-2">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?>" 
                                       class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        Trước
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <a href="?page=<?php echo $i; ?>" 
                                       class="px-3 py-2 text-sm <?php echo $i === $page ? 'bg-blue-600 text-white' : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'; ?> rounded-md">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>" 
                                       class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        Sau
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
