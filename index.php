<?php
require_once 'config/database.php';

$page_title = 'Trang chủ';
$page_description = 'Cửa hàng mỹ phẩm thiên nhiên chất lượng cao, an toàn cho sức khỏe và thân thiện với môi trường';

// Lấy sản phẩm nổi bật
$featured_products = $pdo->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.is_featured = 1 AND p.is_active = 1 
    ORDER BY p.created_at DESC 
    LIMIT 8
")->fetchAll();

// Lấy sản phẩm bán chạy
$bestseller_products = $pdo->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.is_bestseller = 1 AND p.is_active = 1 
    ORDER BY p.created_at DESC 
    LIMIT 8
")->fetchAll();

// Lấy danh mục
$categories = $pdo->query("
    SELECT * FROM categories 
    WHERE is_active = 1 AND parent_id IS NULL 
    ORDER BY sort_order ASC 
    LIMIT 6
")->fetchAll();

// Lấy bài viết mới nhất
$recent_posts = $pdo->query("
    SELECT p.*, u.full_name as author_name 
    FROM posts p 
    LEFT JOIN users u ON p.author_id = u.id 
    WHERE p.is_published = 1 
    ORDER BY p.created_at DESC 
    LIMIT 3
")->fetchAll();

include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-gradient text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div>
                <h1 class="text-4xl md:text-6xl font-bold mb-6">
                    Mỹ phẩm thiên nhiên
                    <span class="block text-yellow-300">chất lượng cao</span>
                </h1>
                <p class="text-xl mb-8 text-green-100">
                    Khám phá bộ sưu tập mỹ phẩm thiên nhiên được chọn lọc kỹ lưỡng, 
                    an toàn cho da và thân thiện với môi trường.
                </p>
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="products.php" class="bg-white text-primary px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors text-center">
                        <i class="fas fa-shopping-bag mr-2"></i>Mua sắm ngay
                    </a>
                    <a href="about.php" class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-primary transition-colors text-center">
                        <i class="fas fa-info-circle mr-2"></i>Tìm hiểu thêm
                    </a>
                </div>
            </div>
            <div class="relative">
                <div class="bg-white bg-opacity-20 rounded-2xl p-8 backdrop-blur-sm">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-white bg-opacity-30 rounded-lg p-4 text-center">
                            <i class="fas fa-leaf text-4xl text-yellow-300 mb-2"></i>
                            <h3 class="font-semibold">100% Thiên nhiên</h3>
                        </div>
                        <div class="bg-white bg-opacity-30 rounded-lg p-4 text-center">
                            <i class="fas fa-shield-alt text-4xl text-yellow-300 mb-2"></i>
                            <h3 class="font-semibold">An toàn tuyệt đối</h3>
                        </div>
                        <div class="bg-white bg-opacity-30 rounded-lg p-4 text-center">
                            <i class="fas fa-heart text-4xl text-yellow-300 mb-2"></i>
                            <h3 class="font-semibold">Tốt cho da</h3>
                        </div>
                        <div class="bg-white bg-opacity-30 rounded-lg p-4 text-center">
                            <i class="fas fa-recycle text-4xl text-yellow-300 mb-2"></i>
                            <h3 class="font-semibold">Thân thiện môi trường</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Danh mục sản phẩm</h2>
            <p class="text-lg text-gray-600">Khám phá các dòng sản phẩm mỹ phẩm thiên nhiên đa dạng</p>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
            <?php foreach ($categories as $category): ?>
            <a href="products.php?category=<?php echo $category['id']; ?>" 
               class="group bg-gray-50 rounded-xl p-6 text-center hover:bg-primary hover:text-white transition-all duration-300 card-hover">
                <div class="w-16 h-16 mx-auto mb-4 bg-primary bg-opacity-10 group-hover:bg-white group-hover:bg-opacity-20 rounded-full flex items-center justify-center">
                    <i class="fas fa-spa text-2xl text-primary group-hover:text-white"></i>
                </div>
                <h3 class="font-semibold text-sm"><?php echo htmlspecialchars($category['name']); ?></h3>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<section class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Sản phẩm nổi bật</h2>
            <p class="text-lg text-gray-600">Những sản phẩm được yêu thích nhất</p>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($featured_products as $product): ?>
            <div class="bg-white rounded-xl shadow-md overflow-hidden card-hover">
                <div class="relative">
                    <?php 
                    $images = json_decode($product['images'], true);
                    $main_image = !empty($images) ? $images[0] : 'https://via.placeholder.com/300x300?text=No+Image';
                    ?>
                    <img src="<?php echo $main_image; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" 
                         class="w-full h-48 object-cover">
                    <?php if ($product['sale_price']): ?>
                        <div class="absolute top-2 left-2 bg-accent text-white px-2 py-1 rounded-full text-xs font-semibold">
                            Sale
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
        
        <div class="text-center mt-8">
            <a href="products.php" class="bg-primary text-white px-8 py-3 rounded-lg font-semibold hover:bg-green-600 transition-colors">
                Xem tất cả sản phẩm
            </a>
        </div>
    </div>
</section>

<!-- Bestseller Products Section -->
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Sản phẩm bán chạy</h2>
            <p class="text-lg text-gray-600">Những sản phẩm được khách hàng tin tưởng nhất</p>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($bestseller_products as $product): ?>
            <div class="bg-white rounded-xl shadow-md overflow-hidden card-hover">
                <div class="relative">
                    <?php 
                    $images = json_decode($product['images'], true);
                    $main_image = !empty($images) ? $images[0] : 'https://via.placeholder.com/300x300?text=No+Image';
                    ?>
                    <img src="<?php echo $main_image; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" 
                         class="w-full h-48 object-cover">
                    <div class="absolute top-2 right-2 bg-yellow-500 text-white px-2 py-1 rounded-full text-xs font-semibold">
                        <i class="fas fa-fire mr-1"></i>Bán chạy
                    </div>
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
    </div>
</section>

<!-- Why Choose Us Section -->
<section class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Tại sao chọn chúng tôi?</h2>
            <p class="text-lg text-gray-600">Cam kết mang đến những sản phẩm tốt nhất cho bạn</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <div class="text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-primary bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-award text-2xl text-primary"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">Chất lượng cao</h3>
                <p class="text-sm text-gray-600">Sản phẩm được kiểm định chất lượng nghiêm ngặt</p>
            </div>
            
            <div class="text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-primary bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-shipping-fast text-2xl text-primary"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">Giao hàng nhanh</h3>
                <p class="text-sm text-gray-600">Giao hàng toàn quốc trong 24-48 giờ</p>
            </div>
            
            <div class="text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-primary bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-undo text-2xl text-primary"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">Đổi trả dễ dàng</h3>
                <p class="text-sm text-gray-600">Chính sách đổi trả linh hoạt trong 30 ngày</p>
            </div>
            
            <div class="text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-primary bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-headset text-2xl text-primary"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">Hỗ trợ 24/7</h3>
                <p class="text-sm text-gray-600">Đội ngũ tư vấn chuyên nghiệp luôn sẵn sàng</p>
            </div>
        </div>
    </div>
</section>

<!-- Blog Section -->
<?php if (!empty($recent_posts)): ?>
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Bài viết mới nhất</h2>
            <p class="text-lg text-gray-600">Cập nhật những kiến thức về làm đẹp tự nhiên</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php foreach ($recent_posts as $post): ?>
            <article class="bg-white rounded-xl shadow-md overflow-hidden card-hover">
                <?php if ($post['featured_image']): ?>
                <img src="<?php echo $post['featured_image']; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" 
                     class="w-full h-48 object-cover">
                <?php endif; ?>
                <div class="p-6">
                    <div class="text-sm text-gray-500 mb-2">
                        <i class="fas fa-calendar mr-1"></i>
                        <?php echo date('d/m/Y', strtotime($post['created_at'])); ?>
                        <span class="mx-2">•</span>
                        <i class="fas fa-user mr-1"></i>
                        <?php echo htmlspecialchars($post['author_name']); ?>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2"><?php echo htmlspecialchars($post['title']); ?></h3>
                    <p class="text-sm text-gray-600 mb-4 line-clamp-3"><?php echo htmlspecialchars($post['excerpt']); ?></p>
                    <a href="post.php?id=<?php echo $post['id']; ?>" 
                       class="text-primary hover:text-green-600 font-medium text-sm">
                        Đọc thêm <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-8">
            <a href="blog.php" class="bg-primary text-white px-8 py-3 rounded-lg font-semibold hover:bg-green-600 transition-colors">
                Xem tất cả bài viết
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Newsletter Section -->
<section class="py-16 bg-primary">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold text-white mb-4">Đăng ký nhận tin</h2>
        <p class="text-xl text-green-100 mb-8">Nhận thông tin về sản phẩm mới và ưu đãi đặc biệt</p>
        
        <form class="max-w-md mx-auto flex">
            <input type="email" placeholder="Nhập email của bạn" 
                   class="flex-1 px-4 py-3 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-white">
            <button type="submit" 
                    class="bg-white text-primary px-6 py-3 rounded-r-lg font-semibold hover:bg-gray-100 transition-colors">
                Đăng ký
            </button>
        </form>
    </div>
</section>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<?php include 'includes/footer.php'; ?>
