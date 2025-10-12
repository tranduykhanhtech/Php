<?php
require_once 'config/database.php';

$page_title = 'Sản phẩm';
$page_description = 'Khám phá bộ sưu tập mỹ phẩm thiên nhiên đa dạng và chất lượng cao';

// Lấy tham số
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search_raw = isset($_GET['q']) ? $_GET['q'] : '';
$search = sanitize($search_raw);
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Xây dựng query
$where_conditions = ["p.is_active = 1"];
$params = [];

if ($category_id > 0) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_id;
}

// Require at least 3 characters for free-text search to avoid expensive broad LIKE scans
if (!empty($search) && mb_strlen($search) >= 3) {
    $where_conditions[] = "(p.name LIKE ? OR p.short_description LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
} else {
    // If user entered a too-short query, ignore it to avoid slow queries
    if (!empty($search)) {
        $search = '';
    }
}

$where_clause = implode(' AND ', $where_conditions);

// Sắp xếp
$order_clause = "ORDER BY p.created_at DESC";
switch ($sort) {
    case 'price_low':
        $order_clause = "ORDER BY p.price ASC";
        break;
    case 'price_high':
        $order_clause = "ORDER BY p.price DESC";
        break;
    case 'name':
        $order_clause = "ORDER BY p.name ASC";
        break;
    case 'bestseller':
        $order_clause = "ORDER BY p.is_bestseller DESC, p.created_at DESC";
        break;
}

// Lấy sản phẩm
// Select only needed columns to reduce payload (avoid selecting large text fields)
$sql = "
    SELECT p.id, p.name, p.price, p.sale_price, p.images, p.short_description, p.is_bestseller, p.category_id, p.created_at, p.stock_quantity,
           c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE $where_clause
    $order_clause
    LIMIT $limit OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Đếm tổng số sản phẩm
$count_sql = "SELECT COUNT(*) as total FROM products p WHERE $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_products = $count_stmt->fetch()['total'];
$total_pages = ceil($total_products / $limit);

// Lấy danh mục
$categories = $pdo->query("
    SELECT * FROM categories 
    WHERE is_active = 1 AND parent_id IS NULL 
    ORDER BY sort_order ASC
")->fetchAll();

// Lấy danh mục hiện tại
$current_category = null;
if ($category_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $current_category = $stmt->fetch();
}

include 'includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Breadcrumb -->
    <nav class="flex mb-8" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="index.php" class="text-gray-700 hover:text-primary">
                    <i class="fas fa-home mr-1"></i>Trang chủ
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-500">Sản phẩm</span>
                </div>
            </li>
            <?php if ($current_category): ?>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-500"><?php echo htmlspecialchars($current_category['name']); ?></span>
                </div>
            </li>
            <?php endif; ?>
        </ol>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Sidebar -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-md p-6 sticky top-24">
                <!-- Search -->
                <div class="mb-6">
                    <h3 class="font-semibold text-gray-900 mb-3">Tìm kiếm</h3>
                    <form method="GET" action="products.php">
                        <div class="flex">
                            <input type="text" name="q" placeholder="Tìm sản phẩm..." 
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-r-md hover:bg-green-600">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <?php if ($category_id): ?>
                            <input type="hidden" name="category" value="<?php echo $category_id; ?>">
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Categories -->
                <div class="mb-6">
                    <h3 class="font-semibold text-gray-900 mb-3">Danh mục</h3>
                    <ul class="space-y-2">
                        <li>
                            <a href="products.php" 
                               class="block px-3 py-2 rounded-md text-sm <?php echo !$category_id ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                                Tất cả sản phẩm
                            </a>
                        </li>
                        <?php foreach ($categories as $category): ?>
                        <li>
                            <a href="products.php?category=<?php echo $category['id']; ?>" 
                               class="block px-3 py-2 rounded-md text-sm <?php echo $category_id == $category['id'] ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Sort -->
                <div>
                    <h3 class="font-semibold text-gray-900 mb-3">Sắp xếp</h3>
                    <form method="GET" id="sort-form">
                        <select name="sort" onchange="document.getElementById('sort-form').submit()" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Mới nhất</option>
                            <option value="bestseller" <?php echo $sort == 'bestseller' ? 'selected' : ''; ?>>Bán chạy</option>
                            <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Giá thấp đến cao</option>
                            <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Giá cao đến thấp</option>
                            <option value="name" <?php echo $sort == 'name' ? 'selected' : ''; ?>>Tên A-Z</option>
                        </select>
                        <?php if ($category_id): ?>
                            <input type="hidden" name="category" value="<?php echo $category_id; ?>">
                        <?php endif; ?>
                        <?php if ($search): ?>
                            <input type="hidden" name="q" value="<?php echo htmlspecialchars($search); ?>">
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <!-- Products -->
        <div class="lg:col-span-3">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        <?php if ($current_category): ?>
                            <?php echo htmlspecialchars($current_category['name']); ?>
                        <?php elseif ($search): ?>
                            Kết quả tìm kiếm cho "<?php echo htmlspecialchars($search); ?>"
                        <?php else: ?>
                            Tất cả sản phẩm
                        <?php endif; ?>
                    </h1>
                    <p class="text-gray-600"><?php echo $total_products; ?> sản phẩm</p>
                </div>
            </div>

            <!-- Products Grid -->
            <?php if (empty($products)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Không tìm thấy sản phẩm</h3>
                    <p class="text-gray-600 mb-6">Hãy thử tìm kiếm với từ khóa khác hoặc duyệt các danh mục khác</p>
                    <a href="products.php" class="bg-primary text-white px-6 py-3 rounded-lg hover:bg-green-600 transition-colors">
                        Xem tất cả sản phẩm
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    <?php foreach ($products as $product): ?>
                    <div class="bg-white rounded-xl shadow-md overflow-hidden card-hover">
                        <div class="relative">
                            <?php 
                            $images = json_decode($product['images'], true);
                            $main_image = !empty($images) ? $images[0] : 'https://via.placeholder.com/300x300?text=No+Image';
                            ?>
                       <img src="<?php echo $main_image; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" 
                           class="w-full h-48 object-cover" loading="lazy">
                            <?php if ($product['sale_price']): ?>
                                <div class="absolute top-2 left-2 bg-accent text-white px-2 py-1 rounded-full text-xs font-semibold">
                                    Sale
                                </div>
                            <?php endif; ?>
                            <?php if ($product['is_bestseller']): ?>
                                <div class="absolute top-2 right-2 bg-yellow-500 text-white px-2 py-1 rounded-full text-xs font-semibold">
                                    <i class="fas fa-fire mr-1"></i>Bán chạy
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-4">
                            <div class="text-sm text-gray-500 mb-1"><?php echo htmlspecialchars($product['category_name']); ?></div>
                            <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="text-sm text-gray-600 mb-3 line-clamp-2"><?php echo htmlspecialchars($product['short_description']); ?></p>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <?php if ($product['sale_price']): ?>
                                        <span class="text-lg font-bold text-primary"><?php echo formatPrice($product['sale_price']); ?></span>
                                        <span class="text-sm text-gray-500 line-through"><?php echo formatPrice($product['price']); ?></span>
                                    <?php else: ?>
                                        <span class="text-lg font-bold text-primary"><?php echo formatPrice($product['price']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <a href="product.php?id=<?php echo $product['id']; ?>" 
                                   class="bg-primary text-white px-3 py-1 rounded-lg text-sm hover:bg-green-600 transition-colors">
                                    Xem chi tiết
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav class="flex justify-center">
                    <ul class="flex space-x-2">
                        <?php if ($page > 1): ?>
                            <li>
                                <a href="?page=<?php echo $page - 1; ?>&category=<?php echo $category_id; ?>&q=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>" 
                                   class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li>
                                <a href="?page=<?php echo $i; ?>&category=<?php echo $category_id; ?>&q=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>" 
                                   class="px-3 py-2 text-sm font-medium <?php echo $i == $page ? 'text-white bg-primary border-primary' : 'text-gray-500 bg-white border-gray-300 hover:bg-gray-50'; ?> border rounded-md">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li>
                                <a href="?page=<?php echo $page + 1; ?>&category=<?php echo $category_id; ?>&q=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>" 
                                   class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<?php include 'includes/footer.php'; ?>
