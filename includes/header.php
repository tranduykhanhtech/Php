<?php require_once 'config.php'; ?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    <meta name="description" content="<?php echo isset($page_description) ? $page_description : 'Cửa hàng mỹ phẩm thiên nhiên chất lượng cao'; ?>">
    
    <!-- Favicon (inline to avoid 404) -->
    <link rel="icon" type="image/svg+xml" sizes="any" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Ccircle fill='%2310B981' cx='32' cy='32' r='28'/%3E%3Cpath fill='%23ffffff' d='M40 20c-8 0-14 6-14 14 0 3 1 5 3 7l-5 5 3 3 5-5c2 2 4 3 7 3 8 0 14-6 14-14S48 20 40 20z'/%3E%3C/svg%3E">
    
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
    
    <!-- In-page toast notifications (SSE) -->
    <?php if (isLoggedIn()): ?>
    <script src="/assets/js/browser-notification.js"></script>
    <?php endif; ?>
    
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
                    
                    <!-- Notifications -->
                    <?php if (isLoggedIn()): ?>
                        <div class="relative">
                            <button id="notification-button" class="relative text-gray-900 hover:text-primary transition-colors">
                                <i class="fas fa-bell text-xl"></i>
                                <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center hidden" id="notification-count">0</span>
                            </button>
                            <div id="notification-dropdown" class="absolute right-0 mt-2 w-80 bg-white rounded-md shadow-lg py-1 z-50 hidden" role="menu">
                                <div class="px-4 py-2 border-b border-gray-200">
                                    <h3 class="text-sm font-medium text-gray-900">Thông báo</h3>
                                </div>
                                <div id="notification-list" class="max-h-64 overflow-y-auto">
                                    <div class="px-4 py-3 text-center text-gray-500">
                                        <i class="fas fa-spinner fa-spin"></i> Đang tải...
                                    </div>
                                </div>
                                <div class="px-4 py-2 border-t border-gray-200">
                                    <a href="notifications.php" class="block text-center text-sm text-primary hover:text-green-700">
                                        Xem tất cả thông báo
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
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
                                <a href="notifications.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                    <i class="fas fa-bell mr-2"></i>Thông báo
                                </a>
                                <!--changed: sửa đường dẫn trong href từ admin/ -> /admin/ -->
                                <?php if (isAdmin()): ?>
                                    <a href="/admin/" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem"> 
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
        
        // Notification functions
        function updateNotificationCount() {
            fetch('api/notification-count.php')
                .then(response => response.json())
                .then(data => {
                    const countElement = document.getElementById('notification-count');
                    if (data.count > 0) {
                        countElement.textContent = data.count;
                        countElement.classList.remove('hidden');
                    } else {
                        countElement.classList.add('hidden');
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        function loadNotifications() {
            fetch('api/notifications.php?page=1')
                .then(response => response.json())
                .then(data => {
                    const listElement = document.getElementById('notification-list');
                    if (data.notifications.length === 0) {
                        listElement.innerHTML = '<div class="px-4 py-3 text-center text-gray-500">Chưa có thông báo nào</div>';
                    } else {
                        listElement.innerHTML = data.notifications.slice(0, 5).map(notification => `
                            <div class="px-4 py-3 hover:bg-gray-50 ${!notification.is_read ? 'bg-blue-50' : ''}">
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0">
                                        <span class="text-lg">${getNotificationIcon(notification.type)}</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900">${notification.title}</p>
                                        <p class="text-sm text-gray-600 truncate">${notification.message}</p>
                                        <p class="text-xs text-gray-500">${formatTime(notification.created_at)}</p>
                                    </div>
                                    ${!notification.is_read ? '<div class="w-2 h-2 bg-blue-500 rounded-full"></div>' : ''}
                                </div>
                            </div>
                        `).join('');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('notification-list').innerHTML = '<div class="px-4 py-3 text-center text-red-500">Lỗi tải thông báo</div>';
                });
        }
        
        function getNotificationIcon(type) {
            const icons = {
                'order': '🛍️',
                'contact': '💬',
                'general': '📢',
                'promotion': '🎉'
            };
            return icons[type] || '📢';
        }
        
        function formatTime(timestamp) {
            const now = new Date();
            const time = new Date(timestamp);
            const diff = now - time;
            const minutes = Math.floor(diff / 60000);
            const hours = Math.floor(diff / 3600000);
            const days = Math.floor(diff / 86400000);
            
            if (minutes < 1) return 'Vừa xong';
            if (minutes < 60) return `${minutes} phút trước`;
            if (hours < 24) return `${hours} giờ trước`;
            return `${days} ngày trước`;
        }
        
        // Update notification count on page load
        updateNotificationCount();
        
        // Notification dropdown toggle
        (function() {
            const btn = document.getElementById('notification-button');
            const dropdown = document.getElementById('notification-dropdown');
            if (!btn || !dropdown) return;

            function openDropdown() {
                dropdown.classList.remove('hidden');
                loadNotifications();
            }

            function closeDropdown() {
                dropdown.classList.add('hidden');
            }

            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                if (dropdown.classList.contains('hidden')) {
                    openDropdown();
                } else {
                    closeDropdown();
                }
            });

            // Close when clicking outside
            document.addEventListener('click', function(e) {
                if (!dropdown.contains(e.target) && !btn.contains(e.target)) {
                    closeDropdown();
                }
            });

            // Close on Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') closeDropdown();
            });
        })();
        
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
