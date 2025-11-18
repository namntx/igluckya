# Instagram Downloader

Một website đơn giản và hiện đại cho phép tải xuống nội dung từ Instagram bao gồm: ảnh, video, reels và stories.

![Instagram Downloader](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat&logo=laravel)
![Tailwind CSS](https://img.shields.io/badge/Tailwind-4.0-38B2AC?style=flat&logo=tailwind-css)
![License](https://img.shields.io/badge/license-MIT-green)

## ✨ Tính năng

- 📸 **Tải ảnh**: Hỗ trợ tải đơn ảnh và album nhiều ảnh
- 🎬 **Tải video**: Tải video và IGTV
- 🎥 **Tải Reels**: Hỗ trợ tải Instagram Reels
- 📖 **Tải Stories**: Tải stories từ Instagram
- 🚀 **Nhanh chóng**: Xử lý nhanh, không lưu trữ trên server
- 🔒 **An toàn**: Không lưu trữ dữ liệu người dùng
- 📱 **Responsive**: Giao diện tương thích mọi thiết bị
- 🎨 **Hiện đại**: UI/UX đẹp mắt với Tailwind CSS
- 💰 **Tích hợp Ads**: Vị trí quảng cáo không gây phiền nhiễu

## 🛠️ Công nghệ sử dụng

- **Backend**: Laravel 12 (PHP 8.2+)
- **Frontend**: Vanilla JavaScript + Tailwind CSS 4.0
- **Build Tool**: Vite 7
- **HTTP Client**: Guzzle

## 📋 Yêu cầu hệ thống

- PHP >= 8.2
- Composer
- Node.js >= 18.x
- NPM hoặc Yarn

## 🚀 Cài đặt

### 1. Clone repository

```bash
git clone <repository-url>
cd igluckya
```

### 2. Cài đặt dependencies

```bash
composer install
npm install
```

### 3. Cấu hình môi trường

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Cấu hình database (tùy chọn)

Mặc định project sử dụng SQLite. Nếu muốn sử dụng database khác, cập nhật file `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=instagram_downloader
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Chạy migrations

```bash
php artisan migrate
```

### 6. Build assets

```bash
npm run build
```

Hoặc chạy development server:

```bash
npm run dev
```

### 7. Chạy ứng dụng

```bash
php artisan serve
```

Truy cập: `http://localhost:8000`

## 🔧 Development

### Chạy tất cả services cùng lúc

```bash
composer dev
```

Lệnh này sẽ chạy đồng thời:
- Laravel development server
- Queue worker
- Vite development server

### Build production

```bash
npm run build
```

### Chạy tests

```bash
composer test
```

## 📁 Cấu trúc dự án

```
igluckya/
├── app/
│   └── Http/
│       └── Controllers/
│           └── InstagramController.php  # Controller xử lý Instagram
├── resources/
│   ├── css/
│   │   └── app.css                      # Tailwind CSS
│   ├── js/
│   │   └── app.js                       # JavaScript logic
│   └── views/
│       └── instagram-downloader.blade.php  # Main view
├── routes/
│   └── web.php                          # Routes definition
└── public/
    └── build/                           # Compiled assets
```

## 🎯 Cách sử dụng

1. Mở website
2. Nhập link Instagram (post, reel, video, hoặc story)
3. Nhấn "Tìm kiếm nội dung"
4. Xem preview và chọn nội dung cần tải
5. Nhấn "Tải xuống" để download

### Định dạng URL được hỗ trợ

- Posts: `https://www.instagram.com/p/XXXXX/`
- Reels: `https://www.instagram.com/reel/XXXXX/`
- Videos: `https://www.instagram.com/tv/XXXXX/`
- Stories: `https://www.instagram.com/stories/username/XXXXX/`

## 🎨 Tích hợp quảng cáo

Website có sẵn các vị trí quảng cáo:

1. **Header Ad** (728x90): Phía trên header
2. **Sidebar Ad** (160x600): Bên phải (chỉ desktop)
3. **Bottom Ad** (728x90): Dưới kết quả

Để tích hợp quảng cáo thực tế (Google AdSense, etc.), cập nhật các placeholder trong file:
- `resources/views/instagram-downloader.blade.php`

Tìm các div với id:
- `#header-ad`
- `.sidebar-ad` (trong class `fixed right-4`)
- Bottom ad section (sau `#media-container`)

## 🔐 Bảo mật

- ✅ CSRF Protection được kích hoạt
- ✅ Input validation
- ✅ Không lưu trữ content trên server
- ✅ Rate limiting (có thể cấu hình thêm)
- ✅ Error handling toàn diện

## ⚠️ Lưu ý quan trọng

1. **Instagram API**: Project sử dụng multiple methods để fetch content:
   - GraphQL API (working 2025) - Primary method
   - Embed scraping - Fallback method
   - oEmbed API - Limited data
   - Instagram có thể thay đổi structure bất cứ lúc nào
   - Document ID của GraphQL cần update định kỳ

2. **Rate Limiting**:
   - Instagram có rate limiting cho mỗi IP
   - Implement caching để giảm số requests
   - Sử dụng queue cho background processing
   - Cân nhắc proxy rotation cho traffic cao

3. **Legal**:
   - Chỉ sử dụng cho mục đích cá nhân và giáo dục
   - Tôn trọng bản quyền và quyền riêng tư
   - Không sử dụng cho commercial mà không có permission
   - Tuân thủ Instagram Terms of Service

4. **Production Deployment**: Khi deploy production, nên:
   - ✅ Sử dụng RapidAPI hoặc Apify cho reliability cao
   - ✅ Implement Redis caching
   - ✅ Setup queue workers (Laravel Queue)
   - ✅ Enable rate limiting middleware
   - ✅ Sử dụng proxy/VPN rotation
   - ✅ Monitor logs và errors
   - ✅ Setup automated alerts
   - ✅ Regular backup và monitoring

5. **Updating GraphQL Document ID**:
   - GraphQL doc_id thay đổi khoảng 1-3 tháng một lần
   - Theo dõi logs để detect khi method này fail
   - Xem [Troubleshooting](#-troubleshooting) để update doc_id mới

## 🔄 API Endpoints

### POST `/api/instagram/fetch`

Lấy thông tin content từ Instagram URL.

**Request:**
```json
{
  "url": "https://www.instagram.com/p/XXXXX/"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "type": "image",
    "caption": "Caption text",
    "thumbnail": "thumbnail_url",
    "author": "username",
    "media": [
      {
        "type": "image",
        "url": "media_url"
      }
    ]
  }
}
```

### POST `/api/instagram/download`

Download media file.

**Request:**
```json
{
  "url": "media_url",
  "type": "image|video"
}
```

## 🔍 Instagram Fetching Methods

Project sử dụng 3 phương pháp để lấy Instagram content (theo thứ tự ưu tiên):

### Method 1: GraphQL API ⭐ (Recommended 2025)
- **Status**: ✅ Working
- **Ưu điểm**: Không cần authentication, hỗ trợ đầy đủ media types
- **Nhược điểm**: Document ID có thể thay đổi theo thời gian
- **Use case**: Primary method cho production

### Method 2: Embed Scraping
- **Status**: ✅ Working
- **Ưu điểm**: Đơn giản, ít bị rate limit
- **Nhược điểm**: Dữ liệu giới hạn, phụ thuộc vào HTML structure
- **Use case**: Fallback khi GraphQL fail

### Method 3: oEmbed API
- **Status**: ⚠️ Limited
- **Ưu điểm**: Official API, ổn định
- **Nhược điểm**: Chỉ trả về thumbnail và metadata cơ bản
- **Use case**: Last resort fallback

### Method 4: RapidAPI (Optional - Premium)
- **Status**: ✅ High Reliability
- **Ưu điểm**: Độ tin cậy cao, không lo bị block, support tốt
- **Nhược điểm**: Có phí (free tier giới hạn)
- **Use case**: Production với yêu cầu uptime cao

👉 Xem hướng dẫn tích hợp RapidAPI: [RAPIDAPI_INTEGRATION.md](RAPIDAPI_INTEGRATION.md)

## 🐛 Troubleshooting

### Lỗi: "Không thể lấy nội dung từ Instagram"

**Nguyên nhân có thể:**
- Content là private/đã bị xóa
- Instagram đã thay đổi API structure
- IP bị rate limit/block

**Giải pháp:**
1. Kiểm tra URL có hợp lệ và public không
2. Check logs: `storage/logs/laravel.log`
3. Thử với URL khác để test
4. Nếu bị block: đợi vài phút hoặc đổi IP/proxy
5. Cân nhắc dùng RapidAPI cho stability

### Lỗi: GraphQL document ID không hoạt động

**Giải pháp:**
1. Mở Instagram web app trong Chrome
2. Mở DevTools (F12) > Network tab
3. Filter: "graphql"
4. Click vào một post
5. Tìm request POST đến `/api/graphql`
6. Copy `doc_id` từ Form Data
7. Update `$docId` trong `InstagramController.php` line 169

### Lỗi: Rate Limited

**Giải pháp:**
```php
// Thêm delay giữa các requests
sleep(2); // 2 seconds delay

// Hoặc implement caching
Cache::remember('instagram_' . $shortcode, 3600, function() {
    return $this->fetchContent();
});

// Hoặc dùng queue
dispatch(new FetchInstagramJob($url))->delay(now()->addSeconds(5));
```

### Lỗi: CORS khi test từ frontend

**Giải pháp:**
```php
// Add CORS middleware nếu cần
// config/cors.php
'paths' => ['api/*'],
'allowed_origins' => ['*'],
```

### Performance Issues

**Optimization tips:**

1. **Enable caching:**
```php
// In .env
CACHE_DRIVER=redis

// In InstagramController
Cache::remember('ig_' . $shortcode, 3600, fn() => $this->fetch());
```

2. **Use queue for heavy tasks:**
```bash
php artisan queue:work
```

3. **Implement CDN cho media files**

4. **Add rate limiting:**
```php
// routes/web.php
->middleware('throttle:30,1') // 30 requests per minute
```

### Debugging Tips

```php
// Enable debug mode
// .env
APP_DEBUG=true
LOG_LEVEL=debug

// Check logs
tail -f storage/logs/laravel.log

// Test API directly
curl -X POST http://localhost:8000/api/instagram/fetch \
  -H "Content-Type: application/json" \
  -d '{"url":"https://www.instagram.com/p/XXXXX/"}'
```

## 🤝 Đóng góp

Mọi đóng góp đều được chào đón! Vui lòng:

1. Fork repository
2. Tạo branch mới (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Mở Pull Request

## 📝 License

Project này được phân phối dưới [MIT License](LICENSE).

## 👤 Tác giả

Your Name - [@yourusername](https://github.com/yourusername)

## 🙏 Acknowledgments

- [Laravel](https://laravel.com)
- [Tailwind CSS](https://tailwindcss.com)
- [Vite](https://vitejs.dev)

## 📞 Liên hệ & Hỗ trợ

Nếu gặp vấn đề hoặc có câu hỏi, vui lòng:
- Mở Issue trên GitHub
- Email: your.email@example.com

---

**Disclaimer**: Công cụ này chỉ dành cho mục đích giáo dục và sử dụng cá nhân. Vui lòng tôn trọng bản quyền và quyền riêng tư của người dùng Instagram.
