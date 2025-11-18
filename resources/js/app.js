import './bootstrap';

// Instagram Downloader Functionality
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('instagram-form');
    const urlInput = document.getElementById('instagram-url');
    const fetchBtn = document.getElementById('fetch-btn');
    const loading = document.getElementById('loading');
    const error = document.getElementById('error');
    const errorMessage = document.getElementById('error-message');
    const results = document.getElementById('results');
    const mediaContainer = document.getElementById('media-container');

    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Form submit handler
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const url = urlInput.value.trim();

        if (!url) {
            showError('Vui lòng nhập link Instagram');
            return;
        }

        // Validate Instagram URL
        if (!isValidInstagramUrl(url)) {
            showError('Link Instagram không hợp lệ. Vui lòng nhập link đúng định dạng.');
            return;
        }

        // Reset UI
        hideError();
        hideResults();
        showLoading();

        try {
            const response = await fetch('/api/instagram/fetch', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ url })
            });

            const data = await response.json();

            hideLoading();

            if (!data.success) {
                showError(data.message || 'Đã xảy ra lỗi khi tải nội dung');
                return;
            }

            displayResults(data.data);

        } catch (err) {
            hideLoading();
            showError('Đã xảy ra lỗi kết nối. Vui lòng thử lại.');
            console.error('Fetch error:', err);
        }
    });

    // Validate Instagram URL
    function isValidInstagramUrl(url) {
        const patterns = [
            /instagram\.com\/p\/[A-Za-z0-9_-]+/,
            /instagram\.com\/reel\/[A-Za-z0-9_-]+/,
            /instagram\.com\/reels\/[A-Za-z0-9_-]+/,
            /instagram\.com\/tv\/[A-Za-z0-9_-]+/,
            /instagram\.com\/stories\/[^\/]+\/[0-9]+/
        ];

        return patterns.some(pattern => pattern.test(url));
    }

    // Show loading state
    function showLoading() {
        loading.classList.remove('hidden');
        fetchBtn.disabled = true;
        fetchBtn.classList.add('opacity-50', 'cursor-not-allowed');
    }

    // Hide loading state
    function hideLoading() {
        loading.classList.add('hidden');
        fetchBtn.disabled = false;
        fetchBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    }

    // Show error message
    function showError(message) {
        errorMessage.textContent = message;
        error.classList.remove('hidden');
    }

    // Hide error message
    function hideError() {
        error.classList.add('hidden');
        errorMessage.textContent = '';
    }

    // Hide results
    function hideResults() {
        results.classList.add('hidden');
        mediaContainer.innerHTML = '';
    }

    // Display results
    function displayResults(data) {
        // Update result info
        document.getElementById('result-thumbnail').src = data.thumbnail || '/placeholder.jpg';
        document.getElementById('result-type').textContent = getTypeLabel(data.type);
        document.getElementById('result-author').textContent = '@' + (data.author || 'unknown');
        document.getElementById('result-caption').textContent = data.caption || 'Không có mô tả';

        // Clear media container
        mediaContainer.innerHTML = '';

        // Add media items
        if (data.media && data.media.length > 0) {
            data.media.forEach((item, index) => {
                const mediaCard = createMediaCard(item, index);
                mediaContainer.appendChild(mediaCard);
            });
        }

        // Show results
        results.classList.remove('hidden');

        // Scroll to results
        results.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // Get type label
    function getTypeLabel(type) {
        const labels = {
            'image': 'Ảnh',
            'video': 'Video',
            'carousel': 'Album',
            'post': 'Bài viết'
        };
        return labels[type] || 'Nội dung';
    }

    // Create media card
    function createMediaCard(item, index) {
        const card = document.createElement('div');
        card.className = 'bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow';

        const isVideo = item.type === 'video';
        const mediaUrl = item.url;
        const thumbnail = item.thumbnail || item.url;

        card.innerHTML = `
            <div class="relative aspect-square bg-gray-100">
                ${isVideo ? `
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="w-16 h-16 bg-black bg-opacity-50 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/>
                            </svg>
                        </div>
                    </div>
                ` : ''}
                <img
                    src="${thumbnail}"
                    alt="Media ${index + 1}"
                    class="w-full h-full object-cover"
                    onerror="this.src='/placeholder.jpg'"
                >
            </div>
            <div class="p-4">
                <div class="flex items-center justify-between mb-3">
                    <span class="inline-flex items-center gap-1 text-sm font-medium text-gray-700">
                        ${isVideo ? `
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zm12.553 1.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"/>
                            </svg>
                            Video
                        ` : `
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                            </svg>
                            Ảnh
                        `}
                    </span>
                </div>
                <button
                    onclick="downloadMedia('${mediaUrl}', '${isVideo ? 'video' : 'image'}', ${index + 1})"
                    class="w-full bg-gradient-to-r from-purple-500 to-pink-500 text-white font-semibold py-2 px-4 rounded-lg hover:shadow-md transform hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Tải xuống
                </button>
            </div>
        `;

        return card;
    }

    // Download media function (make it global)
    window.downloadMedia = async function(url, type, index) {
        try {
            // Create a temporary link and trigger download
            const link = document.createElement('a');
            link.href = url;
            link.download = `instagram_${type}_${Date.now()}_${index}.${type === 'video' ? 'mp4' : 'jpg'}`;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';

            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            // Show success message
            showSuccessToast('Đang tải xuống...');

        } catch (err) {
            console.error('Download error:', err);
            showError('Không thể tải xuống. Vui lòng thử lại.');
        }
    };

    // Show success toast
    function showSuccessToast(message) {
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center gap-3 z-50 animate-slide-up';
        toast.innerHTML = `
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span>${message}</span>
        `;

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
});
