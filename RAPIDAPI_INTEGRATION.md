# RapidAPI Integration Guide (Optional)

Nếu các phương pháp miễn phí không hoạt động ổn định, bạn có thể tích hợp RapidAPI để có độ tin cậy cao hơn.

## Tại sao nên dùng RapidAPI?

- ✅ Độ tin cậy cao (uptime 99.9%)
- ✅ Không bị Instagram block
- ✅ Hỗ trợ download chất lượng cao
- ✅ API ổn định, ít thay đổi
- ✅ Rate limiting tốt
- ⚠️ Có phí (free tier có giới hạn)

## Các API được khuyến nghị

### 1. Instagram Downloader API
- **URL**: https://rapidapi.com/olojeugbaha1/api/instagram-downloader35
- **Free Tier**: 100 requests/tháng
- **Price**: Từ $0/tháng

### 2. Instagram Media Downloader
- **URL**: https://rapidapi.com/arraybobo/api/instagram-media-downloader
- **Features**: Download posts, reels, stories

### 3. Instagram API - Media Downloader
- **URL**: https://rapidapi.com/instagramdp-instagramdp-default/api/instagram-api-media-downloader
- **Features**: HD profile pictures, videos, photos, stories

## Cách tích hợp

### Bước 1: Đăng ký RapidAPI

1. Truy cập https://rapidapi.com/
2. Đăng ký tài khoản miễn phí
3. Subscribe vào API bạn chọn (có free tier)
4. Lấy API Key từ dashboard

### Bước 2: Cấu hình .env

Thêm vào file `.env`:

```env
RAPIDAPI_KEY=your_rapidapi_key_here
RAPIDAPI_HOST=instagram-downloader35.p.rapidapi.com
```

### Bước 3: Cập nhật InstagramController

Thêm method mới vào `app/Http/Controllers/InstagramController.php`:

```php
/**
 * Fetch using RapidAPI (Premium method)
 */
private function fetchUsingRapidAPI($url)
{
    try {
        $apiKey = env('RAPIDAPI_KEY');
        $apiHost = env('RAPIDAPI_HOST', 'instagram-downloader35.p.rapidapi.com');

        if (!$apiKey) {
            Log::warning('RapidAPI key not configured');
            return null;
        }

        $response = Http::withHeaders([
            'X-RapidAPI-Key' => $apiKey,
            'X-RapidAPI-Host' => $apiHost,
        ])->timeout(15)->get('https://' . $apiHost . '/download', [
            'url' => $url
        ]);

        if (!$response->successful()) {
            Log::warning('RapidAPI fetch failed', ['status' => $response->status()]);
            return null;
        }

        $data = $response->json();

        return $this->parseRapidAPIData($data);

    } catch (\Exception $e) {
        Log::error('RapidAPI fetch error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Parse RapidAPI response
 */
private function parseRapidAPIData($data)
{
    // Parse theo format của API bạn chọn
    // Mỗi API có response format khác nhau

    // Example for instagram-downloader35:
    if (isset($data['media'])) {
        $media = [];

        foreach ($data['media'] as $item) {
            $media[] = [
                'type' => $item['type'] ?? 'image',
                'url' => $item['url'] ?? null,
                'thumbnail' => $item['thumbnail'] ?? null,
            ];
        }

        return [
            'type' => count($media) > 1 ? 'carousel' : ($media[0]['type'] ?? 'image'),
            'caption' => $data['caption'] ?? '',
            'thumbnail' => $data['thumbnail'] ?? ($media[0]['thumbnail'] ?? null),
            'author' => $data['username'] ?? 'Unknown',
            'media' => $media,
            'shortcode' => $data['shortcode'] ?? null,
        ];
    }

    return null;
}
```

### Bước 4: Cập nhật fetch flow

Trong method `fetchInstagramContent`, thêm RapidAPI làm method đầu tiên (nếu đã config):

```php
private function fetchInstagramContent($shortcode, $fullUrl)
{
    // Method 0: Try RapidAPI if configured (Premium)
    if (env('RAPIDAPI_KEY')) {
        $content = $this->fetchUsingRapidAPI($fullUrl);
        if ($content) {
            return $content;
        }
    }

    // Method 1: Try GraphQL API (Working 2025)
    $content = $this->fetchUsingGraphQL($shortcode);
    if ($content) {
        return $content;
    }

    // ... rest of methods
}
```

## Testing

Test API integration:

```bash
# Set your API key
export RAPIDAPI_KEY="your_key_here"

# Test với một Instagram URL
curl -X POST http://localhost:8000/api/instagram/fetch \
  -H "Content-Type: application/json" \
  -d '{"url":"https://www.instagram.com/p/XXXXX/"}'
```

## So sánh các API

| API | Free Tier | Giá/tháng | Tính năng | Độ tin cậy |
|-----|-----------|-----------|-----------|------------|
| Instagram Downloader35 | 100 req | $0-$20 | Posts, Reels, Stories | ⭐⭐⭐⭐⭐ |
| Instagram Media Downloader | 50 req | $0-$15 | Posts, Reels | ⭐⭐⭐⭐ |
| Apify Instagram Scraper | 1000 credits | Pay-as-go | Full scraping | ⭐⭐⭐⭐⭐ |
| GraphQL (Free) | Unlimited* | $0 | Public posts | ⭐⭐⭐ |
| oEmbed (Free) | Unlimited | $0 | Limited data | ⭐⭐ |

*Có thể bị rate limit hoặc block nếu abuse

## Best Practices

### 1. Caching
Implement caching để giảm số lượng API calls:

```php
use Illuminate\Support\Facades\Cache;

private function fetchInstagramContent($shortcode, $fullUrl)
{
    // Check cache first
    $cacheKey = 'instagram_' . $shortcode;

    return Cache::remember($cacheKey, 3600, function () use ($shortcode, $fullUrl) {
        // Try API methods...
        return $this->fetchUsingRapidAPI($fullUrl)
            ?? $this->fetchUsingGraphQL($shortcode)
            ?? $this->fetchUsingEmbedScraping($fullUrl)
            ?? $this->fetchUsingOEmbed($shortcode);
    });
}
```

### 2. Rate Limiting
Implement rate limiting cho users:

```php
// In routes/web.php
Route::prefix('api/instagram')->middleware('throttle:10,1')->group(function () {
    Route::post('/fetch', [InstagramController::class, 'fetch']);
    Route::post('/download', [InstagramController::class, 'download']);
});
```

### 3. Monitoring
Log API usage để track costs:

```php
private function fetchUsingRapidAPI($url)
{
    $result = // ... API call

    if ($result) {
        Log::info('RapidAPI used', [
            'url' => $url,
            'timestamp' => now(),
        ]);
    }

    return $result;
}
```

## Troubleshooting

### Error: "API key invalid"
- Kiểm tra lại API key trong .env
- Đảm bảo đã subscribe vào API trên RapidAPI

### Error: "Rate limit exceeded"
- Upgrade plan trên RapidAPI
- Implement caching
- Sử dụng fallback methods

### Error: "Request timeout"
- Tăng timeout duration
- Check RapidAPI service status
- Fallback to free methods

## Tổng kết

- **Development**: Dùng free methods (GraphQL, Embed scraping)
- **Production nhỏ**: Free methods + caching
- **Production lớn**: RapidAPI hoặc Apify cho stability
- **Enterprise**: Custom solution với Instagram Graph API (cần app review)

## Resources

- RapidAPI Hub: https://rapidapi.com/hub
- Instagram APIs Collection: https://rapidapi.com/collection/instagram-api
- Apify Instagram Scraper: https://apify.com/apilabs/instagram-downloader
- Instagram Graph API Docs: https://developers.facebook.com/docs/instagram-api/
