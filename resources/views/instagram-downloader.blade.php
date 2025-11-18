<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Instagram Downloader - Tải ảnh, video, reels, stories từ Instagram</title>
    <meta name="description" content="Tải xuống ảnh, video, reels và stories từ Instagram miễn phí, nhanh chóng và dễ dàng.">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-purple-50 via-pink-50 to-blue-50">
    <!-- Header Ad Placeholder -->
    <div id="header-ad" class="bg-gray-100 border-b border-gray-200 py-2">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <div class="text-xs text-gray-500 h-20 flex items-center justify-center">
                <span>[ Header Ad Space - 728x90 ]</span>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <header class="text-center mb-12">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-purple-500 via-pink-500 to-orange-500 rounded-2xl mb-4 shadow-lg">
                    <svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                    </svg>
                </div>
                <h1 class="text-4xl md:text-5xl font-bold text-gray-800 mb-3">
                    Instagram Downloader
                </h1>
                <p class="text-lg text-gray-600">
                    Tải xuống ảnh, video, reels và stories từ Instagram miễn phí
                </p>
            </header>

            <!-- Download Form -->
            <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
                <form id="instagram-form" class="space-y-6">
                    <div>
                        <label for="instagram-url" class="block text-sm font-medium text-gray-700 mb-2">
                            Nhập link Instagram
                        </label>
                        <div class="relative">
                            <input
                                type="text"
                                id="instagram-url"
                                placeholder="https://www.instagram.com/p/xxxxx hoặc /reel/xxxxx"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                                required
                            >
                            <div class="absolute right-3 top-1/2 -translate-y-1/2">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                </svg>
                            </div>
                        </div>
                        <p class="mt-2 text-sm text-gray-500">
                            Hỗ trợ: Posts, Reels, Videos, Stories
                        </p>
                    </div>

                    <button
                        type="submit"
                        id="fetch-btn"
                        class="w-full bg-gradient-to-r from-purple-500 via-pink-500 to-orange-500 text-white font-semibold py-3 px-6 rounded-lg hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <span>Tìm kiếm nội dung</span>
                    </button>
                </form>

                <!-- Loading State -->
                <div id="loading" class="hidden mt-8 text-center">
                    <div class="inline-flex items-center gap-3">
                        <svg class="animate-spin h-8 w-8 text-purple-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-gray-600 font-medium">Đang tải...</span>
                    </div>
                </div>

                <!-- Error Message -->
                <div id="error" class="hidden mt-6 bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <p class="text-red-700 text-sm" id="error-message"></p>
                    </div>
                </div>
            </div>

            <!-- Sidebar Ad Placeholder (Desktop) -->
            <div class="hidden lg:block fixed right-4 top-1/2 -translate-y-1/2">
                <div class="bg-gray-100 border border-gray-200 rounded-lg p-4 text-center">
                    <div class="text-xs text-gray-500 w-32 h-96 flex items-center justify-center">
                        <span class="transform -rotate-0">[ Sidebar Ad<br>160x600 ]</span>
                    </div>
                </div>
            </div>

            <!-- Results Container -->
            <div id="results" class="hidden">
                <!-- Content Info -->
                <div class="bg-white rounded-2xl shadow-xl p-8 mb-6">
                    <div class="flex items-start gap-4">
                        <img id="result-thumbnail" src="" alt="Thumbnail" class="w-24 h-24 rounded-lg object-cover flex-shrink-0">
                        <div class="flex-1 min-w-0">
                            <h2 class="text-xl font-bold text-gray-800 mb-2">
                                <span id="result-type" class="inline-block px-3 py-1 bg-purple-100 text-purple-700 text-sm font-semibold rounded-full mr-2"></span>
                            </h2>
                            <p class="text-gray-600 mb-2">
                                <span class="font-medium">Tác giả:</span>
                                <span id="result-author" class="text-purple-600">@username</span>
                            </p>
                            <p id="result-caption" class="text-gray-500 text-sm line-clamp-2"></p>
                        </div>
                    </div>
                </div>

                <!-- Media Grid -->
                <div id="media-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
                    <!-- Media items will be inserted here -->
                </div>

                <!-- Bottom Ad Placeholder -->
                <div class="bg-gray-100 border border-gray-200 rounded-lg py-4 mb-8">
                    <div class="text-xs text-gray-500 text-center h-24 flex items-center justify-center">
                        <span>[ Bottom Ad Space - 728x90 ]</span>
                    </div>
                </div>
            </div>

            <!-- Features Section -->
            <div class="grid md:grid-cols-3 gap-6 mt-12">
                <div class="bg-white rounded-xl p-6 shadow-md">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Nhanh chóng</h3>
                    <p class="text-gray-600 text-sm">Tải xuống nội dung Instagram chỉ trong vài giây</p>
                </div>

                <div class="bg-white rounded-xl p-6 shadow-md">
                    <div class="w-12 h-12 bg-pink-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">An toàn</h3>
                    <p class="text-gray-600 text-sm">Không lưu trữ nội dung trên server, bảo mật tuyệt đối</p>
                </div>

                <div class="bg-white rounded-xl p-6 shadow-md">
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Miễn phí</h3>
                    <p class="text-gray-600 text-sm">Hoàn toàn miễn phí, không giới hạn số lượng tải</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-16 py-8">
        <div class="container mx-auto px-4 text-center">
            <p class="text-gray-600 text-sm">
                © 2025 Instagram Downloader. Công cụ này không liên kết với Instagram.
            </p>
            <p class="text-gray-500 text-xs mt-2">
                Vui lòng tôn trọng quyền sở hữu trí tuệ và quyền riêng tư khi sử dụng nội dung đã tải.
            </p>
        </div>
    </footer>
</body>
</html>
