<?php
require_once '../config/database.php';

requireAdmin();

$page_title = 'Quản lý danh mục';

// Handle create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_category'])) {
    $name = sanitize($_POST['name'] ?? '');
    $slug = sanitize($_POST['slug'] ?? '');
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $sort_order = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($name)) {
        $_SESSION['error'] = 'Tên danh mục không được để trống.';
    } else {
        if (empty($slug)) $slug = generateSlug($name);
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, parent_id, sort_order, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        if ($stmt->execute([$name, $slug, $_POST['description'] ?? '', $parent_id, $sort_order, $is_active])) {
            $_SESSION['success'] = 'Thêm danh mục thành công.';
        } else {
            $_SESSION['error'] = 'Có lỗi xảy ra khi thêm danh mục.';
        }
    }

    redirect('admin/categories.php'); // changed: sửa đường dẫn để sau khi thực hiện chức năng create, trang sẽ load lại chính xác     
}

// Handle update
// đang được phát triển
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    $id = (int)$_POST['category_id'];
    $name = sanitize($_POST['name'] ?? '');
    $slug = sanitize($_POST['slug'] ?? '');
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $sort_order = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($name)) {
        $_SESSION['error'] = 'Tên danh mục không được để trống.';
    } else {
        if (empty($slug)) $slug = generateSlug($name);
        $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, parent_id = ?, sort_order = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
        if ($stmt->execute([$name, $slug, $_POST['description'] ?? '', $parent_id, $sort_order, $is_active, $id])) {
            $_SESSION['success'] = 'Cập nhật danh mục thành công.';
        } else {
            $_SESSION['error'] = 'Có lỗi xảy ra khi cập nhật danh mục.';
        }
    }

   redirect('admin/categories.php'); // changed: sửa đường dẫn để sau khi thực hiện chức năng create, trang sẽ load lại chính xác   
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = (int)$_POST['delete_id'];
    // Set parent_id to NULL for children before delete to avoid FK issues
    $pdo->prepare("UPDATE categories SET parent_id = NULL WHERE parent_id = ?")->execute([$id]);
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    if ($stmt->execute([$id])) {
        $_SESSION['success'] = 'Xóa danh mục thành công.';
    } else {
        $_SESSION['error'] = 'Có lỗi xảy ra khi xóa danh mục.';
    }

    redirect('admin/categories.php'); // changed: sửa đường dẫn để sau khi thực hiện chức năng create, trang sẽ load lại chính xác   
}

// Fetch categories (flat list)
$cats = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC, name ASC")->fetchAll();

// For edit form
$edit = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$eid]);
    $edit = $stmt->fetch();
}

include 'includes/header.php';
?>

<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-semibold text-gray-900">Quản lý danh mục</h1>
    <a href="categories.php" class="px-4 py-2 bg-white border rounded-md text-sm hover:bg-gray-50">Làm mới</a>
</div>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert bg-green-50 text-green-800 px-4 py-2 rounded mb-4"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert bg-red-50 text-red-800 px-4 py-2 rounded mb-4"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold mb-4">Danh sách danh mục</h2>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tên</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Parent</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Sắp xếp</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Hành động</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($cats as $c): ?>
                    <tr>
                        <td class="px-4 py-3 text-sm"><?php echo $c['id']; ?></td>
                        <td class="px-4 py-3 text-sm font-medium"><?php echo htmlspecialchars($c['name']); ?></td>
                        <td class="px-4 py-3 text-sm">
                            <?php
                            if ($c['parent_id']) {
                                $p = array_filter($cats, function($x) use ($c) { return $x['id'] == $c['parent_id']; });
                                $p = array_values($p);
                                echo isset($p[0]) ? htmlspecialchars($p[0]['name']) : '-';
                            } else echo '-';
                            ?>
                        </td>
                        <td class="px-4 py-3 text-sm"><?php echo (int)$c['sort_order']; ?></td>
                        <td class="px-4 py-3 text-sm"><?php echo $c['is_active'] ? 'Kích hoạt' : 'Không kích hoạt'; ?></td>
                        <td class="px-4 py-3 text-sm">
                            <a href="?edit=<?php echo $c['id']; ?>" class="text-blue-600 hover:text-blue-800 mr-2"><i class="fas fa-edit"></i></a>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Bạn có chắc muốn xóa danh mục này?');">
                                <input type="hidden" name="delete_id" value="<?php echo $c['id']; ?>">
                                <button type="submit" class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <aside class="bg-white rounded-lg shadow-md p-6">
        <?php if ($edit): ?>
            <h2 class="text-lg font-semibold mb-4">Chỉnh sửa danh mục</h2>
            <form method="POST">
                <div class="mb-3">
                    <label class="block text-sm text-gray-600 mb-1">Tên</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($edit['name']); ?>" class="w-full px-3 py-2 border rounded-md">
                </div>
                <div class="mb-3">
                    <label class="block text-sm text-gray-600 mb-1">Slug</label>
                    <input type="text" name="slug" value="<?php echo htmlspecialchars($edit['slug']); ?>" class="w-full px-3 py-2 border rounded-md">
                </div>
                <div class="mb-3">
                    <label class="block text-sm text-gray-600 mb-1">Parent</label>
                    <select name="parent_id" class="w-full px-3 py-2 border rounded-md">
                        <option value="">-- Không --</option>
                        <?php foreach ($cats as $p): if ($p['id'] == $edit['id']) continue; ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo $p['id'] == $edit['parent_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="block text-sm text-gray-600 mb-1">Mô tả</label>
                    <textarea name="description" class="w-full px-3 py-2 border rounded-md"><?php echo htmlspecialchars($edit['description']); ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="block text-sm text-gray-600 mb-1">Thứ tự</label>
                    <input type="number" name="sort_order" value="<?php echo (int)$edit['sort_order']; ?>" class="w-full px-3 py-2 border rounded-md">
                </div>
                <div class="mb-3">
                    <label class="inline-flex items-center"><input type="checkbox" name="is_active" value="1" <?php echo $edit['is_active'] ? 'checked' : ''; ?> class="mr-2"> Kích hoạt</label>
                </div>
                <input type="hidden" name="category_id" value="<?php echo $edit['id']; ?>">
                <div class="flex space-x-2">
                    <button type="submit" name="update_category" class="px-4 py-2 bg-primary text-white rounded-md">Lưu</button>
                    <a href="categories.php" class="px-4 py-2 bg-gray-100 rounded-md">Hủy</a>
                </div>
            </form>
        <?php else: ?>
            <h2 class="text-lg font-semibold mb-4">Thêm danh mục mới</h2>
            <form method="POST">
                <div class="mb-3">
                    <label class="block text-sm text-gray-600 mb-1">Tên</label>
                    <input type="text" name="name" class="w-full px-3 py-2 border rounded-md" required>
                </div>
                <div class="mb-3">
                    <label class="block text-sm text-gray-600 mb-1">Slug (tùy chọn)</label>
                    <input type="text" name="slug" class="w-full px-3 py-2 border rounded-md">
                </div>
                <div class="mb-3">
                    <label class="block text-sm text-gray-600 mb-1">Parent</label>
                    <select name="parent_id" class="w-full px-3 py-2 border rounded-md">
                        <option value="">-- Không --</option>
                        <?php foreach ($cats as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="block text-sm text-gray-600 mb-1">Mô tả</label>
                    <textarea name="description" class="w-full px-3 py-2 border rounded-md"></textarea>
                </div>
                <div class="mb-3">
                    <label class="block text-sm text-gray-600 mb-1">Thứ tự</label>
                    <input type="number" name="sort_order" value="0" class="w-full px-3 py-2 border rounded-md">
                </div>
                <div class="mb-3">
                    <label class="inline-flex items-center"><input type="checkbox" name="is_active" value="1" checked class="mr-2"> Kích hoạt</label>
                </div>
                <div class="flex space-x-2">
                    <button type="submit" name="create_category" class="px-4 py-2 bg-primary text-white rounded-md">Tạo</button>
                    <button type="reset" class="px-4 py-2 bg-gray-100 rounded-md">Xóa</button>
                </div>
            </form>
        <?php endif; ?>
    </aside>
</div>

<?php include 'includes/footer.php'; ?>

?>
