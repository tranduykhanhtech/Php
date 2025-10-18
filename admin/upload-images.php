<?php
require_once '../config/database.php';
requireAdmin();

$page_title = 'Upload Hình Ảnh';
$page_description = 'Upload và quản lý hình ảnh sản phẩm';

include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Upload Hình Ảnh</h1>
            <p class="text-gray-600 mt-2">Upload và quản lý hình ảnh sản phẩm</p>
        </div>
        <a href="products.php" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Quay lại
        </a>
    </div>

    <!-- Upload Area -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">Upload Hình Ảnh Mới</h2>
        
        <div id="upload-area" class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-primary transition-colors cursor-pointer">
            <div id="upload-content">
                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-4"></i>
                <p class="text-lg text-gray-600 mb-2">Kéo thả hình ảnh vào đây hoặc click để chọn</p>
                <p class="text-sm text-gray-500">Hỗ trợ: JPG, PNG, GIF, WebP (Tối đa 5MB mỗi file)</p>
            </div>
            <div id="upload-progress" class="hidden">
                <div class="w-full bg-gray-200 rounded-full h-2.5 mb-4">
                    <div id="progress-bar" class="bg-primary h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
                <p id="progress-text" class="text-sm text-gray-600">Đang upload...</p>
            </div>
        </div>
        
        <input type="file" id="file-input" multiple accept="image/*" class="hidden">
        
        <div id="uploaded-images" class="mt-6 grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4"></div>
    </div>

    <!-- Image Gallery -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">Thư viện Hình Ảnh</h2>
        
        <div class="mb-4">
            <input type="text" id="search-images" placeholder="Tìm kiếm hình ảnh..." 
                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
        </div>
        
        <div id="image-gallery" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <!-- Images will be loaded here -->
        </div>
    </div>
</div>

<style>
.upload-area.dragover {
    border-color: #10B981;
    background-color: #F0FDF4;
}

.image-item {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.image-item:hover {
    transform: scale(1.05);
}

.image-item img {
    width: 100%;
    height: 150px;
    object-fit: cover;
}

.image-actions {
    position: absolute;
    top: 8px;
    right: 8px;
    display: flex;
    gap: 4px;
    opacity: 0;
    transition: opacity 0.2s;
}

.image-item:hover .image-actions {
    opacity: 1;
}

.image-actions button {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 12px;
}

.copy-btn {
    background: rgba(16, 185, 129, 0.9);
    color: white;
}

.delete-btn {
    background: rgba(239, 68, 68, 0.9);
    color: white;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadArea = document.getElementById('upload-area');
    const fileInput = document.getElementById('file-input');
    const uploadContent = document.getElementById('upload-content');
    const uploadProgress = document.getElementById('upload-progress');
    const progressBar = document.getElementById('progress-bar');
    const progressText = document.getElementById('progress-text');
    const uploadedImages = document.getElementById('uploaded-images');
    const imageGallery = document.getElementById('image-gallery');
    const searchInput = document.getElementById('search-images');

    // Load existing images
    loadImages();

    // Upload area click
    uploadArea.addEventListener('click', () => fileInput.click());

    // File input change
    fileInput.addEventListener('change', handleFiles);

    // Drag and drop
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        handleFiles({ target: { files: e.dataTransfer.files } });
    });

    // Search images
    searchInput.addEventListener('input', filterImages);

    function handleFiles(e) {
        const files = Array.from(e.target.files);
        if (files.length === 0) return;

        uploadContent.classList.add('hidden');
        uploadProgress.classList.remove('hidden');

        let uploadedCount = 0;
        const totalFiles = files.length;

        files.forEach((file, index) => {
            uploadFile(file, (success, data) => {
                uploadedCount++;
                const progress = (uploadedCount / totalFiles) * 100;
                progressBar.style.width = progress + '%';
                progressText.textContent = `Đã upload ${uploadedCount}/${totalFiles} file`;

                if (success) {
                    addUploadedImage(data);
                }

                if (uploadedCount === totalFiles) {
                    setTimeout(() => {
                        uploadContent.classList.remove('hidden');
                        uploadProgress.classList.add('hidden');
                        progressBar.style.width = '0%';
                        fileInput.value = '';
                        loadImages(); // Reload gallery
                    }, 1000);
                }
            });
        });
    }

    function uploadFile(file, callback) {
        const formData = new FormData();
        formData.append('image', file);

        fetch('../api/upload-image.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            callback(data.success, data);
        })
        .catch(error => {
            console.error('Upload error:', error);
            callback(false, { message: 'Lỗi upload' });
        });
    }

    function addUploadedImage(data) {
        const imageDiv = document.createElement('div');
        imageDiv.className = 'image-item';
        imageDiv.innerHTML = `
            <img src="${data.file_url}" alt="Uploaded image">
            <div class="image-actions">
                <button class="copy-btn" onclick="copyToClipboard('${data.file_url}')" title="Copy URL">
                    <i class="fas fa-copy"></i>
                </button>
                <button class="delete-btn" onclick="deleteImage('${data.file_name}')" title="Xóa">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        uploadedImages.appendChild(imageDiv);
    }

    function loadImages() {
        // This would typically load from a database or file listing
        // For now, we'll show a placeholder
        imageGallery.innerHTML = '<p class="col-span-full text-center text-gray-500">Chức năng tải danh sách hình ảnh đang được phát triển</p>';
    }

    function filterImages() {
        const searchTerm = searchInput.value.toLowerCase();
        const images = imageGallery.querySelectorAll('.image-item');
        
        images.forEach(img => {
            const alt = img.querySelector('img').alt.toLowerCase();
            if (alt.includes(searchTerm)) {
                img.style.display = 'block';
            } else {
                img.style.display = 'none';
            }
        });
    }

    // Global functions
    window.copyToClipboard = function(url) {
        navigator.clipboard.writeText(url).then(() => {
            alert('Đã copy URL vào clipboard!');
        });
    };

    window.deleteImage = function(fileName) {
        if (confirm('Bạn có chắc chắn muốn xóa hình ảnh này?')) {
            // Implement delete functionality
            alert('Chức năng xóa đang được phát triển');
        }
    };
});
</script>

<?php include 'includes/footer.php'; ?>
