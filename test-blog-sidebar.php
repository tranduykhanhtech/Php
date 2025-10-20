<?php
require_once 'config/database.php';

// Lấy bài viết nổi bật
$featured_posts = $pdo->query("
    SELECT p.*, u.full_name as author_name 
    FROM posts p 
    LEFT JOIN users u ON p.author_id = u.id 
    WHERE p.is_published = 1 
    ORDER BY p.view_count DESC, p.created_at DESC 
    LIMIT 10
")->fetchAll();

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Blog Sidebar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-md mx-auto">
        <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4">
            <p><strong>Debug Info:</strong></p>
            <p>Total featured posts: <?php echo count($featured_posts); ?></p>
            <p>Should show button: <?php echo count($featured_posts) > 3 ? 'YES' : 'NO'; ?></p>
        </div>

        <!-- Featured Posts -->
        <?php if (!empty($featured_posts)): ?>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Bài viết nổi bật</h3>
            <div id="featuredPostsList" class="space-y-4">
                <?php foreach ($featured_posts as $index => $post): ?>
                <div class="flex space-x-3 featured-post-item <?php echo $index >= 3 ? 'hidden' : ''; ?> border-b pb-3">
                    <?php if ($post['featured_image']): ?>
                    <img src="/<?php echo $post['featured_image']; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" 
                         class="w-16 h-16 object-cover rounded flex-shrink-0">
                    <?php endif; ?>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-medium text-gray-900 line-clamp-2">
                            <a href="post.php?id=<?php echo $post['id']; ?>" class="hover:text-green-600">
                                <?php echo htmlspecialchars($post['title']); ?>
                            </a>
                        </h4>
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="fas fa-eye mr-1"></i><?php echo $post['view_count']; ?> lượt xem
                        </p>
                        <span class="text-xs bg-gray-200 px-2 py-1 rounded">Index: <?php echo $index; ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (count($featured_posts) > 3): ?>
            <button id="toggleFeaturedPosts" onclick="toggleFeaturedPosts()" 
                    class="mt-4 w-full text-center text-sm text-green-600 hover:text-green-700 font-medium transition-colors bg-green-50 hover:bg-green-100 py-2 rounded">
                <span id="toggleText">Xem thêm (<?php echo count($featured_posts) - 3; ?> bài)</span>
                <i id="toggleIcon" class="fas fa-chevron-down ml-1"></i>
            </button>
            <?php else: ?>
            <div class="mt-4 text-center text-sm text-red-600">
                Không đủ bài để hiển thị nút (cần > 3 bài)
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
    function toggleFeaturedPosts() {
        const items = document.querySelectorAll('.featured-post-item');
        const toggleText = document.getElementById('toggleText');
        const toggleIcon = document.getElementById('toggleIcon');
        const totalHidden = items.length - 3;
        const isExpanded = toggleText.textContent.includes('Thu gọn');
        
        console.log('Toggle clicked!');
        console.log('Total items:', items.length);
        console.log('Is expanded:', isExpanded);
        
        items.forEach((item, index) => {
            if (index >= 3) {
                if (isExpanded) {
                    item.classList.add('hidden');
                } else {
                    item.classList.remove('hidden');
                }
            }
        });
        
        if (isExpanded) {
            toggleText.textContent = 'Xem thêm (' + totalHidden + ' bài)';
            toggleIcon.className = 'fas fa-chevron-down ml-1';
        } else {
            toggleText.textContent = 'Thu gọn';
            toggleIcon.className = 'fas fa-chevron-up ml-1';
        }
    }
    </script>
</body>
</html>
