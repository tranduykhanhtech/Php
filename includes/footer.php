    <!-- Footer -->
    <footer class="bg-gray-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-leaf text-primary text-2xl mr-2"></i>
                        <span class="text-xl font-bold"><?php echo SITE_NAME; ?></span>
                    </div>
                    <p class="text-gray-300 mb-4">
                        Chuyên cung cấp các sản phẩm mỹ phẩm thiên nhiên chất lượng cao, 
                        an toàn cho sức khỏe và thân thiện với môi trường.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-300 hover:text-primary transition-colors">
                            <i class="fab fa-facebook-f text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-300 hover:text-primary transition-colors">
                            <i class="fab fa-instagram text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-300 hover:text-primary transition-colors">
                            <i class="fab fa-youtube text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-300 hover:text-primary transition-colors">
                            <i class="fab fa-tiktok text-xl"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Liên kết nhanh</h3>
                    <ul class="space-y-2">
                        <li><a href="index.php" class="text-gray-300 hover:text-primary transition-colors">Trang chủ</a></li>
                        <li><a href="products.php" class="text-gray-300 hover:text-primary transition-colors">Sản phẩm</a></li>
                        <li><a href="blog.php" class="text-gray-300 hover:text-primary transition-colors">Blog</a></li>
                        <li><a href="about.php" class="text-gray-300 hover:text-primary transition-colors">Giới thiệu</a></li>
                        <li><a href="contact.php" class="text-gray-300 hover:text-primary transition-colors">Liên hệ</a></li>
                    </ul>
                </div>
                
                <!-- Contact Info -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Thông tin liên hệ</h3>
                    <div class="space-y-2">
                        <div class="flex items-center">
                            <i class="fas fa-map-marker-alt text-primary mr-2"></i>
                            <span class="text-gray-300">123 Đường ABC, Quận XYZ, TP.HCM</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-phone text-primary mr-2"></i>
                            <span class="text-gray-300">0123 456 789</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-envelope text-primary mr-2"></i>
                            <span class="text-gray-300">info@naturalcosmetics.com</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-clock text-primary mr-2"></i>
                            <span class="text-gray-300">8:00 - 22:00 (T2-CN)</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-700 mt-8 pt-8">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <p class="text-gray-300 text-sm">
                        © <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Tất cả quyền được bảo lưu.
                    </p>
                    <div class="flex space-x-6 mt-4 md:mt-0">
                        <a href="privacy.php" class="text-gray-300 hover:text-primary text-sm transition-colors">Chính sách bảo mật</a>
                        <a href="terms.php" class="text-gray-300 hover:text-primary text-sm transition-colors">Điều khoản sử dụng</a>
                        <a href="shipping.php" class="text-gray-300 hover:text-primary text-sm transition-colors">Chính sách vận chuyển</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to top button -->
    <button id="back-to-top" class="fixed bottom-4 right-4 bg-primary text-white p-3 rounded-full shadow-lg hover:bg-green-600 transition-all duration-300 opacity-0 invisible">
        <i class="fas fa-arrow-up"></i>
    </button>

    <script>
        // Back to top button
        const backToTopButton = document.getElementById('back-to-top');
        
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTopButton.classList.remove('opacity-0', 'invisible');
                backToTopButton.classList.add('opacity-100', 'visible');
            } else {
                backToTopButton.classList.add('opacity-0', 'invisible');
                backToTopButton.classList.remove('opacity-100', 'visible');
            }
        });
        
        backToTopButton.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    </script>
</body>
</html>
