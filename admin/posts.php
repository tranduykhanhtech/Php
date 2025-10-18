<?php
require_once '../config/database.php';

requireAdmin();

$page_title = 'Quản lý bài viết';

// Handle create
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['form_action'] ?? (
        isset($_POST['create_post']) ? 'create' : (isset($_POST['update_post']) ? 'update' : (isset($_POST['delete_post']) ? 'delete' : ''))
    );

    if ($action === 'create' || isset($_POST['create_post'])) {
        $title = sanitize($_POST['title'] ?? '');
        $slug = sanitize($_POST['slug'] ?? '') ?: generateSlug($title);
        $excerpt = sanitize($_POST['excerpt'] ?? '');
        $content = $_POST['content'] ?? '';
        $featured_image = sanitize($_POST['featured_image'] ?? '');
        $is_published = isset($_POST['is_published']) ? 1 : 0;
        $author_id = $_SESSION['user_id'] ?? null;

        try {
            $stmt = $pdo->prepare("INSERT INTO posts (title, slug, excerpt, content, featured_image, author_id, is_published, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$title, $slug, $excerpt, $content, $featured_image, $author_id, $is_published]);
            $_SESSION['success'] = 'Tạo bài viết thành công.';
        } catch (Exception $e) {
            error_log('Post create error: ' . $e->getMessage());
            $_SESSION['error'] = 'Có lỗi xảy ra khi tạo bài viết.';
        }
    }

    if ($action === 'update' || isset($_POST['update_post'])) {
        $id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
        $title = sanitize($_POST['title'] ?? '');
        $slug = sanitize($_POST['slug'] ?? '') ?: generateSlug($title);
        $excerpt = sanitize($_POST['excerpt'] ?? '');
        $content = $_POST['content'] ?? '';
        $featured_image = sanitize($_POST['featured_image'] ?? '');
        $is_published = isset($_POST['is_published']) ? 1 : 0;

        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE posts SET title = ?, slug = ?, excerpt = ?, content = ?, featured_image = ?, is_published = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$title, $slug, $excerpt, $content, $featured_image, $is_published, $id]);
                $_SESSION['success'] = 'Cập nhật bài viết thành công.';
            } catch (Exception $e) {
                error_log('Post update error: ' . $e->getMessage());
                $_SESSION['error'] = 'Có lỗi xảy ra khi cập nhật bài viết.';
            }
        } else {
            $_SESSION['error'] = 'ID bài viết không hợp lệ.';
        }
    }

    if ($action === 'delete' || isset($_POST['delete_post'])) {
        $id = isset($_POST['delete_post']) ? (int)$_POST['delete_post'] : (isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
                $stmt->execute([$id]);
                if ($stmt->rowCount() > 0) {
                    $_SESSION['success'] = 'Xóa bài viết thành công.';
                } else {
                    $_SESSION['error'] = 'Bài viết không tồn tại hoặc đã bị xóa.';
                }
            } catch (Exception $e) {
                error_log('Post delete error: ' . $e->getMessage());
                $_SESSION['error'] = 'Có lỗi khi xóa bài viết.';
            }
        } else {
            $_SESSION['error'] = 'ID bài viết không hợp lệ.';
        }
    }

    redirect('posts.php');
}

// List posts with pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

try {
    $stmt = $pdo->prepare("SELECT p.*, u.full_name as author_name FROM posts p LEFT JOIN users u ON p.author_id = u.id ORDER BY p.created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute([$limit, $offset]);
    $posts = $stmt->fetchAll();

    $count = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
    $total_pages = max(1, (int)ceil($count / $limit));
} catch (Exception $e) {
    error_log('Posts fetch error: ' . $e->getMessage());
    $posts = [];
    $total_pages = 1;
}

include 'includes/header.php';
?>

<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-semibold text-gray-900">Quản lý bài viết</h1>
    <div>
        <button onclick="openCreate()" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-green-600">
            <i class="fas fa-plus mr-2"></i>Thêm bài viết
        </button>
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tiêu đề</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tác giả</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày tạo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hành động</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($posts)): ?>
                    <tr><td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">Không có bài viết</td></tr>
                <?php else: ?>
                    <?php foreach ($posts as $p): ?>
                        <tr>
                            <td class="px-6 py-4 text-sm"><?php echo $p['id']; ?></td>
                            <td class="px-6 py-4 text-sm font-medium"><?php echo htmlspecialchars($p['title']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars($p['author_name'] ?? ''); ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo $p['is_published'] ? 'Đã đăng' : 'Bản nháp'; ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500"><?php echo date('d/m/Y', strtotime($p['created_at'])); ?></td>
                            <td class="px-6 py-4 text-sm">
                                <div class="flex items-center space-x-2">
                                    <button onclick='openEdit(<?php echo json_encode($p); ?>)' class="text-blue-600 hover:text-blue-800"><i class="fas fa-edit"></i></button>
                                    <form method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa bài viết này?');">
                                        <input type="hidden" name="delete_post" value="<?php echo $p['id']; ?>">
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
            <?php for ($i=1;$i<=$total_pages;$i++): ?><li><a href="?page=<?php echo $i; ?>" class="px-3 py-2 <?php echo $i==$page ? 'bg-primary text-white' : 'bg-white'; ?> border rounded"><?php echo $i; ?></a></li><?php endfor; ?>
            <?php if ($page < $total_pages): ?><li><a href="?page=<?php echo $page+1; ?>" class="px-3 py-2 bg-white border rounded">&raquo;</a></li><?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>

<!-- Create/Edit Modal -->
<div id="postModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full">
            <form method="POST" id="postForm">
                <input type="hidden" name="form_action" id="form_action" value="create">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold" id="postModalTitle">Thêm bài viết mới</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm">Tiêu đề</label>
                        <input type="text" name="title" id="post_title" class="w-full px-3 py-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-sm">Slug (tùy chọn)</label>
                        <input type="text" name="slug" id="post_slug" class="w-full px-3 py-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-sm">Mô tả ngắn</label>
                        <textarea name="excerpt" id="post_excerpt" class="w-full px-3 py-2 border rounded" rows="3"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm">Ảnh đại diện (URL)</label>
                        <input type="text" name="featured_image" id="post_image" class="w-full px-3 py-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-sm">Nội dung</label>
                        <textarea name="content" id="post_content" class="w-full px-3 py-2 border rounded" rows="8"></textarea>
                    </div>
                    <div class="flex items-center space-x-4">
                        <label class="inline-flex items-center"><input type="checkbox" name="is_published" id="post_published" class="mr-2"> Đã đăng</label>
                    </div>
                </div>
                <div class="px-6 py-4 border-t flex justify-end space-x-2">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-100 rounded">Hủy</button>
                    <button type="submit" id="postSubmit" class="px-4 py-2 bg-primary text-white rounded">Lưu</button>
                </div>
                <input type="hidden" name="post_id" id="post_id">
            </form>
        </div>
    </div>
</div>

<script>
function openCreate(){
    document.getElementById('postModal').classList.remove('hidden');
    document.getElementById('postModalTitle').textContent = 'Thêm bài viết mới';
    document.getElementById('form_action').value = 'create';
    document.getElementById('postForm').reset();
    document.getElementById('postSubmit').textContent = 'Tạo';
}
function openEdit(post){
    document.getElementById('postModal').classList.remove('hidden');
    document.getElementById('postModalTitle').textContent = 'Chỉnh sửa bài viết';
    document.getElementById('form_action').value = 'update';
    document.getElementById('post_title').value = post.title || '';
    document.getElementById('post_slug').value = post.slug || '';
    document.getElementById('post_excerpt').value = post.excerpt || '';
    document.getElementById('post_image').value = post.featured_image || '';
    document.getElementById('post_content').value = post.content || '';
    document.getElementById('post_published').checked = post.is_published == 1;
    document.getElementById('post_id').value = post.id;
    document.getElementById('postSubmit').textContent = 'Cập nhật';
}
function closeModal(){ document.getElementById('postModal').classList.add('hidden'); }
</script>

<?php include 'includes/footer.php'; ?>
