<?php
require_once 'config/database.php';

requireLogin();

$page_title = 'Cài đặt thông báo';

include 'includes/header.php';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Cài đặt thông báo</h1>
            <p class="mt-2 text-gray-600">Quản lý cách bạn nhận thông báo</p>
        </div>

        <!-- In-page Toast Notification Settings -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-3 mb-2">
                        <i class="fas fa-bell text-2xl text-primary"></i>
                        <h2 class="text-xl font-semibold text-gray-900">Thông báo trong trang (góc phải dưới)</h2>
                    </div>
                    <p class="text-gray-600 mb-4">
                        Nhận thông báo nổi ngay trong trang khi có cập nhật mới về đơn hàng, khuyến mãi và tin tức. Mặc định tính năng này <strong>đang tắt</strong> để tiết kiệm tài nguyên; khi bật, hệ thống dùng một kết nối nền duy nhất (không spam request).
                    </p>
                    
                    <div id="notification-status" class="mb-4">
                        <!-- Status will be inserted by JS -->
                    </div>
                    
                    <div id="notification-actions" class="space-y-3">
                        <!-- Actions will be inserted by JS -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Features -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-list-check mr-2 text-primary"></i>
                Bạn sẽ nhận được thông báo về:
            </h3>
            <ul class="space-y-3">
                <li class="flex items-start">
                    <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                    <div>
                        <strong class="text-gray-900">Đơn hàng</strong>
                        <p class="text-sm text-gray-600">Xác nhận, vận chuyển, giao hàng thành công</p>
                    </div>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                    <div>
                        <strong class="text-gray-900">Liên hệ & Hỗ trợ</strong>
                        <p class="text-sm text-gray-600">Phản hồi yêu cầu hỗ trợ của bạn</p>
                    </div>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                    <div>
                        <strong class="text-gray-900">Khuyến mãi</strong>
                        <p class="text-sm text-gray-600">Flash sale, voucher, điểm thưởng</p>
                    </div>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                    <div>
                        <strong class="text-gray-900">Tin tức</strong>
                        <p class="text-sm text-gray-600">Cập nhật chính sách, sản phẩm mới</p>
                    </div>
                </li>
            </ul>
        </div>

        <!-- Instructions -->
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <div class="flex items-start">
                <i class="fas fa-info-circle text-blue-600 text-xl mt-1 mr-3"></i>
                <div>
                    <h4 class="font-semibold text-gray-900 mb-2">Lưu ý</h4>
                    <ul class="text-sm text-gray-700 space-y-2">
                        <li>• Thông báo chỉ hoạt động khi bạn đang mở website</li>
                        <li>• Bạn có thể bật/tắt bất cứ lúc nào; khi tắt sẽ không có request nền</li>
                        <li>• Không yêu cầu quyền thông báo của trình duyệt và không hiện pop-up hệ thống</li>
                        <li>• Không có thông báo spam, chỉ những thông tin quan trọng</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusDiv = document.getElementById('notification-status');
    const actionsDiv = document.getElementById('notification-actions');
    
    function updateStatus() {
        const enabled = (localStorage.getItem('enableBrowserNotifications') === '1');
        let statusHTML = '';
        let actionsHTML = '';

        if (enabled) {
            statusHTML = `
                <div class="flex items-center space-x-2 text-green-600">
                    <i class="fas fa-check-circle"></i>
                    <span class="font-medium">Đang bật thông báo trong trang</span>
                </div>
            `;
            actionsHTML = `
                <button onclick="disableNotifications()" 
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fas fa-bell-slash mr-2"></i>Tắt thông báo
                </button>
                <button onclick="testNotification()" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-vial mr-2"></i>Gửi thông báo thử
                </button>
            `;
        } else {
            statusHTML = `
                <div class="flex items-center space-x-2 text-gray-600">
                    <i class="fas fa-bell-slash"></i>
                    <span>Đang tắt thông báo trong trang</span>
                </div>
            `;
            actionsHTML = `
                <button onclick="enableNotifications()" 
                        class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-green-600 transition-colors font-semibold">
                    <i class="fas fa-bell mr-2"></i>Bật thông báo ngay
                </button>
            `;
        }

        statusDiv.innerHTML = statusHTML;
        actionsDiv.innerHTML = actionsHTML;
    }
    
    // Update initial status
    updateStatus();
    
    // Global functions
    window.enableNotifications = async function() {
        if (window.browserNotification) {
            window.browserNotification.savePreference(true);
            await window.browserNotification.init();
            updateStatus();
            alert('✅ Đã bật thông báo trong trang!');
        }
    };
    
    window.disableNotifications = function() {
        if (window.browserNotification) {
            window.browserNotification.stopSSE();
            window.browserNotification.savePreference(false);
            alert('🔕 Đã tắt thông báo. Bạn có thể bật lại bất cứ lúc nào.');
            updateStatus();
        }
    };
    
    window.testNotification = function() {
        if (window.browserNotification) {
            window.browserNotification.showToast('🧪 Thông báo thử nghiệm', 'Đây là thông báo thử nghiệm từ Gecko Shop. Bạn sẽ nhận được thông báo tương tự khi có cập nhật mới!', '/notifications.php');
        }
    };
});
</script>

<?php include 'includes/footer.php'; ?>
