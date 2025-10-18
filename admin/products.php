<?php
require_once '../config/database.php';

requireAdmin();

$page_title = 'Quản lý sản phẩm';

// Helper: upload file to local storage
function upload_to_local($file, $upload_dir = 'uploads/products/') {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return false;
    }
    
    // Kiểm tra kích thước file
    if ($file['size'] > MAX_FILE_SIZE) {
        return false;
    }
    
    // Kiểm tra loại file
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = mime_content_type($file['tmp_name']);
    
    if (!in_array($file_type, $allowed_types)) {
        return false;
    }
    
    // Tạo tên file unique
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $file_name = uniqid() . '_' . time() . '.' . $file_extension;
    $file_path = $upload_dir . $file_name;
    
    // Tạo thư mục nếu chưa có
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Di chuyển file
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        return SITE_URL . '/' . $file_path;
    }
    
    return false;
}

// Xử lý thêm/sửa/xóa sản phẩm
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['form_action'] ?? (
        isset($_POST['add_product']) ? 'add' : (isset($_POST['update_product']) ? 'update' : (isset($_POST['delete_product']) ? 'delete' : ''))
    );

    if ($action === 'add' || isset($_POST['add_product'])) {
        $name = sanitize($_POST['name']);
        $slug = generateSlug($name);
        $description = sanitize($_POST['description']);
        $short_description = sanitize($_POST['short_description']);
        $price = (float)$_POST['price'];
        $sale_price = !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : null;
        $sku = sanitize($_POST['sku']);
        $stock_quantity = (int)$_POST['stock_quantity'];
        $category_id = (int)$_POST['category_id'];
        $ingredients = sanitize($_POST['ingredients']);
        $usage_instructions = sanitize($_POST['usage_instructions']);
        $weight = sanitize($_POST['weight']);
        $origin = sanitize($_POST['origin']);
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_bestseller = isset($_POST['is_bestseller']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Handle images: upload to local storage
        $images_urls = [];
        if (!empty($_FILES['images']) && is_array($_FILES['images']['tmp_name'])) {
            for ($i = 0; $i < count($_FILES['images']['tmp_name']); $i++) {
                $file = [
                    'tmp_name' => $_FILES['images']['tmp_name'][$i],
                    'name' => $_FILES['images']['name'][$i],
                    'size' => $_FILES['images']['size'][$i],
                    'type' => $_FILES['images']['type'][$i]
                ];
                
                $uploaded = upload_to_local($file);
                if ($uploaded) {
                    $images_urls[] = $uploaded;
                }
            }
        }

        // Also accept comma/newline separated URLs from an images_urls textarea
        if (!empty($_POST['images_urls'])) {
            $raw = trim($_POST['images_urls']);
            $parts = preg_split('/[\r\n,]+/', $raw);
            foreach ($parts as $p) {
                $u = trim($p);
                if (filter_var($u, FILTER_VALIDATE_URL)) $images_urls[] = $u;
            }
        }

        $images_json = !empty($images_urls) ? json_encode(array_values($images_urls)) : null;

        $stmt = $pdo->prepare("
            INSERT INTO products (name, slug, description, short_description, price, sale_price, sku, 
                                stock_quantity, category_id, ingredients, usage_instructions, weight, 
                                origin, is_featured, is_bestseller, is_active, images)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([
            $name, $slug, $description, $short_description, $price, $sale_price, $sku,
            $stock_quantity, $category_id, $ingredients, $usage_instructions, $weight,
            $origin, $is_featured, $is_bestseller, $is_active, $images_json
        ])) {
            $_SESSION['success'] = 'Thêm sản phẩm thành công';
        } else {
            $_SESSION['error'] = 'Có lỗi xảy ra khi thêm sản phẩm';
        }
    }
    
    if ($action === 'update' || isset($_POST['update_product'])) {
        $id = (int)$_POST['product_id'];
        $name = sanitize($_POST['name']);
        $slug = generateSlug($name);
        $description = sanitize($_POST['description']);
        $short_description = sanitize($_POST['short_description']);
        $price = (float)$_POST['price'];
        $sale_price = !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : null;
        $sku = sanitize($_POST['sku']);
        $stock_quantity = (int)$_POST['stock_quantity'];
        $category_id = (int)$_POST['category_id'];
        $ingredients = sanitize($_POST['ingredients']);
        $usage_instructions = sanitize($_POST['usage_instructions']);
        $weight = sanitize($_POST['weight']);
        $origin = sanitize($_POST['origin']);
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_bestseller = isset($_POST['is_bestseller']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Handle images for update: if new files provided, upload and replace images; else keep existing
        $images_urls = [];
        if (!empty($_FILES['images']) && is_array($_FILES['images']['tmp_name']) && $_FILES['images']['tmp_name'][0]) {
            for ($i = 0; $i < count($_FILES['images']['tmp_name']); $i++) {
                $file = [
                    'tmp_name' => $_FILES['images']['tmp_name'][$i],
                    'name' => $_FILES['images']['name'][$i],
                    'size' => $_FILES['images']['size'][$i],
                    'type' => $_FILES['images']['type'][$i]
                ];
                
                $uploaded = upload_to_local($file);
                if ($uploaded) {
                    $images_urls[] = $uploaded;
                }
            }
        }

        // also accept images_urls textarea on update
        if (!empty($_POST['images_urls'])) {
            $raw = trim($_POST['images_urls']);
            $parts = preg_split('/[\r\n,]+/', $raw);
            foreach ($parts as $p) {
                $u = trim($p);
                if (filter_var($u, FILTER_VALIDATE_URL)) $images_urls[] = $u;
            }
        }

        if (empty($images_urls)) {
            // keep existing images
            $existing = $pdo->prepare("SELECT images FROM products WHERE id = ?");
            $existing->execute([$id]);
            $row = $existing->fetch();
            $images_json = $row ? $row['images'] : null;
        } else {
            $images_json = json_encode(array_values($images_urls));
        }

        $stmt = $pdo->prepare("
            UPDATE products SET name=?, slug=?, description=?, short_description=?, price=?, 
                              sale_price=?, sku=?, stock_quantity=?, category_id=?, ingredients=?, 
                              usage_instructions=?, weight=?, origin=?, is_featured=?, is_bestseller=?, 
                              is_active=?, images=?, updated_at=NOW()
            WHERE id=?
        ");

        if ($stmt->execute([
            $name, $slug, $description, $short_description, $price, $sale_price, $sku,
            $stock_quantity, $category_id, $ingredients, $usage_instructions, $weight,
            $origin, $is_featured, $is_bestseller, $is_active, $images_json, $id
        ])) {
            $_SESSION['success'] = 'Cập nhật sản phẩm thành công';
        } else {
            $_SESSION['error'] = 'Có lỗi xảy ra khi cập nhật sản phẩm';
        }
    }
    
    if ($action === 'delete' || isset($_POST['delete_product'])) {
        $id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        if ($id <= 0) {
            $_SESSION['error'] = 'ID sản phẩm không hợp lệ.';
        } else {
            try {
                // Check exists
                $check = $pdo->prepare("SELECT id FROM products WHERE id = ?");
                $check->execute([$id]);
                if (!$check->fetch()) {
                    $_SESSION['error'] = 'Sản phẩm không tồn tại.';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                    $stmt->execute([$id]);
                    if ($stmt->rowCount() > 0) {
                        $_SESSION['success'] = 'Xóa sản phẩm thành công';
                    } else {
                        $_SESSION['error'] = 'Không thể xóa sản phẩm (không có bản ghi bị ảnh hưởng).';
                    }
                }
            } catch (Exception $e) {
                error_log('Product delete error (id=' . $id . '): ' . $e->getMessage());
                $_SESSION['error'] = 'Có lỗi xảy ra khi xóa sản phẩm. Kiểm tra log.';
            }
        }
    }
    
    redirect('admin/products.php');
}

// Lấy danh sách sản phẩm
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$products = $pdo->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.created_at DESC 
    LIMIT $limit OFFSET $offset
")->fetchAll();

// Lấy danh mục
$categories = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name")->fetchAll();

include 'includes/header.php';
?>

<!-- Add/Edit Product Modal -->
<div id="productModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-screen overflow-y-auto">
            <form method="POST" id="productForm" enctype="multipart/form-data">
                <input type="hidden" name="form_action" id="form_action" value="add">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900" id="modalTitle">Thêm sản phẩm mới</h3>
                </div>
                
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tên sản phẩm *</label>
                            <input type="text" name="name" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">SKU</label>
                            <input type="text" name="sku"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Danh mục *</label>
                            <select name="category_id" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="">Chọn danh mục</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Giá *</label>
                            <input type="number" name="price" step="0.01" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Giá khuyến mãi</label>
                            <input type="number" name="sale_price" step="0.01"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Số lượng tồn kho *</label>
                            <input type="number" name="stock_quantity" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Trọng lượng</label>
                            <input type="text" name="weight"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Xuất xứ</label>
                            <input type="text" name="origin"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mô tả ngắn</label>
                        <textarea name="short_description" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mô tả chi tiết</label>
                        <textarea name="description" rows="5"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Thành phần</label>
                        <textarea name="ingredients" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Hướng dẫn sử dụng</label>
                        <textarea name="usage_instructions" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                    </div>
                    
                    <div class="flex space-x-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_featured" class="rounded border-gray-300 text-primary focus:ring-primary">
                            <span class="ml-2 text-sm text-gray-700">Sản phẩm nổi bật</span>
                        </label>
                        
                        <label class="flex items-center">
                            <input type="checkbox" name="is_bestseller" class="rounded border-gray-300 text-primary focus:ring-primary">
                            <span class="ml-2 text-sm text-gray-700">Sản phẩm bán chạy</span>
                        </label>
                        
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" checked class="rounded border-gray-300 text-primary focus:ring-primary">
                            <span class="ml-2 text-sm text-gray-700">Kích hoạt</span>
                        </label>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ảnh sản phẩm (tải lên hoặc dán URL)</label>
                        <input type="file" name="images[]" accept="image/*" multiple class="mb-2">
                        <textarea name="images_urls" rows="2" placeholder="Hoặc dán URL ảnh, mỗi URL 1 dòng hoặc cách nhau bằng dấu phẩy" class="w-full px-3 py-2 border border-gray-300 rounded-md"></textarea>
                    </div>
                </div>
                
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                    <button type="button" onclick="closeModal()" 
                            class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                        Hủy
                    </button>
                    <button type="submit" name="add_product" id="submitBtn"
                            class="px-4 py-2 bg-primary text-white rounded-md hover:bg-green-600">
                        Lưu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-semibold text-gray-900">Quản lý sản phẩm</h1>
        <button onclick="openModal()" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-green-600">
            <i class="fas fa-plus mr-2"></i>Thêm sản phẩm
        </button>
    </div>
</div>

<!-- Products Table -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sản phẩm</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Danh mục</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Giá</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tồn kho</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($products as $product): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-12 w-12">
                                <?php
                                $thumb = null;
                                if (!empty($product['images'])) {
                                    $imgs = json_decode($product['images'], true);
                                    if (!empty($imgs)) $thumb = $imgs[0];
                                }
                                ?>
                                <?php if ($thumb): ?>
                                <img src="<?php echo htmlspecialchars($thumb); ?>" class="h-12 w-12 rounded-md object-cover" loading="lazy">
                                <?php else: ?>
                                <div class="h-12 w-12 bg-gray-200 rounded-md flex items-center justify-center">
                                    <i class="fas fa-image text-gray-400"></i>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></div>
                                <div class="text-sm text-gray-500">SKU: <?php echo htmlspecialchars($product['sku']); ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo htmlspecialchars($product['category_name']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <div>
                            <div class="font-medium"><?php echo formatPrice($product['price']); ?></div>
                            <?php if ($product['sale_price']): ?>
                            <div class="text-sm text-gray-500 line-through"><?php echo formatPrice($product['sale_price']); ?></div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo $product['stock_quantity']; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex flex-col space-y-1">
                            <?php if ($product['is_featured']): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                Nổi bật
                            </span>
                            <?php endif; ?>
                            
                            <?php if ($product['is_bestseller']): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                Bán chạy
                            </span>
                            <?php endif; ?>
                            
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $product['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                <?php echo $product['is_active'] ? 'Kích hoạt' : 'Tạm dừng'; ?>
                            </span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex space-x-2">
                            <button onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)" 
                                    class="text-primary hover:text-green-600">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" class="inline" onsubmit="return confirmDelete()">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <button type="submit" name="delete_product" class="text-red-600 hover:text-red-800">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('productModal').classList.remove('hidden');
    document.getElementById('productForm').reset();
    document.getElementById('modalTitle').textContent = 'Thêm sản phẩm mới';
    document.getElementById('submitBtn').name = 'add_product';
    document.getElementById('submitBtn').textContent = 'Thêm';
    document.getElementById('form_action').value = 'add';
}

function closeModal() {
    document.getElementById('productModal').classList.add('hidden');
}

function editProduct(product) {
    document.getElementById('productModal').classList.remove('hidden');
    document.getElementById('modalTitle').textContent = 'Sửa sản phẩm';
    document.getElementById('submitBtn').name = 'update_product';
    document.getElementById('submitBtn').textContent = 'Cập nhật';
    document.getElementById('form_action').value = 'update';
    
    // Fill form with product data
    document.querySelector('input[name="name"]').value = product.name;
    document.querySelector('input[name="sku"]').value = product.sku || '';
    document.querySelector('select[name="category_id"]').value = product.category_id;
    document.querySelector('input[name="price"]').value = product.price;
    document.querySelector('input[name="sale_price"]').value = product.sale_price || '';
    document.querySelector('input[name="stock_quantity"]').value = product.stock_quantity;
    document.querySelector('input[name="weight"]').value = product.weight || '';
    document.querySelector('input[name="origin"]').value = product.origin || '';
    document.querySelector('textarea[name="short_description"]').value = product.short_description || '';
    document.querySelector('textarea[name="description"]').value = product.description || '';
    document.querySelector('textarea[name="ingredients"]').value = product.ingredients || '';
    document.querySelector('textarea[name="usage_instructions"]').value = product.usage_instructions || '';
    document.querySelector('input[name="is_featured"]').checked = product.is_featured == 1;
    document.querySelector('input[name="is_bestseller"]').checked = product.is_bestseller == 1;
    document.querySelector('input[name="is_active"]').checked = product.is_active == 1;
    
    // Add hidden input for product ID
    let existingInput = document.querySelector('input[name="product_id"]');
    if (existingInput) {
        existingInput.remove();
    }
    let productIdInput = document.createElement('input');
    productIdInput.type = 'hidden';
    productIdInput.name = 'product_id';
    productIdInput.value = product.id;
    document.getElementById('productForm').appendChild(productIdInput);
    // Prefill images URLs textarea if existing
    try {
        var imgs = product.images ? (typeof product.images === 'string' ? JSON.parse(product.images) : product.images) : [];
        document.querySelector('textarea[name="images_urls"]').value = imgs && imgs.length ? imgs.join("\n") : '';
    } catch (e) {
        document.querySelector('textarea[name="images_urls"]').value = '';
    }
}
</script>

<?php include 'includes/footer.php'; ?>
