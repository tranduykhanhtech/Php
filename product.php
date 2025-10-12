<?php
require_once 'config/database.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$product_id) {
    redirect('products.php');
}

// Lấy thông tin sản phẩm
$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ? AND p.is_active = 1
");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    redirect('products.php');
}

$page_title = $product['name'];
$page_description = $product['short_description'];

// Lấy sản phẩm liên quan
$related_products = $pdo->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.category_id = ? AND p.id != ? AND p.is_active = 1 
    ORDER BY p.created_at DESC 
    LIMIT 4
");
$related_products->execute([$product['category_id'], $product_id]);
$related_products = $related_products->fetchAll();
// Lấy sản phẩm liên quan (chỉ chọn các trường cần thiết để giảm payload)
$related_products = $pdo->prepare(
     "SELECT p.id, p.name, p.price, p.sale_price, p.images, c.name as category_name
      FROM products p
      LEFT JOIN categories c ON p.category_id = c.id
      WHERE p.category_id = ? AND p.id != ? AND p.is_active = 1
      ORDER BY p.created_at DESC
      LIMIT 4"
);
$related_products->execute([$product['category_id'], $product_id]);
$related_products = $related_products->fetchAll();

// Xử lý thêm vào giỏ hàng
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    if (!isLoggedIn()) {
        $_SESSION['error'] = 'Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng';
        redirect('login.php');
    }
    
    $quantity = (int)$_POST['quantity'];
    if ($quantity > 0 && $quantity <= $product['stock_quantity']) {
        // Kiểm tra sản phẩm đã có trong giỏ hàng chưa
        $stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$_SESSION['user_id'], $product_id]);
        $existing_item = $stmt->fetch();
        
        if ($existing_item) {
            // Cập nhật số lượng
            $new_quantity = $existing_item['quantity'] + $quantity;
            if ($new_quantity <= $product['stock_quantity']) {
                $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                $stmt->execute([$new_quantity, $existing_item['id']]);
                $_SESSION['success'] = 'Đã cập nhật sản phẩm trong giỏ hàng';
            } else {
                $_SESSION['error'] = 'Số lượng vượt quá tồn kho';
            }
        } else {
            // Thêm mới vào giỏ hàng
            $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $product_id, $quantity]);
            $_SESSION['success'] = 'Đã thêm sản phẩm vào giỏ hàng';
        }
    } else {
        $_SESSION['error'] = 'Số lượng không hợp lệ hoặc vượt quá tồn kho';
    }
}

$images = json_decode($product['images'], true) ?: [];
$main_image = !empty($images) ? $images[0] : 'https://via.placeholder.com/600x600?text=No+Image';

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
                    <a href="products.php" class="text-gray-700 hover:text-primary">Sản phẩm</a>
                </div>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <a href="products.php?category=<?php echo $product['category_id']; ?>" class="text-gray-700 hover:text-primary">
                        <?php echo htmlspecialchars($product['category_name']); ?>
                    </a>
                </div>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-500"><?php echo htmlspecialchars($product['name']); ?></span>
                </div>
            </li>
        </ol>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        <!-- Product Images -->
        <div>
            <div class="aspect-square bg-gray-100 rounded-xl overflow-hidden mb-4">
             <img src="<?php echo $main_image; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" 
                 class="w-full h-full object-cover" id="main-image" loading="eager">
            </div>
            
            <?php if (count($images) > 1): ?>
            <div class="grid grid-cols-4 gap-2">
                <?php foreach ($images as $index => $image): ?>
                <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden cursor-pointer border-2 <?php echo $index == 0 ? 'border-primary' : 'border-transparent'; ?>" 
                     onclick="changeMainImage('<?php echo $image; ?>', this)">
                <img src="<?php echo $image; ?>" alt="Hình ảnh <?php echo $index + 1; ?>" 
                    class="w-full h-full object-cover" loading="lazy">
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Product Info -->
        <div>
            <div class="mb-4">
                <span class="inline-block bg-primary bg-opacity-10 text-primary px-3 py-1 rounded-full text-sm font-medium">
                    <?php echo htmlspecialchars($product['category_name']); ?>
                </span>
            </div>
            
            <h1 class="text-3xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars($product['name']); ?></h1>
            
            <div class="flex items-center space-x-4 mb-6">
                <?php if ($product['sale_price']): ?>
                    <span class="text-3xl font-bold text-primary"><?php echo formatPrice($product['sale_price']); ?></span>
                    <span class="text-xl text-gray-500 line-through"><?php echo formatPrice($product['price']); ?></span>
                    <span class="bg-accent text-white px-2 py-1 rounded-full text-sm font-semibold">
                        -<?php echo round((($product['price'] - $product['sale_price']) / $product['price']) * 100); ?>%
                    </span>
                <?php else: ?>
                    <span class="text-3xl font-bold text-primary"><?php echo formatPrice($product['price']); ?></span>
                <?php endif; ?>
            </div>

            <div class="mb-6">
                <h3 class="font-semibold text-gray-900 mb-2">Mô tả sản phẩm</h3>
                <p class="text-gray-600 leading-relaxed"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            </div>

            <?php if ($product['ingredients']): ?>
            <div class="mb-6">
                <h3 class="font-semibold text-gray-900 mb-2">Thành phần</h3>
                <p class="text-gray-600"><?php echo nl2br(htmlspecialchars($product['ingredients'])); ?></p>
            </div>
            <?php endif; ?>

            <?php if ($product['usage_instructions']): ?>
            <div class="mb-6">
                <h3 class="font-semibold text-gray-900 mb-2">Hướng dẫn sử dụng</h3>
                <p class="text-gray-600"><?php echo nl2br(htmlspecialchars($product['usage_instructions'])); ?></p>
            </div>
            <?php endif; ?>

            <!-- Product Details -->
            <div class="grid grid-cols-2 gap-4 mb-6">
                <?php if ($product['weight']): ?>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <div class="text-sm text-gray-500">Trọng lượng</div>
                    <div class="font-semibold"><?php echo htmlspecialchars($product['weight']); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if ($product['origin']): ?>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <div class="text-sm text-gray-500">Xuất xứ</div>
                    <div class="font-semibold"><?php echo htmlspecialchars($product['origin']); ?></div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Add to Cart -->
            <form method="POST" class="mb-8">
                <div class="flex items-center space-x-4 mb-4">
                    <label for="quantity" class="font-semibold text-gray-900">Số lượng:</label>
                    <div class="flex items-center border border-gray-300 rounded-lg">
                        <button type="button" onclick="decreaseQuantity()" 
                                class="px-3 py-2 text-gray-600 hover:text-gray-800">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" 
                               class="w-16 text-center border-0 focus:outline-none focus:ring-0">
                        <button type="button" onclick="increaseQuantity()" 
                                class="px-3 py-2 text-gray-600 hover:text-gray-800">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <span class="text-sm text-gray-500">
                        Còn lại: <?php echo $product['stock_quantity']; ?> sản phẩm
                    </span>
                </div>

                <div class="flex space-x-4">
                    <button type="submit" name="add_to_cart" 
                            class="flex-1 bg-primary text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-600 transition-colors">
                        <i class="fas fa-shopping-cart mr-2"></i>Thêm vào giỏ hàng
                    </button>
                    <button type="button" onclick="buyNow()" 
                            class="flex-1 bg-accent text-white px-6 py-3 rounded-lg font-semibold hover:bg-pink-600 transition-colors">
                        <i class="fas fa-bolt mr-2"></i>Mua ngay
                    </button>
                </div>
            </form>

            <!-- Product Features -->
            <div class="border-t pt-6">
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex items-center">
                        <i class="fas fa-shipping-fast text-primary mr-3"></i>
                        <span class="text-sm text-gray-600">Giao hàng miễn phí</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-undo text-primary mr-3"></i>
                        <span class="text-sm text-gray-600">Đổi trả 30 ngày</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-shield-alt text-primary mr-3"></i>
                        <span class="text-sm text-gray-600">Bảo hành chính hãng</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-headset text-primary mr-3"></i>
                        <span class="text-sm text-gray-600">Hỗ trợ 24/7</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <?php if (!empty($related_products)): ?>
    <div class="mt-16">
        <h2 class="text-2xl font-bold text-gray-900 mb-8">Sản phẩm liên quan</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($related_products as $related): ?>
            <div class="bg-white rounded-xl shadow-md overflow-hidden card-hover">
                <div class="relative">
                    <?php 
                    $related_images = json_decode($related['images'], true);
                    $related_main_image = !empty($related_images) ? $related_images[0] : 'https://via.placeholder.com/300x300?text=No+Image';
                    ?>
                <img src="<?php echo $related_main_image; ?>" alt="<?php echo htmlspecialchars($related['name']); ?>" 
                    class="w-full h-48 object-cover" loading="lazy">
                    <?php if ($related['sale_price']): ?>
                        <div class="absolute top-2 left-2 bg-accent text-white px-2 py-1 rounded-full text-xs font-semibold">
                            Sale
                        </div>
                    <?php endif; ?>
                </div>
                <div class="p-4">
                    <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2"><?php echo htmlspecialchars($related['name']); ?></h3>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <?php if ($related['sale_price']): ?>
                                <span class="text-lg font-bold text-primary"><?php echo formatPrice($related['sale_price']); ?></span>
                                <span class="text-sm text-gray-500 line-through"><?php echo formatPrice($related['price']); ?></span>
                            <?php else: ?>
                                <span class="text-lg font-bold text-primary"><?php echo formatPrice($related['price']); ?></span>
                            <?php endif; ?>
                        </div>
                        <a href="product.php?id=<?php echo $related['id']; ?>" 
                           class="bg-primary text-white px-3 py-1 rounded-lg text-sm hover:bg-green-600 transition-colors">
                            Xem chi tiết
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function changeMainImage(imageSrc, element) {
    document.getElementById('main-image').src = imageSrc;
    
    // Update border
    document.querySelectorAll('.grid .border-primary').forEach(el => {
        el.classList.remove('border-primary');
        el.classList.add('border-transparent');
    });
    element.classList.remove('border-transparent');
    element.classList.add('border-primary');
}

function increaseQuantity() {
    const quantityInput = document.getElementById('quantity');
    const max = parseInt(quantityInput.getAttribute('max'));
    const current = parseInt(quantityInput.value);
    if (current < max) {
        quantityInput.value = current + 1;
    }
}

function decreaseQuantity() {
    const quantityInput = document.getElementById('quantity');
    const current = parseInt(quantityInput.value);
    if (current > 1) {
        quantityInput.value = current - 1;
    }
}

function buyNow() {
    const quantity = document.getElementById('quantity').value;
    window.location.href = `checkout.php?product_id=<?php echo $product_id; ?>&quantity=${quantity}`;
}
</script>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<?php include 'includes/footer.php'; ?>
