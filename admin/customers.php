<?php
require_once '../config/database.php';

requireAdmin();

$page_title = 'Quản lý khách hàng';

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $full_name = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $address = $_POST['address'] ?? '';
    $role = in_array($_POST['role'] ?? 'customer', ['customer','admin']) ? $_POST['role'] : 'customer';

    if ($id > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, role = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$full_name, $email, $phone, $address, $role, $id]);
            $_SESSION['success'] = 'Cập nhật thông tin người dùng thành công.';
        } catch (Exception $e) {
            error_log('User update error: ' . $e->getMessage());
            $_SESSION['error'] = 'Có lỗi xảy ra khi cập nhật.';
        }
    }

    redirect('admin/customers.php'); // changed: Sửa lại đường dẫn để load lại chính xác với yêu cầu
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $id = isset($_POST['delete_user']) ? (int)$_POST['delete_user'] : 0;

    // Prevent deleting yourself or admin accounts
    if ($id === ($_SESSION['user_id'] ?? 0)) {
        $_SESSION['error'] = 'Bạn không thể xóa chính mình.';
        redirect('customers.php');
    }

    $check = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $check->execute([$id]);
    $u = $check->fetch();
    if (!$u) {
        $_SESSION['error'] = 'Người dùng không tồn tại.';
        redirect('customers.php');
    }
    if ($u['role'] === 'admin') {
        $_SESSION['error'] = 'Không thể xóa tài khoản admin.';
        redirect('customers.php');
    }

    try {
        $del = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $del->execute([$id]);
        $_SESSION['success'] = 'Xóa người dùng thành công.';
    } catch (Exception $e) {
        error_log('User delete error: ' . $e->getMessage());
        $_SESSION['error'] = 'Có lỗi khi xóa người dùng.';
    }

    redirect('admin/customers.php'); // changed: Sửa lại đường dẫn để load lại chính xác với yêu cầu
}

// Handle suspend/unlock/lock actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['suspend_user'])) {
    $id = (int)($_POST['suspend_user'] ?? 0);
    $days = max(0, (int)($_POST['suspend_days'] ?? 0));
    if ($id > 0) {
        $until = $days > 0 ? date('Y-m-d H:i:s', strtotime("+$days days")) : date('Y-m-d H:i:s', strtotime('+100 years'));
        $stmt = $pdo->prepare("UPDATE users SET suspension_until = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$until, $id]);
        // Force signout: xóa session của user
    $delToken = $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
    $delToken->execute([$id]);
        $_SESSION['success'] = 'Đã đình chỉ tài khoản và đăng xuất người dùng.';
    }
    redirect('admin/customers.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lock_user'])) {
    $id = (int)($_POST['lock_user'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE users SET is_locked = 1, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
        // Force signout: xóa session của user
    $delToken = $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
    $delToken->execute([$id]);
        $_SESSION['success'] = 'Tài khoản đã bị khóa vĩnh viễn và đăng xuất người dùng.';
    }
    redirect('admin/customers.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unlock_user'])) {
    $id = (int)($_POST['unlock_user'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE users SET is_locked = 0, suspension_until = NULL, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = 'Đã mở khóa tài khoản.';
    }
    redirect('admin/customers.php');// changed: Sửa lại đường dẫn để load lại chính xác với yêu cầu
}

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

try {
    $usersStmt = $pdo->prepare("SELECT id, username, email, full_name, phone, address, role, created_at, suspension_until, is_locked FROM users WHERE role = 'customer' ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    $usersStmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $usersStmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $usersStmt->execute();
    $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

    $countStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'");
    $total = (int)$countStmt->fetchColumn();
    $total_pages = max(1, (int)ceil($total / $limit));
} catch (Exception $e) {
    error_log('Users fetch error: ' . $e->getMessage());
    $users = [];
    $total_pages = 1;
}

include 'includes/header.php';
?>

<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-semibold text-gray-900">Quản lý khách hàng</h1>
    <a href="customers.php" class="px-3 py-2 bg-white border rounded">Làm mới</a>
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SĐT</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vai trò</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày tạo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($users)): ?>
                    <tr><td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">Không có người dùng</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                                <tr>
                                    <td class="px-6 py-4 text-sm"><?php echo $u['id']; ?></td>
                                    <td class="px-6 py-4 text-sm font-medium"><?php echo htmlspecialchars($u['full_name'] ?: $u['username']); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($u['phone']); ?></td>
                                    <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($u['role']); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-500"><?php echo date('d/m/Y', strtotime($u['created_at'])); ?></td>
                                    <td class="px-6 py-4 text-sm">
                                        <?php
                                        if ($u['is_locked']) {
                                            echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Khóa vĩnh viễn</span>';
                                        } elseif (!empty($u['suspension_until']) && strtotime($u['suspension_until']) > time()) {
                                            echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Đình chỉ đến ' . date('d/m/Y', strtotime($u['suspension_until'])) . '</span>';
                                        } else {
                                            echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Hoạt động</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                <div class="flex items-center space-x-2">
                                    <button onclick="openEdit(event, <?php echo (int)$u['id']; ?>)" class="text-blue-600 hover:text-blue-800"><i class="fas fa-edit"></i></button>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Bạn có chắc muốn xóa người dùng này?');">
                                        <input type="hidden" name="delete_user" value="<?php echo (int)$u['id']; ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button>
                                    </form>
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
            <?php if ($page > 1): ?><li><a href="?page=<?php echo $page-1; ?>" class="px-3 py-2 bg-white border rounded">&laquo;</a></li><?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li><a href="?page=<?php echo $i; ?>" class="px-3 py-2 <?php echo $i==$page ? 'bg-primary text-white' : 'bg-white'; ?> border rounded"><?php echo $i; ?></a></li>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?><li><a href="?page=<?php echo $page+1; ?>" class="px-3 py-2 bg-white border rounded">&raquo;</a></li><?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>

<!-- Edit User Modal -->
<div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <form method="POST">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold">Chỉnh sửa người dùng</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm">Họ và tên</label>
                        <input type="text" name="full_name" id="u_full_name" class="w-full px-3 py-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-sm">Email</label>
                        <input type="email" name="email" id="u_email" class="w-full px-3 py-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-sm">Số điện thoại</label>
                        <input type="text" name="phone" id="u_phone" class="w-full px-3 py-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-sm">Địa chỉ</label>
                        <textarea name="address" id="u_address" class="w-full px-3 py-2 border rounded"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm">Vai trò</label>
                        <select name="role" id="u_role" class="w-full px-3 py-2 border rounded">
                            <option value="customer">customer</option>
                            <option value="admin">admin</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm">Đình chỉ (số ngày, 0 = hủy đình chỉ)</label>
                        <input type="number" name="suspend_days" id="u_suspend_days" class="w-full px-3 py-2 border rounded" min="0" value="0">
                    </div>
                    <div class="flex space-x-2">
                        <button type="submit" name="suspend_user" value="" id="btn_suspend" class="px-4 py-2 bg-yellow-600 text-white rounded">Đình chỉ</button>
                        <button type="submit" name="lock_user" value="" id="btn_lock" class="px-4 py-2 bg-red-600 text-white rounded">Khóa vĩnh viễn</button>
                        <button type="submit" name="unlock_user" value="" id="btn_unlock" class="px-4 py-2 bg-green-600 text-white rounded">Mở khóa</button>
                    </div>
                </div>
                <div class="px-6 py-4 border-t flex justify-end space-x-2">
                    <button type="button" onclick="closeEdit()" class="px-4 py-2 bg-gray-100 rounded">Hủy</button>
                    <button type="submit" name="update_user" class="px-4 py-2 bg-primary text-white rounded">Lưu</button>
                </div>
                <input type="hidden" name="user_id" id="u_id">
            </form>
        </div>
    </div>
</div>

<script>
function openEdit(e, id) {
    const tr = e.target.closest('tr');
    const cells = tr.querySelectorAll('td');
    document.getElementById('u_id').value = id;
    document.getElementById('u_full_name').value = cells[1].innerText.trim();
    document.getElementById('u_email').value = cells[2].innerText.trim();
    document.getElementById('u_phone').value = cells[3].innerText.trim();
    document.getElementById('u_address').value = '';
    document.getElementById('u_role').value = cells[4].innerText.trim();
    // Gán id user cho các nút thao tác
    document.getElementById('btn_suspend').value = id;
    document.getElementById('btn_lock').value = id;
    document.getElementById('btn_unlock').value = id;
    try {
        const statusCell = cells[5].innerText.trim();
        const m = statusCell.match(/\d{2}\/\d{2}\/\d{4}/);
        if (m) {
            const until = new Date(m[0].split('/').reverse().join('-'));
            const days = Math.ceil((until - new Date()) / (1000*60*60*24));
            document.getElementById('u_suspend_days').value = days > 0 ? days : 0;
        } else {
            document.getElementById('u_suspend_days').value = 0;
        }
    } catch (er) {
        document.getElementById('u_suspend_days').value = 0;
    }
    document.getElementById('editModal').classList.remove('hidden');
}
function closeEdit() {
    document.getElementById('editModal').classList.add('hidden');
}
</script>

<?php include 'includes/footer.php'; ?>
