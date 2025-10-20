<?php
require_once '../config/database.php';
requireAdmin();

$page_title = 'Quản lý Voucher';
$page_description = 'Quản lý mã giảm giá';

// Xử lý thêm voucher
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add') {
        $code = sanitize($_POST['code']);
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $type = $_POST['type'];
        $value = floatval($_POST['value']);
        $min_order_amount = floatval($_POST['min_order_amount']);
        $max_discount = !empty($_POST['max_discount']) ? floatval($_POST['max_discount']) : null;
        $usage_limit = !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : null;
        $starts_at = !empty($_POST['starts_at']) ? $_POST['starts_at'] : null;
        $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO vouchers (code, name, description, type, value, min_order_amount, max_discount, usage_limit, starts_at, expires_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $code, $name, $description, $type, $value, $min_order_amount, 
                $max_discount, $usage_limit, $starts_at, $expires_at
            ]);
            $_SESSION['success'] = 'Thêm voucher thành công';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Lỗi: ' . $e->getMessage();
        }
    } 
    elseif ($_POST['action'] == 'toggle') {
        $id = intval($_POST['id']);
        $is_active = intval($_POST['is_active']);
        
        $stmt = $pdo->prepare("UPDATE vouchers SET is_active = ? WHERE id = ?");
        $stmt->execute([$is_active, $id]);
        $_SESSION['success'] = 'Cập nhật trạng thái voucher thành công';
    }
    elseif ($_POST['action'] == 'delete'){ // changed: thêm chức năng xóa voucher
        $id = intval($_POST['id']);
        try {
            $stmt = $pdo->prepare("DELETE FROM vouchers WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = 'Xóa voucher thành công';
        } 
        catch (Exception $e) {
            $_SESSION['error'] = 'Lỗi: ' . $e->getMessage();
        }
    }
    elseif ($_POST['action'] == 'edit') {
        $id = intval($_POST['id']);
        $code = sanitize($_POST['code']);
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $type = $_POST['type'];
        $value = floatval($_POST['value']);
        $min_order_amount = floatval($_POST['min_order_amount']);
        $max_discount = !empty($_POST['max_discount']) ? floatval($_POST['max_discount']) : null;
        $usage_limit = !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : null;
        $starts_at = !empty($_POST['starts_at']) ? $_POST['starts_at'] : null;
        $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;

        try {
            $stmt = $pdo->prepare("
                UPDATE vouchers
                SET code = ?, name = ?, description = ?, type = ?, value = ?, min_order_amount = ?, 
                    max_discount = ?, usage_limit = ?, starts_at = ?, expires_at = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $code, $name, $description, $type, $value, $min_order_amount,
                $max_discount, $usage_limit, $starts_at, $expires_at, $id
            ]);
            $_SESSION['success'] = 'Cập nhật voucher thành công';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Lỗi: ' . $e->getMessage();
        }
        redirect('admin/vouchers.php');
    }

    redirect('admin/vouchers.php');
}

// Lấy danh sách voucher
$vouchers = $pdo->query("
    SELECT v.*, 
           COUNT(vu.id) as total_used,
           (v.used_count - COUNT(vu.id)) as remaining_usage
    FROM vouchers v
    LEFT JOIN voucher_usage vu ON v.id = vu.voucher_id
    GROUP BY v.id
    ORDER BY v.created_at DESC
")->fetchAll();

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Quản lý Voucher</h1>
            <p class="text-gray-600 mt-2">Quản lý mã giảm giá và khuyến mãi</p>
        </div>
        <button onclick="openAddModal()" 
                class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-primary-dark transition-colors">
            <i class="fas fa-plus mr-2"></i>Thêm Voucher
        </button>
    </div>

    <!-- Voucher List -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mã</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loại</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Giá trị</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Đã dùng</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hạn sử dụng</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($vouchers as $voucher): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-mono text-sm font-medium text-gray-900"><?php echo htmlspecialchars($voucher['code']); ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($voucher['name']); ?></div>
                            <?php if ($voucher['description']): ?>
                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($voucher['description']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                <?php echo $voucher['type'] == 'percentage' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'; ?>">
                                <?php echo $voucher['type'] == 'percentage' ? 'Phần trăm' : 'Cố định'; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php if ($voucher['type'] == 'percentage'): ?>
                                <?php echo $voucher['value']; ?>%
                                <?php if ($voucher['max_discount']): ?>
                                    <br><span class="text-xs text-gray-500">Tối đa: <?php echo formatPrice($voucher['max_discount']); ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php echo formatPrice($voucher['value']); ?>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo $voucher['total_used']; ?>
                            <?php if ($voucher['usage_limit']): ?>
                                / <?php echo $voucher['usage_limit']; ?>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?php echo $voucher['id']; ?>">
                                <input type="hidden" name="is_active" value="<?php echo $voucher['is_active'] ? 0 : 1; ?>">
                                <button type="submit" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                    <?php echo $voucher['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo $voucher['is_active'] ? 'Hoạt động' : 'Tạm dừng'; ?>
                                </button>
                            </form>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php if ($voucher['expires_at']): ?>
                                <?php echo date('d/m/Y H:i', strtotime($voucher['expires_at'])); ?>
                            <?php else: ?>
                                Không giới hạn
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button class="text-indigo-600 hover:text-indigo-900 mr-3 edit-btn" 
                                    data-voucher='<?php echo json_encode($voucher, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteVoucher(<?php echo $voucher['id']; ?>)"
                                    class="text-red-600 hover:text-red-900">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Voucher Modal -->
<div id="addModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto">
        <form method="POST" class="flex flex-col h-full">
            <!-- hidden fields -->
            <input type="hidden" name="action" id="form_action" value="add">
            <input type="hidden" name="id" id="voucher_id" value="">

            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200 sticky top-0 bg-white z-10">
                <h3 class="text-lg font-medium text-gray-900">Thêm Voucher Mới</h3>
            </div>

            <!-- Form body -->
            <div class="px-6 py-4 space-y-4 overflow-y-auto">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mã voucher *</label>
                    <input type="text" name="code" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tên voucher *</label>
                    <input type="text" name="name" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
                    <textarea name="description" rows="2"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Loại *</label>
                        <select name="type" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="percentage">Phần trăm</option>
                            <option value="fixed">Cố định</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Giá trị *</label>
                        <input type="number" name="value" step="0.01" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Đơn hàng tối thiểu (VNĐ)</label>
                    <input type="number" name="min_order_amount" value="0" step="1000"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Giảm tối đa (VNĐ)</label>
                    <input type="number" name="max_discount" step="1000"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Giới hạn sử dụng</label>
                    <input type="number" name="usage_limit" min="1"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bắt đầu</label>
                        <input type="datetime-local" name="starts_at"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kết thúc</label>
                        <input type="datetime-local" name="expires_at"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3 sticky bottom-0">
                <button type="button" onclick="closeAddModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    Hủy
                </button>
                <button type="submit" id="submitBtn"
                        class="px-4 py-2 text-sm font-medium text-white bg-primary rounded-md hover:bg-primary-dark">
                    Thêm
                </button>
            </div>
        </form>
    </div>
</div>


<script>
function openAddModal() { //changed: chỉnh sửa hàm openAddModal() để form linh động hơn (vừa dùng cho thêm, vừa cho chỉnh sửa)
    document.getElementById('addModal').classList.remove('hidden');
    document.querySelector('#addModal h3').textContent = 'Thêm Voucher Mới';
    document.getElementById('submitBtn').textContent = 'Thêm';
    document.getElementById('form_action').value = 'add';
    document.getElementById('voucher_id').value = '';

    // reset tất cả input
    document.querySelector('input[name="code"]').value = '';
    document.querySelector('input[name="name"]').value = '';
    document.querySelector('textarea[name="description"]').value = '';
    document.querySelector('select[name="type"]').value = 'percentage';
    document.querySelector('input[name="value"]').value = '';
    document.querySelector('input[name="min_order_amount"]').value = '0';
    document.querySelector('input[name="max_discount"]').value = '';
    document.querySelector('input[name="usage_limit"]').value = '';
    document.querySelector('input[name="starts_at"]').value = '';
    document.querySelector('input[name="expires_at"]').value = '';
}

function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
}

function editVoucher(voucher) { //changed: phát triển chức năng thêm voucher
    document.getElementById('addModal').classList.remove('hidden');
    document.querySelector('#addModal h3').textContent = 'Chỉnh sửa Voucher';

    document.getElementById('form_action').value = 'edit';
    document.getElementById('voucher_id').value = voucher.id;

    document.querySelector('input[name="code"]').value = voucher.code;
    document.querySelector('input[name="name"]').value = voucher.name;
    document.querySelector('textarea[name="description"]').value = voucher.description || '';
    document.querySelector('select[name="type"]').value = voucher.type;
    document.querySelector('input[name="value"]').value = voucher.value;
    document.querySelector('input[name="min_order_amount"]').value = voucher.min_order_amount;
    document.querySelector('input[name="max_discount"]').value = voucher.max_discount || '';
    document.querySelector('input[name="usage_limit"]').value = voucher.usage_limit || '';

    document.querySelector('input[name="starts_at"]').value = voucher.starts_at ? voucher.starts_at.replace(' ', 'T') : '';
    document.querySelector('input[name="expires_at"]').value = voucher.expires_at ? voucher.expires_at.replace(' ', 'T') : '';

    // đổi nút submit
    document.getElementById('submitBtn').textContent = 'Cập nhật';
}




function deleteVoucher(id) { // changed: phát triển chức năng xóa voucher
    if (confirm('Bạn có chắc chắn muốn xóa voucher này?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'vouchers.php'; // file hiện tại xử lý
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();       
    }
}

document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const voucher = JSON.parse(this.dataset.voucher);
        editVoucher(voucher);
    });
});

</script>

<?php include '../includes/footer.php'; ?>
