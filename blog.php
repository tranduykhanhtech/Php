<?php
require_once 'config/database.php';

$page_title = 'Blog';
$page_description = 'Cập nhật những kiến thức về làm đẹp tự nhiên và chăm sóc da';

// Lấy tham số
$category = isset($_GET['category']) ? sanitize($_GET['category']) : '';
$search = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 9;
$offset = ($page - 1) * $limit;

// Xây dựng query
$where_conditions = ["p.is_published = 1"];
$params = [];

if (!empty($category)) {
    $where_conditions[] = "p.category = ?";
    $params[] = $category;
}

if (!empty($search)) {
    $where_conditions[] = "(p.title LIKE ? OR p.content LIKE ? OR p.excerpt LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$where_clause = implode(' AND ', $where_conditions);

// Lấy bài viết
$sql = "
    SELECT p.*, u.full_name as author_name 
    FROM posts p 
    LEFT JOIN users u ON p.author_id = u.id 
    WHERE $where_clause 
    ORDER BY p.created_at DESC 
    LIMIT $limit OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

// Đếm tổng số bài viết
$count_sql = "SELECT COUNT(*) as total FROM posts p WHERE $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_posts = $count_stmt->fetch()['total'];
$total_pages = ceil($total_posts / $limit);

// Lấy danh mục bài viết
$categories = $pdo->query("
    SELECT category, COUNT(*) as count 
    FROM posts 
    WHERE is_published = 1 AND category IS NOT NULL AND category != '' 
    GROUP BY category 
    ORDER BY count DESC
")->fetchAll();

// Lấy bài viết nổi bật (chỉ 1 bài có lượt xem cao nhất)
$featured_post = $pdo->query("
    SELECT p.*, u.full_name as author_name 
    FROM posts p 
    LEFT JOIN users u ON p.author_id = u.id 
    WHERE p.is_published = 1 
    ORDER BY p.view_count DESC, p.created_at DESC 
    LIMIT 1
")->fetch();

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
                    <span class="text-gray-500">Blog</span>
                </div>
            </li>
        </ol>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Sidebar -->
        <div class="lg:col-span-1">
            <div class="space-y-6">
                <!-- Search -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Tìm kiếm</h3>
                    <form method="GET" action="blog.php">
                        <div class="flex">
                            <input type="text" name="q" placeholder="Tìm bài viết..." 
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-r-md hover:bg-green-600">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <?php if ($category): ?>
                            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Categories -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Danh mục</h3>
                    <ul class="space-y-2">
                        <li>
                            <a href="blog.php" 
                               class="block px-3 py-2 rounded-md text-sm <?php echo !$category ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                                Tất cả bài viết
                            </a>
                        </li>
                        <?php foreach ($categories as $cat): ?>
                        <li>
                            <a href="blog.php?category=<?php echo urlencode($cat['category']); ?>" 
                               class="block px-3 py-2 rounded-md text-sm <?php echo $category == $cat['category'] ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                                <?php echo htmlspecialchars($cat['category']); ?>
                                <span class="text-xs text-gray-500 ml-1">(<?php echo $cat['count']; ?>)</span>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Featured Post -->
                <?php if ($featured_post): ?>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-star text-yellow-400 mr-2"></i>
                        Bài viết nổi bật
                    </h3>
                    <div class="flex space-x-3">
                        <!-- Image -->
                        <div class="w-20 h-20 flex-shrink-0">
                            <?php if ($featured_post['featured_image']): ?>
                            <img src="/<?php echo $featured_post['featured_image']; ?>" alt="<?php echo htmlspecialchars($featured_post['title']); ?>" 
                                 class="w-full h-full object-cover rounded-lg">
                            <?php else: ?>
                            <div class="w-full h-full bg-gradient-to-br from-primary to-green-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-image text-white text-2xl"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        <!-- Content -->
                        <div class="flex-1 min-w-0 overflow-hidden">
                            <h4 class="text-base font-semibold text-gray-900 line-clamp-2 mb-2 break-words">
                                <a href="post.php?id=<?php echo $featured_post['id']; ?>" class="hover:text-primary transition-colors">
                                    <?php echo htmlspecialchars($featured_post['title']); ?>
                                </a>
                            </h4>
                            <p class="text-xs text-gray-500 mb-2">
                                <i class="fas fa-user mr-1"></i><?php echo htmlspecialchars($featured_post['author_name']); ?>
                                <span class="mx-2">•</span>
                                <i class="fas fa-eye mr-1"></i><?php echo $featured_post['view_count']; ?> lượt xem
                            </p>
                            <?php if ($featured_post['excerpt']): ?>
                            <p class="text-xs text-gray-600 line-clamp-2">
                                <?php echo htmlspecialchars($featured_post['excerpt']); ?>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Posts -->
        <div class="lg:col-span-3">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-4">
                    <?php if ($category): ?>
                        <?php echo htmlspecialchars($category); ?>
                    <?php elseif ($search): ?>
                        Kết quả tìm kiếm cho "<?php echo htmlspecialchars($search); ?>"
                    <?php else: ?>
                        Blog làm đẹp tự nhiên
                    <?php endif; ?>
                </h1>
                <p class="text-lg text-gray-600">
                    <?php if ($category): ?>
                        Khám phá những bài viết về <?php echo htmlspecialchars($category); ?>
                    <?php elseif ($search): ?>
                        Tìm thấy <?php echo $total_posts; ?> bài viết
                    <?php else: ?>
                        Cập nhật những kiến thức về làm đẹp tự nhiên và chăm sóc da
                    <?php endif; ?>
                </p>
            </div>

            <!-- Posts Grid -->
            <?php if (empty($posts)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Không tìm thấy bài viết</h3>
                    <p class="text-gray-600 mb-6">Hãy thử tìm kiếm với từ khóa khác hoặc duyệt các danh mục khác</p>
                    <a href="blog.php" class="bg-primary text-white px-6 py-3 rounded-lg hover:bg-green-600 transition-colors">
                        Xem tất cả bài viết
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    <?php foreach ($posts as $post): ?>
                    <article class="bg-white rounded-xl shadow-md overflow-hidden card-hover">
                        <?php if ($post['featured_image']): ?>
                        <div class="aspect-w-16 aspect-h-9">
                            <img src="/<?php echo $post['featured_image']; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" 
                                 class="w-full h-48 object-cover">
                        </div>
                        <?php endif; ?>
                        
                        <div class="p-6">
                            <div class="flex items-center text-sm text-gray-500 mb-3">
                                <i class="fas fa-calendar mr-1"></i>
                                <span><?php echo date('d/m/Y', strtotime($post['created_at'])); ?></span>
                                <span class="mx-2">•</span>
                                <i class="fas fa-user mr-1"></i>
                                <span><?php echo htmlspecialchars($post['author_name']); ?></span>
                                <span class="mx-2">•</span>
                                <i class="fas fa-eye mr-1"></i>
                                <span><?php echo $post['view_count']; ?></span>
                            </div>
                            
                            <?php if ($post['category']): ?>
                            <div class="mb-3">
                                <span class="inline-block bg-primary bg-opacity-10 text-primary px-2 py-1 rounded-full text-xs font-medium">
                                    <?php echo htmlspecialchars($post['category']); ?>
                                </span>
                            </div>
                            <?php endif; ?>
                            
                            <h2 class="text-xl font-semibold text-gray-900 mb-3 line-clamp-2">
                                <a href="post.php?id=<?php echo $post['id']; ?>" class="hover:text-primary">
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </a>
                            </h2>
                            
                            <p class="text-gray-600 mb-4 line-clamp-3">
                                <?php echo htmlspecialchars($post['excerpt']); ?>
                            </p>
                            
                            <a href="post.php?id=<?php echo $post['id']; ?>" 
                               class="text-primary hover:text-green-600 font-medium text-sm">
                                Đọc thêm <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav class="flex justify-center">
                    <ul class="flex space-x-2">
                        <?php if ($page > 1): ?>
                            <li>
                                <a href="?page=<?php echo $page - 1; ?>&category=<?php echo urlencode($category); ?>&q=<?php echo urlencode($search); ?>" 
                                   class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li>
                                <a href="?page=<?php echo $i; ?>&category=<?php echo urlencode($category); ?>&q=<?php echo urlencode($search); ?>" 
                                   class="px-3 py-2 text-sm font-medium <?php echo $i == $page ? 'text-white bg-primary border-primary' : 'text-gray-500 bg-white border-gray-300 hover:bg-gray-50'; ?> border rounded-md">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li>
                                <a href="?page=<?php echo $page + 1; ?>&category=<?php echo urlencode($category); ?>&q=<?php echo urlencode($search); ?>" 
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
    word-break: break-word;
    overflow-wrap: break-word;
    max-height: 2.8em;
    line-height: 1.4em;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    word-break: break-word;
    overflow-wrap: break-word;
}
</style>

<?php include 'includes/footer.php'; ?>
