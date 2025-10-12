<?php
require_once 'config/database.php';

$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$post_id) {
    redirect('blog.php');
}

// Lấy thông tin bài viết
$stmt = $pdo->prepare("
    SELECT p.*, u.full_name as author_name 
    FROM posts p 
    LEFT JOIN users u ON p.author_id = u.id 
    WHERE p.id = ? AND p.is_published = 1
");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post) {
    redirect('blog.php');
}

// Tăng lượt xem
$pdo->prepare("UPDATE posts SET view_count = view_count + 1 WHERE id = ?")->execute([$post_id]);

$page_title = $post['title'];
$page_description = $post['excerpt'];

// Lấy bài viết liên quan
$related_posts = $pdo->prepare("
    SELECT p.*, u.full_name as author_name 
    FROM posts p 
    LEFT JOIN users u ON p.author_id = u.id 
    WHERE p.category = ? AND p.id != ? AND p.is_published = 1 
    ORDER BY p.created_at DESC 
    LIMIT 3
");
$related_posts->execute([$post['category'], $post_id]);
$related_posts = $related_posts->fetchAll();

include 'includes/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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
                    <a href="blog.php" class="text-gray-700 hover:text-primary">Blog</a>
                </div>
            </li>
            <?php if ($post['category']): ?>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <a href="blog.php?category=<?php echo urlencode($post['category']); ?>" class="text-gray-700 hover:text-primary">
                        <?php echo htmlspecialchars($post['category']); ?>
                    </a>
                </div>
            </li>
            <?php endif; ?>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-500"><?php echo htmlspecialchars($post['title']); ?></span>
                </div>
            </li>
        </ol>
    </nav>

    <article class="bg-white rounded-lg shadow-md overflow-hidden">
        <!-- Featured Image -->
        <?php if ($post['featured_image']): ?>
        <div class="aspect-w-16 aspect-h-9">
            <img src="<?php echo $post['featured_image']; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" 
                 class="w-full h-64 md:h-96 object-cover">
        </div>
        <?php endif; ?>

        <!-- Article Header -->
        <div class="px-6 py-8">
            <div class="flex items-center text-sm text-gray-500 mb-4">
                <i class="fas fa-calendar mr-1"></i>
                <span><?php echo date('d/m/Y', strtotime($post['created_at'])); ?></span>
                <span class="mx-2">•</span>
                <i class="fas fa-user mr-1"></i>
                <span><?php echo htmlspecialchars($post['author_name']); ?></span>
                <span class="mx-2">•</span>
                <i class="fas fa-eye mr-1"></i>
                <span><?php echo $post['view_count']; ?> lượt xem</span>
            </div>

            <?php if ($post['category']): ?>
            <div class="mb-4">
                <span class="inline-block bg-primary bg-opacity-10 text-primary px-3 py-1 rounded-full text-sm font-medium">
                    <?php echo htmlspecialchars($post['category']); ?>
                </span>
            </div>
            <?php endif; ?>

            <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                <?php echo htmlspecialchars($post['title']); ?>
            </h1>

            <?php if ($post['excerpt']): ?>
            <p class="text-xl text-gray-600 leading-relaxed">
                <?php echo htmlspecialchars($post['excerpt']); ?>
            </p>
            <?php endif; ?>
        </div>

        <!-- Article Content -->
        <div class="px-6 pb-8">
            <div class="prose prose-lg max-w-none">
                <?php echo nl2br(htmlspecialchars($post['content'])); ?>
            </div>

            <!-- Tags -->
            <?php if ($post['tags']): ?>
            <div class="mt-8 pt-6 border-t border-gray-200">
                <h3 class="text-sm font-medium text-gray-900 mb-3">Tags:</h3>
                <div class="flex flex-wrap gap-2">
                    <?php 
                    $tags = json_decode($post['tags'], true);
                    if ($tags):
                        foreach ($tags as $tag):
                    ?>
                    <span class="inline-block bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm">
                        #<?php echo htmlspecialchars($tag); ?>
                    </span>
                    <?php 
                        endforeach;
                    endif;
                    ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Share Buttons -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <h3 class="text-sm font-medium text-gray-900 mb-3">Chia sẻ bài viết:</h3>
                <div class="flex space-x-4">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(SITE_URL . '/post.php?id=' . $post_id); ?>" 
                       target="_blank" class="text-blue-600 hover:text-blue-800">
                        <i class="fab fa-facebook-f text-xl"></i>
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(SITE_URL . '/post.php?id=' . $post_id); ?>&text=<?php echo urlencode($post['title']); ?>" 
                       target="_blank" class="text-blue-400 hover:text-blue-600">
                        <i class="fab fa-twitter text-xl"></i>
                    </a>
                    <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode(SITE_URL . '/post.php?id=' . $post_id); ?>" 
                       target="_blank" class="text-blue-700 hover:text-blue-900">
                        <i class="fab fa-linkedin-in text-xl"></i>
                    </a>
                    <a href="https://pinterest.com/pin/create/button/?url=<?php echo urlencode(SITE_URL . '/post.php?id=' . $post_id); ?>&media=<?php echo urlencode($post['featured_image']); ?>&description=<?php echo urlencode($post['title']); ?>" 
                       target="_blank" class="text-red-600 hover:text-red-800">
                        <i class="fab fa-pinterest-p text-xl"></i>
                    </a>
                </div>
            </div>
        </div>
    </article>

    <!-- Related Posts -->
    <?php if (!empty($related_posts)): ?>
    <div class="mt-12">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Bài viết liên quan</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php foreach ($related_posts as $related): ?>
            <article class="bg-white rounded-xl shadow-md overflow-hidden card-hover">
                <?php if ($related['featured_image']): ?>
                <div class="aspect-w-16 aspect-h-9">
                    <img src="<?php echo $related['featured_image']; ?>" alt="<?php echo htmlspecialchars($related['title']); ?>" 
                         class="w-full h-48 object-cover">
                </div>
                <?php endif; ?>
                
                <div class="p-6">
                    <div class="flex items-center text-sm text-gray-500 mb-3">
                        <i class="fas fa-calendar mr-1"></i>
                        <span><?php echo date('d/m/Y', strtotime($related['created_at'])); ?></span>
                        <span class="mx-2">•</span>
                        <i class="fas fa-eye mr-1"></i>
                        <span><?php echo $related['view_count']; ?></span>
                    </div>
                    
                    <h3 class="text-lg font-semibold text-gray-900 mb-3 line-clamp-2">
                        <a href="post.php?id=<?php echo $related['id']; ?>" class="hover:text-primary">
                            <?php echo htmlspecialchars($related['title']); ?>
                        </a>
                    </h3>
                    
                    <p class="text-gray-600 mb-4 line-clamp-3">
                        <?php echo htmlspecialchars($related['excerpt']); ?>
                    </p>
                    
                    <a href="post.php?id=<?php echo $related['id']; ?>" 
                       class="text-primary hover:text-green-600 font-medium text-sm">
                        Đọc thêm <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Navigation -->
    <div class="mt-12 flex justify-between">
        <a href="blog.php" class="inline-flex items-center text-primary hover:text-green-600 font-medium">
            <i class="fas fa-arrow-left mr-2"></i>Quay lại blog
        </a>
        
        <div class="flex space-x-4">
            <a href="products.php" class="inline-flex items-center text-primary hover:text-green-600 font-medium">
                <i class="fas fa-shopping-bag mr-2"></i>Mua sắm
            </a>
            <a href="index.php" class="inline-flex items-center text-primary hover:text-green-600 font-medium">
                <i class="fas fa-home mr-2"></i>Trang chủ
            </a>
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

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.prose {
    color: #374151;
    line-height: 1.75;
}

.prose h1, .prose h2, .prose h3, .prose h4, .prose h5, .prose h6 {
    color: #111827;
    font-weight: 600;
    margin-top: 2rem;
    margin-bottom: 1rem;
}

.prose p {
    margin-bottom: 1.5rem;
}

.prose ul, .prose ol {
    margin-bottom: 1.5rem;
    padding-left: 1.5rem;
}

.prose li {
    margin-bottom: 0.5rem;
}

.prose blockquote {
    border-left: 4px solid #10B981;
    padding-left: 1rem;
    margin: 1.5rem 0;
    font-style: italic;
    color: #6B7280;
}

.prose img {
    border-radius: 0.5rem;
    margin: 1.5rem 0;
}
</style>

<?php include 'includes/footer.php'; ?>
