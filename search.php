<?php
require_once 'config/database.php';

$query = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Giới hạn độ dài query để tránh tấn công
if (mb_strlen($query) > 100) {
    $query = mb_substr($query, 0, 100);
}

$page_title = $query ? ('Tìm kiếm: ' . htmlspecialchars($query)) : 'Tìm kiếm sản phẩm';
$page_description = 'Kết quả tìm kiếm sản phẩm theo từ khóa';

// Nếu trống query, hiển thị gợi ý thay vì lỗi
$where_clause = 'p.is_active = 1';
$params = [];
if ($query !== '') {
    // So khop khong phan biet hoa thuong: dung LOWER() de tranh vuong collation
    $where_clause .= ' AND (LOWER(p.name) LIKE ? OR LOWER(p.description) LIKE ? OR LOWER(p.short_description) LIKE ?)';
    $qLower = function_exists('mb_strtolower') ? mb_strtolower($query, 'UTF-8') : strtolower($query);
    $like = "%$qLower%";
    $params = [$like, $like, $like];
}

// Lấy tổng số kết quả
$count_sql = "SELECT COUNT(*) as total FROM products p WHERE $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total = (int)$count_stmt->fetch()['total'];
$total_pages = max(1, (int)ceil($total / $limit));

// Lấy danh sách sản phẩm theo trang
$sql = "
    SELECT p.*, c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE $where_clause
    ORDER BY p.created_at DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Kết quả tìm kiếm</h1>
        <p class="text-gray-600 mt-1">
            <?php if ($query): ?>
                Tìm thấy <?php echo $total; ?> kết quả cho "<?php echo htmlspecialchars($query); ?>"
            <?php else: ?>
                Nhập từ khóa để tìm sản phẩm phù hợp
            <?php endif; ?>
        </p>
    </div>

    <!-- Form tìm kiếm -->
    <form method="GET" action="search.php" class="mb-8">
        <div class="flex">
            <input type="text" name="q" placeholder="Nhập từ khóa (ví dụ: toner, serum, bưởi)" value="<?php echo htmlspecialchars($query); ?>"
                   class="flex-1 px-4 py-3 border border-gray-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
            <button type="submit" class="px-6 py-3 bg-primary text-white rounded-r-md hover:bg-green-600">
                <i class="fas fa-search"></i>
            </button>
        </div>
    </form>

    <?php if ($total === 0): ?>
        <div class="text-center py-16">
            <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Không tìm thấy sản phẩm</h3>
            <p class="text-gray-600 mb-6">Thử từ khóa khác hoặc xem các sản phẩm nổi bật</p>
            <a href="products.php" class="bg-primary text-white px-6 py-3 rounded-lg hover:bg-green-600 transition-colors">
                Xem tất cả sản phẩm
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($products as $product): ?>
            <div class="bg-white rounded-xl shadow-md overflow-hidden card-hover">
                <div class="relative">
                    <?php 
                    $images = $product['images'] ? json_decode($product['images'], true) : [];
                    $main_image = !empty($images) ? $images[0] : 'https://via.placeholder.com/300x300?text=No+Image';
                    ?>
                    <img src="<?php echo $main_image; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-full h-48 object-cover">
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
                            <?php if (!empty($product['sale_price'])): ?>
                                <span class="text-lg font-bold text-primary"><?php echo formatPrice($product['sale_price']); ?></span>
                                <span class="text-sm text-gray-500 line-through"><?php echo formatPrice($product['price']); ?></span>
                            <?php else: ?>
                                <span class="text-lg font-bold text-primary"><?php echo formatPrice($product['price']); ?></span>
                            <?php endif; ?>
                        </div>
                        <a href="product.php?id=<?php echo $product['id']; ?>" class="bg-primary text-white px-3 py-1 rounded-lg text-sm hover:bg-green-600 transition-colors">
                            Xem chi tiết
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Phân trang -->
        <?php if ($total_pages > 1): ?>
        <nav class="flex justify-center mt-8">
            <ul class="flex space-x-2">
                <?php if ($page > 1): ?>
                <li>
                    <a href="?q=<?php echo urlencode($query); ?>&page=<?php echo $page - 1; ?>" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <li>
                    <a href="?q=<?php echo urlencode($query); ?>&page=<?php echo $i; ?>" class="px-3 py-2 text-sm font-medium <?php echo $i == $page ? 'text-white bg-primary border-primary' : 'text-gray-500 bg-white border-gray-300 hover:bg-gray-50'; ?> border rounded-md">
                        <?php echo $i; ?>
                    </a>
                </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                <li>
                    <a href="?q=<?php echo urlencode($query); ?>&page=<?php echo $page + 1; ?>" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>

<?php include 'includes/footer.php'; ?>


