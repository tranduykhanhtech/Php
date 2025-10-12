<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    <meta name="description" content="<?php echo isset($page_description) ? $page_description : 'Cửa hàng mỹ phẩm thiên nhiên chất lượng cao'; ?>">
    
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#10B981',
                        secondary: '#F59E0B',
                        accent: '#EC4899'
                    }
                }
            }
        }
    </script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        .hero-gradient {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
        }
        .card-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex-shrink-0">
                    <a href="index.php" class="flex items-center">
                        <i class="fas fa-leaf text-primary text-2xl mr-2"></i>
                        <span class="text-xl font-bold text-gray-900"><?php echo SITE_NAME; ?></span>
                    </a>
                </div>
                
                <!-- Desktop Menu -->
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <a href="index.php" class="text-gray-900 hover:text-primary px-3 py-2 rounded-md text-sm font-medium transition-colors">
                            <i class="fas fa-home mr-1"></i>Trang chủ
                        </a>
                        <a href="products.php" class="text-gray-900 hover:text-primary px-3 py-2 rounded-md text-sm font-medium transition-colors">
                            <i class="fas fa-store mr-1"></i>Sản phẩm
                        </a>
                        <a href="blog.php" class="text-gray-900 hover:text-primary px-3 py-2 rounded-md text-sm font-medium transition-colors">
                            <i class="fas fa-blog mr-1"></i>Blog
                        </a>
                        <a href="about.php" class="text-gray-900 hover:text-primary px-3 py-2 rounded-md text-sm font-medium transition-colors">
                            <i class="fas fa-info-circle mr-1"></i>Giới thiệu
                        </a>
                        <a href="contact.php" class="text-gray-900 hover:text-primary px-3 py-2 rounded-md text-sm font-medium transition-colors">
                            <i class="fas fa-envelope mr-1"></i>Liên hệ
                        </a>
                    </div>
                </div>
                
                <!-- Right side -->
                <div class="flex items-center space-x-4">
                    <!-- Search -->
                    <div class="hidden md:block">
                        <form action="search.php" method="GET" class="flex">
                            <input type="text" name="q" placeholder="Tìm kiếm sản phẩm..." 
                                   class="px-3 py-2 border border-gray-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-r-md hover:bg-green-600 transition-colors">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                    
                    <!-- Cart -->
                    <a href="cart.php" class="relative text-gray-900 hover:text-primary transition-colors">
                        <i class="fas fa-shopping-cart text-xl"></i>
                        <span class="absolute -top-2 -right-2 bg-accent text-white text-xs rounded-full h-5 w-5 flex items-center justify-center" id="cart-count">0</span>
                    </a>
                    
                    <!-- User Menu -->
                    <?php if (isLoggedIn()): ?>
                        <div class="relative">
                            <button id="user-menu-button" aria-haspopup="true" aria-expanded="false" type="button" class="flex items-center text-gray-900 hover:text-primary transition-colors focus:outline-none">
                                <i class="fas fa-user-circle text-xl mr-1"></i>
                                <span class="hidden md:block"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                                <i class="fas fa-chevron-down ml-1 text-xs"></i>
                            </button>
                            <div id="user-menu" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 hidden" role="menu" aria-labelledby="user-menu-button">
                                <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                    <i class="fas fa-user mr-2"></i>Thông tin cá nhân
                                </a>
                                <a href="orders.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                    <i class="fas fa-shopping-bag mr-2"></i>Đơn hàng của tôi
                                </a>
                                <?php if (isAdmin()): ?>
                                    <a href="admin/" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                        <i class="fas fa-cog mr-2"></i>Quản trị
                                    </a>
                                <?php endif; ?>
                                <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Đăng xuất
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="text-gray-900 hover:text-primary transition-colors">
                            <i class="fas fa-sign-in-alt mr-1"></i>Đăng nhập
                        </a>
                    <?php endif; ?>
                    
                    <!-- Mobile menu button -->
                    <button class="md:hidden inline-flex items-center justify-center p-2 rounded-md text-gray-900 hover:text-primary focus:outline-none" id="mobile-menu-button">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile menu -->
        <div class="md:hidden hidden" id="mobile-menu">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg-gray-50">
                <a href="index.php" class="text-gray-900 hover:text-primary block px-3 py-2 rounded-md text-base font-medium">
                    <i class="fas fa-home mr-2"></i>Trang chủ
                </a>
                <a href="products.php" class="text-gray-900 hover:text-primary block px-3 py-2 rounded-md text-base font-medium">
                    <i class="fas fa-store mr-2"></i>Sản phẩm
                </a>
                <a href="blog.php" class="text-gray-900 hover:text-primary block px-3 py-2 rounded-md text-base font-medium">
                    <i class="fas fa-blog mr-2"></i>Blog
                </a>
                <a href="about.php" class="text-gray-900 hover:text-primary block px-3 py-2 rounded-md text-base font-medium">
                    <i class="fas fa-info-circle mr-2"></i>Giới thiệu
                </a>
                <a href="contact.php" class="text-gray-900 hover:text-primary block px-3 py-2 rounded-md text-base font-medium">
                    <i class="fas fa-envelope mr-2"></i>Liên hệ
                </a>
                <div class="px-3 py-2">
                    <form action="search.php" method="GET" class="flex">
                        <input type="text" name="q" placeholder="Tìm kiếm..." 
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-primary">
                        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-r-md">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        });
        
        // Update cart count
        function updateCartCount() {
            fetch('api/cart-count.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('cart-count').textContent = data.count;
                })
                .catch(error => console.error('Error:', error));
        }
        
        // Update cart count on page load
        updateCartCount();
        
        // User menu toggle (click to open/close) and accessibility
        (function() {
            const btn = document.getElementById('user-menu-button');
            const menu = document.getElementById('user-menu');
            if (!btn || !menu) return;

            function openMenu() {
                menu.classList.remove('hidden');
                btn.setAttribute('aria-expanded', 'true');
            }

            function closeMenu() {
                menu.classList.add('hidden');
                btn.setAttribute('aria-expanded', 'false');
            }

            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                if (menu.classList.contains('hidden')) openMenu(); else closeMenu();
            });

            // Close when clicking outside
            document.addEventListener('click', function(e) {
                if (!menu.contains(e.target) && !btn.contains(e.target)) {
                    closeMenu();
                }
            });

            // Close on Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') closeMenu();
            });
        })();
    </script>
