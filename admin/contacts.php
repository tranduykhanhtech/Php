<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/notification_helper.php';

requireAdmin();

$page_title = 'Quản lý liên hệ';

// Xử lý cập nhật trạng thái
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $contact_id = (int)$_POST['contact_id'];
    $status = $_POST['status'];
    $admin_note = sanitize($_POST['admin_note']);
    
    try {
        // Lấy thông tin liên hệ trước khi cập nhật
        $contact_stmt = $pdo->prepare("SELECT email, status FROM contacts WHERE id = ?");
        $contact_stmt->execute([$contact_id]);
        $contact = $contact_stmt->fetch();
        
        if ($contact) {
            $old_status = $contact['status'];
            
            // Cập nhật trạng thái liên hệ
            $stmt = $pdo->prepare("UPDATE contacts SET status = ?, admin_note = ? WHERE id = ?");
            $stmt->execute([$status, $admin_note, $contact_id]);
            
            // Tìm user_id từ email và gửi thông báo nếu trạng thái thay đổi
            if ($old_status !== $status) {
                $user_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $user_stmt->execute([$contact['email']]);
                $user = $user_stmt->fetch();
                
                if ($user) {
                    sendContactNotification($user['id'], $contact_id, $status);
                }
            }
            
            $_SESSION['success'] = 'Đã cập nhật trạng thái liên hệ';
        } else {
            $_SESSION['error'] = 'Không tìm thấy liên hệ';
        }
    } catch (Exception $e) {
        error_log('Contact update error: ' . $e->getMessage());
        $_SESSION['error'] = 'Có lỗi xảy ra khi cập nhật liên hệ';
    }
    
    redirect('admin/contacts.php'); // changed: xử lý đường dẫn để load lại chính xác
}

// Xử lý xóa liên hệ
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_contact'])) {
    $contact_id = (int)$_POST['contact_id'];
    
    $stmt = $pdo->prepare("DELETE FROM contacts WHERE id = ?");
    $stmt->execute([$contact_id]);
    
    $_SESSION['success'] = 'Đã xóa liên hệ';
    redirect('admin/contacts.php'); // changed: xử lý đường dẫn để load lại chính xác
}

// Phân trang và lọc
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Đếm tổng số liên hệ theo trạng thái
$where = "";
$params = [];
if ($status_filter) {
    $where = "WHERE status = ?";
    $params[] = $status_filter;
}

$count_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM contacts $where");
$count_stmt->execute($params);
$total_contacts = $count_stmt->fetch()['total'];
$total_pages = ceil($total_contacts / $limit);

// Lấy danh sách liên hệ
$sql = "SELECT * FROM contacts $where ORDER BY 
        CASE 
            WHEN status = 'new' THEN 1
            WHEN status = 'in_progress' THEN 2
            WHEN status = 'resolved' THEN 3
            WHEN status = 'closed' THEN 4
        END,
        created_at DESC
        LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($sql);

// Bind parameters - dùng bindValue cho status filter (nếu có)
$param_index = 1;
if ($status_filter) {
    $stmt->bindValue($param_index++, $status_filter, PDO::PARAM_STR);
}

// Bind LIMIT và OFFSET với PDO::PARAM_INT
$stmt->bindValue($param_index++, $limit, PDO::PARAM_INT);
$stmt->bindValue($param_index, $offset, PDO::PARAM_INT);
$stmt->execute();
$contacts = $stmt->fetchAll();

// Đếm theo trạng thái
$status_counts = $pdo->query("
    SELECT 
        status,
        COUNT(*) as count
    FROM contacts
    GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

include 'includes/header.php';
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Quản lý liên hệ</h2>
    <p class="text-gray-600">Quản lý các yêu cầu liên hệ từ khách hàng</p>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="bg-green-50 border border-green-200 text-green-600 px-4 py-3 rounded-md mb-6">
        <i class="fas fa-check-circle mr-2"></i>
        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>

<!-- Bộ lọc trạng thái -->
<div class="bg-white rounded-lg shadow-md p-4 mb-6">
    <div class="flex flex-wrap gap-2">
        <a href="contacts.php" 
           class="px-4 py-2 rounded-lg <?php echo !$status_filter ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
            Tất cả (<?php echo $total_contacts; ?>)
        </a>
        <a href="contacts.php?status=new" 
           class="px-4 py-2 rounded-lg <?php echo $status_filter == 'new' ? 'bg-yellow-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
            Mới (<?php echo $status_counts['new'] ?? 0; ?>)
        </a>
        <a href="contacts.php?status=in_progress" 
           class="px-4 py-2 rounded-lg <?php echo $status_filter == 'in_progress' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
            Đang xử lý (<?php echo $status_counts['in_progress'] ?? 0; ?>)
        </a>
        <a href="contacts.php?status=resolved" 
           class="px-4 py-2 rounded-lg <?php echo $status_filter == 'resolved' ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
            Đã giải quyết (<?php echo $status_counts['resolved'] ?? 0; ?>)
        </a>
        <a href="contacts.php?status=closed" 
           class="px-4 py-2 rounded-lg <?php echo $status_filter == 'closed' ? 'bg-gray-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
            Đã đóng (<?php echo $status_counts['closed'] ?? 0; ?>)
        </a>
    </div>
</div>

<!-- Danh sách liên hệ -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thông tin</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Chủ đề</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nội dung</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày gửi</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($contacts)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-2"></i>
                            <p>Không có liên hệ nào</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($contacts as $contact): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($contact['name']); ?></div>
                            <div class="text-sm text-gray-500">
                                <i class="fas fa-envelope mr-1"></i><?php echo htmlspecialchars($contact['email']); ?>
                            </div>
                            <?php if ($contact['phone']): ?>
                            <div class="text-sm text-gray-500">
                                <i class="fas fa-phone mr-1"></i><?php echo htmlspecialchars($contact['phone']); ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php
                            $subject_map = [
                                'product_inquiry' => 'Tư vấn sản phẩm',
                                'order_support' => 'Hỗ trợ đơn hàng',
                                'complaint' => 'Khiếu nại',
                                'partnership' => 'Hợp tác',
                                'other' => 'Khác'
                            ];
                            $subject_icons = [
                                'product_inquiry' => 'fa-box',
                                'order_support' => 'fa-shopping-bag',
                                'complaint' => 'fa-exclamation-triangle',
                                'partnership' => 'fa-handshake',
                                'other' => 'fa-question-circle'
                            ];
                            ?>
                            <div class="flex items-center">
                                <i class="fas <?php echo $subject_icons[$contact['subject']] ?? 'fa-comment'; ?> mr-2 text-gray-400"></i>
                                <span class="text-sm"><?php echo $subject_map[$contact['subject']] ?? $contact['subject']; ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900 max-w-xs truncate" title="<?php echo htmlspecialchars($contact['message']); ?>">
                                <?php echo htmlspecialchars(substr($contact['message'], 0, 100)); ?>
                                <?php if (strlen($contact['message']) > 100): ?>...<?php endif; ?>
                            </div>
                            <?php if ($contact['admin_note']): ?>
                            <div class="text-xs text-blue-600 mt-1">
                                <i class="fas fa-sticky-note mr-1"></i>Ghi chú: <?php echo htmlspecialchars(substr($contact['admin_note'], 0, 50)); ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php
                            $status_classes = [
                                'new' => 'bg-yellow-100 text-yellow-800',
                                'in_progress' => 'bg-blue-100 text-blue-800',
                                'resolved' => 'bg-green-100 text-green-800',
                                'closed' => 'bg-gray-100 text-gray-800'
                            ];
                            $status_text = [
                                'new' => 'Mới',
                                'in_progress' => 'Đang xử lý',
                                'resolved' => 'Đã giải quyết',
                                'closed' => 'Đã đóng'
                            ];
                            $status_class = $status_classes[$contact['status']] ?? 'bg-gray-100 text-gray-800';
                            ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status_class; ?>">
                                <?php echo $status_text[$contact['status']]; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo date('d/m/Y H:i', strtotime($contact['created_at'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button onclick="viewContact(<?php echo $contact['id']; ?>)" 
                                    class="text-primary hover:text-green-600 mr-3">
                                <i class="fas fa-eye"></i> Xem
                            </button>
                            <button onclick="deleteContact(<?php echo $contact['id']; ?>)" 
                                    class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash"></i> Xóa
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Phân trang -->
    <?php if ($total_pages > 1): ?>
    <div class="px-6 py-4 border-t border-gray-200">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700">
                Trang <?php echo $page; ?> / <?php echo $total_pages; ?>
            </div>
            <div class="flex space-x-2">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>" 
                       class="px-3 py-2 border border-gray-300 rounded-md text-sm hover:bg-gray-50">
                        <i class="fas fa-chevron-left"></i> Trước
                    </a>
                <?php endif; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>" 
                       class="px-3 py-2 border border-gray-300 rounded-md text-sm hover:bg-gray-50">
                        Sau <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal xem chi tiết -->
<div id="contactModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900">Chi tiết liên hệ</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="contactModalContent" class="px-6 py-4">
                <!-- Nội dung sẽ được load bằng JavaScript -->
            </div>
        </div>
    </div>
</div>

<script>
const contactsData = <?php echo json_encode($contacts); ?>;

function viewContact(contactId) {
    const contact = contactsData.find(c => c.id == contactId);
    if (!contact) return;
    
    const subjectMap = {
        'product_inquiry': 'Tư vấn sản phẩm',
        'order_support': 'Hỗ trợ đơn hàng',
        'complaint': 'Khiếu nại',
        'partnership': 'Hợp tác',
        'other': 'Khác'
    };
    
    const statusMap = {
        'new': 'Mới',
        'in_progress': 'Đang xử lý',
        'resolved': 'Đã giải quyết',
        'closed': 'Đã đóng'
    };
    
    const content = `
        <form method="POST" class="space-y-4">
            <input type="hidden" name="contact_id" value="${contact.id}">
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Họ tên</label>
                    <div class="text-gray-900">${contact.name}</div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <div class="text-gray-900">${contact.email}</div>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Số điện thoại</label>
                    <div class="text-gray-900">${contact.phone || 'N/A'}</div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Chủ đề</label>
                    <div class="text-gray-900">${subjectMap[contact.subject] || contact.subject}</div>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nội dung tin nhắn</label>
                <div class="bg-gray-50 p-3 rounded-md text-gray-900 whitespace-pre-wrap">${contact.message}</div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="new" ${contact.status === 'new' ? 'selected' : ''}>Mới</option>
                    <option value="in_progress" ${contact.status === 'in_progress' ? 'selected' : ''}>Đang xử lý</option>
                    <option value="resolved" ${contact.status === 'resolved' ? 'selected' : ''}>Đã giải quyết</option>
                    <option value="closed" ${contact.status === 'closed' ? 'selected' : ''}>Đã đóng</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú của admin</label>
                <textarea name="admin_note" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md">${contact.admin_note || ''}</textarea>
            </div>
            
            <div class="flex justify-between items-center pt-4 border-t">
                <div class="text-sm text-gray-500">
                    Ngày gửi: ${new Date(contact.created_at).toLocaleString('vi-VN')}
                </div>
                <div class="flex space-x-3">
                    <button type="button" onclick="closeModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">
                        Đóng
                    </button>
                    <button type="submit" name="update_status" 
                            class="px-4 py-2 bg-primary text-white rounded-md hover:bg-green-600">
                        <i class="fas fa-save mr-1"></i>Cập nhật
                    </button>
                </div>
            </div>
        </form>
    `;
    
    document.getElementById('contactModalContent').innerHTML = content;
    document.getElementById('contactModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('contactModal').classList.add('hidden');
}

function deleteContact(contactId) {
    if (!confirm('Bạn có chắc muốn xóa liên hệ này?')) return;
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="delete_contact" value="1">
        <input type="hidden" name="contact_id" value="${contactId}">
    `;
    document.body.appendChild(form);
    form.submit();
}

// Đóng modal khi click bên ngoài
document.getElementById('contactModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

// Đóng modal khi nhấn Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});
</script>

<?php include 'includes/footer.php'; ?>
