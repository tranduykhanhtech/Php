                </div>
            </main>
        </div>
    </div>

    <script>
        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            });
        }, 5000);

        // Confirm delete actions
        function confirmDelete(message) {
            return confirm(message || 'Bạn có chắc muốn xóa mục này?');
        }

        // Toggle sidebar on mobile
        function toggleSidebar() {
            const sidebar = document.querySelector('.w-64');
            sidebar.classList.toggle('-translate-x-full');
        }
    </script>
</body>
</html>
