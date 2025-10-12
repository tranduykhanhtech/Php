<?php
require_once 'config/database.php';

$page_title = 'Giới thiệu';
$page_description = 'Tìm hiểu về Natural Cosmetics Shop - cửa hàng mỹ phẩm thiên nhiên chất lượng cao';

include 'includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Hero Section -->
    <div class="text-center mb-16">
        <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">
            Về chúng tôi
        </h1>
        <p class="text-xl text-gray-600 max-w-3xl mx-auto">
            Natural Cosmetics Shop cam kết mang đến những sản phẩm mỹ phẩm thiên nhiên 
            chất lượng cao, an toàn cho sức khỏe và thân thiện với môi trường.
        </p>
    </div>

    <!-- Mission & Vision -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 mb-16">
        <div class="bg-white rounded-lg shadow-md p-8">
            <div class="w-16 h-16 bg-primary bg-opacity-10 rounded-full flex items-center justify-center mb-6">
                <i class="fas fa-bullseye text-2xl text-primary"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Sứ mệnh</h2>
            <p class="text-gray-600 leading-relaxed">
                Chúng tôi cam kết cung cấp những sản phẩm mỹ phẩm thiên nhiên chất lượng cao, 
                giúp khách hàng chăm sóc làn da một cách an toàn và hiệu quả. Mỗi sản phẩm 
                đều được lựa chọn kỹ lưỡng từ những thành phần thiên nhiên tốt nhất.
            </p>
        </div>

        <div class="bg-white rounded-lg shadow-md p-8">
            <div class="w-16 h-16 bg-primary bg-opacity-10 rounded-full flex items-center justify-center mb-6">
                <i class="fas fa-eye text-2xl text-primary"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Tầm nhìn</h2>
            <p class="text-gray-600 leading-relaxed">
                Trở thành thương hiệu mỹ phẩm thiên nhiên hàng đầu tại Việt Nam, 
                được khách hàng tin tưởng và yêu mến. Chúng tôi hướng đến một tương lai 
                nơi mọi người đều có thể sử dụng mỹ phẩm an toàn và thân thiện với môi trường.
            </p>
        </div>
    </div>

    <!-- Values -->
    <div class="mb-16">
        <h2 class="text-3xl font-bold text-gray-900 text-center mb-12">Giá trị cốt lõi</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-leaf text-3xl text-green-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">100% Thiên nhiên</h3>
                <p class="text-gray-600">
                    Tất cả sản phẩm đều được làm từ thành phần thiên nhiên, 
                    không chứa hóa chất độc hại.
                </p>
            </div>

            <div class="text-center">
                <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-shield-alt text-3xl text-blue-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">An toàn tuyệt đối</h3>
                <p class="text-gray-600">
                    Mỗi sản phẩm đều được kiểm định chất lượng nghiêm ngặt 
                    trước khi đến tay khách hàng.
                </p>
            </div>

            <div class="text-center">
                <div class="w-20 h-20 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-heart text-3xl text-purple-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Tốt cho da</h3>
                <p class="text-gray-600">
                    Các sản phẩm được nghiên cứu để phù hợp với mọi loại da, 
                    đặc biệt là da nhạy cảm.
                </p>
            </div>
        </div>
    </div>

    <!-- Story -->
    <div class="bg-gray-50 rounded-lg p-8 mb-16">
        <h2 class="text-3xl font-bold text-gray-900 text-center mb-8">Câu chuyện của chúng tôi</h2>
        <div class="max-w-4xl mx-auto">
            <p class="text-lg text-gray-600 leading-relaxed mb-6">
                Natural Cosmetics Shop được thành lập từ niềm đam mê với mỹ phẩm thiên nhiên 
                và mong muốn mang đến những sản phẩm an toàn, chất lượng cho người tiêu dùng Việt Nam.
            </p>
            <p class="text-lg text-gray-600 leading-relaxed mb-6">
                Với hơn 5 năm kinh nghiệm trong lĩnh vực mỹ phẩm thiên nhiên, chúng tôi đã 
                xây dựng được mạng lưới đối tác uy tín trên toàn thế giới, mang về những 
                sản phẩm tốt nhất cho khách hàng.
            </p>
            <p class="text-lg text-gray-600 leading-relaxed">
                Mỗi sản phẩm trong cửa hàng đều được chúng tôi lựa chọn kỹ lưỡng, 
                đảm bảo chất lượng và an toàn cho người sử dụng. Chúng tôi tin rằng 
                vẻ đẹp thật sự đến từ sự tự nhiên và khỏe mạnh.
            </p>
        </div>
    </div>

    <!-- Team -->
    <div class="mb-16">
        <h2 class="text-3xl font-bold text-gray-900 text-center mb-12">Đội ngũ của chúng tôi</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="w-32 h-32 bg-gray-200 rounded-full mx-auto mb-4 flex items-center justify-center">
                    <i class="fas fa-user text-4xl text-gray-400"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Nguyễn Thị Lan</h3>
                <p class="text-gray-600 mb-2">Giám đốc điều hành</p>
                <p class="text-sm text-gray-500">
                    Chuyên gia mỹ phẩm thiên nhiên với 10 năm kinh nghiệm
                </p>
            </div>

            <div class="text-center">
                <div class="w-32 h-32 bg-gray-200 rounded-full mx-auto mb-4 flex items-center justify-center">
                    <i class="fas fa-user text-4xl text-gray-400"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Trần Văn Minh</h3>
                <p class="text-gray-600 mb-2">Chuyên gia nghiên cứu</p>
                <p class="text-sm text-gray-500">
                    Tiến sĩ Hóa học, chuyên về thành phần thiên nhiên
                </p>
            </div>

            <div class="text-center">
                <div class="w-32 h-32 bg-gray-200 rounded-full mx-auto mb-4 flex items-center justify-center">
                    <i class="fas fa-user text-4xl text-gray-400"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Lê Thị Hương</h3>
                <p class="text-gray-600 mb-2">Chuyên gia tư vấn</p>
                <p class="text-sm text-gray-500">
                    Chuyên gia chăm sóc da với 8 năm kinh nghiệm
                </p>
            </div>
        </div>
    </div>

    <!-- Contact Info -->
    <div class="bg-primary rounded-lg p-8 text-white text-center">
        <h2 class="text-3xl font-bold mb-6">Liên hệ với chúng tôi</h2>
        <p class="text-xl mb-8">Chúng tôi luôn sẵn sàng hỗ trợ và tư vấn cho bạn</p>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
                <i class="fas fa-map-marker-alt text-3xl mb-4"></i>
                <h3 class="text-lg font-semibold mb-2">Địa chỉ</h3>
                <p>123 Đường ABC, Quận XYZ, TP.HCM</p>
            </div>
            
            <div>
                <i class="fas fa-phone text-3xl mb-4"></i>
                <h3 class="text-lg font-semibold mb-2">Điện thoại</h3>
                <p>0123 456 789</p>
            </div>
            
            <div>
                <i class="fas fa-envelope text-3xl mb-4"></i>
                <h3 class="text-lg font-semibold mb-2">Email</h3>
                <p>info@naturalcosmetics.com</p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
