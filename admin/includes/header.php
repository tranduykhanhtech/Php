<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - Admin' : 'Admin Panel'; ?></title>
    
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
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-white shadow-lg">
            <div class="p-6">
                <div class="flex items-center">
                    <i class="fas fa-leaf text-primary text-2xl mr-2"></i>
                    <span class="text-xl font-bold text-gray-900">Admin Panel</span>
                </div>
            </div>
            
            <nav class="mt-6">
                <a href="index.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-primary bg-opacity-10 text-primary border-r-2 border-primary' : ''; ?>">
                    <i class="fas fa-tachometer-alt mr-3"></i>
                    Dashboard
                </a>
                
                <a href="products.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'bg-primary bg-opacity-10 text-primary border-r-2 border-primary' : ''; ?>">
                    <i class="fas fa-box mr-3"></i>
                    Sản phẩm
                </a>
                
                <a href="categories.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'bg-primary bg-opacity-10 text-primary border-r-2 border-primary' : ''; ?>">
                    <i class="fas fa-tags mr-3"></i>
                    Danh mục
                </a>
                
                <a href="orders.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'bg-primary bg-opacity-10 text-primary border-r-2 border-primary' : ''; ?>">
                    <i class="fas fa-shopping-bag mr-3"></i>
                    Đơn hàng
                </a>
                
                <a href="customers.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 <?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'bg-primary bg-opacity-10 text-primary border-r-2 border-primary' : ''; ?>">
                    <i class="fas fa-users mr-3"></i>
                    Khách hàng
                </a>
                
                <a href="posts.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 <?php echo basename($_SERVER['PHP_SELF']) == 'posts.php' ? 'bg-primary bg-opacity-10 text-primary border-r-2 border-primary' : ''; ?>">
                    <i class="fas fa-blog mr-3"></i>
                    Bài viết
                </a>
                
                <a href="settings.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'bg-primary bg-opacity-10 text-primary border-r-2 border-primary' : ''; ?>">
                    <i class="fas fa-cog mr-3"></i>
                    Cài đặt
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Navigation -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="flex items-center justify-between px-6 py-4">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900"><?php echo $page_title ?? 'Admin Panel'; ?></h1>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <!-- Notifications -->
                        <button class="relative p-2 text-gray-400 hover:text-gray-600">
                            <i class="fas fa-bell text-xl"></i>
                            <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-400"></span>
                        </button>
                        
                        <!-- User Menu (click to toggle) -->
                        <div class="relative">
                            <button id="adminMenuButton" aria-haspopup="true" aria-expanded="false" class="flex items-center space-x-2 text-gray-700 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary rounded px-2 py-1">
                                <img src="https://via.placeholder.com/32x32?text=<?php echo substr($_SESSION['user_name'], 0, 1); ?>" 
                                     class="w-8 h-8 rounded-full">
                                <span><?php echo $_SESSION['user_name']; ?></span>
                                <i class="fas fa-chevron-down text-xs ml-1"></i>
                            </button>

                            <div id="adminMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 hidden" role="menu" aria-labelledby="adminMenuButton">
                                <a href="../profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                    <i class="fas fa-user mr-2"></i>Thông tin cá nhân
                                </a>
                                <a href="../index.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                    <i class="fas fa-home mr-2"></i>Về trang chủ
                                </a>
                                <a href="../logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Đăng xuất
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <script>
                // Admin user menu toggle
                (function(){
                    var btn = document.getElementById('adminMenuButton');
                    var menu = document.getElementById('adminMenu');

                    if (!btn || !menu) return;

                    function openMenu() {
                        menu.classList.remove('hidden');
                        btn.setAttribute('aria-expanded', 'true');
                    }
                    function closeMenu() {
                        menu.classList.add('hidden');
                        btn.setAttribute('aria-expanded', 'false');
                    }

                    btn.addEventListener('click', function(e){
                        e.stopPropagation();
                        if (menu.classList.contains('hidden')) openMenu(); else closeMenu();
                    });

                    // Close when clicking outside
                    document.addEventListener('click', function(e){
                        if (!menu.contains(e.target) && !btn.contains(e.target)) {
                            closeMenu();
                        }
                    });

                    // Close on Escape
                    document.addEventListener('keydown', function(e){
                        if (e.key === 'Escape') closeMenu();
                    });
                })();
            </script>

            <!-- Page Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100">
                <div class="container mx-auto px-6 py-8">
