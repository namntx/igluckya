# Testing Guide

## Quick Test

### 1. Test với Instagram Public Post

Thử với các URL công khai này:

```bash
# Test 1: Recent public post
https://www.instagram.com/p/DD7dC-TyKNu/

# Test 2: Another public post
https://www.instagram.com/p/DBkfVzJyWjh/

# Test 3: Reel
https://www.instagram.com/reel/C_example/
```

### 2. Check Logs

Xem logs để debug:

```bash
# Watch logs in real-time
tail -f storage/logs/laravel.log

# Or check recent errors
tail -100 storage/logs/laravel.log
```

### 3. Test API Directly

```bash
# Test fetch endpoint
curl -X POST http://localhost:8000/api/instagram/fetch \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: your-token" \
  -d '{"url":"https://www.instagram.com/p/DD7dC-TyKNu/"}'
```

## Debug Steps

### Step 1: Enable Debug Mode

In `.env`:
```env
APP_DEBUG=true
LOG_LEVEL=debug
```

### Step 2: Clear Cache

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Step 3: Check What Method Works

Logs sẽ cho biết method nào đang được thử:
- "Trying page scraping" - Fetching main page
- "Found JSON-LD data" - Success with JSON-LD
- "Found window._sharedData" - Success with shared data
- "Trying embed page" - Trying embed variant
- "Trying oEmbed API" - Fallback to oEmbed

### Step 4: Common Issues

**Issue**: "All methods failed"
- Check if URL is public
- Try different URL
- Check if Instagram changed structure
- Consider RapidAPI for production

**Issue**: "Page scraping failed - HTTP error 429"
- Rate limited by Instagram
- Wait a few minutes
- Use different IP/proxy
- Implement caching

**Issue**: "Found JSON-LD but no media URL"
- Instagram might have changed HTML structure
- Check logs for raw JSON data
- May need to update parsing logic

## Manual Testing

### Test Page Scraping

```php
// Add to routes/web.php for testing
Route::get('/test-ig/{shortcode}', function($shortcode) {
    $url = "https://www.instagram.com/p/{$shortcode}/";

    $response = Http::withHeaders([
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    ])->get($url);

    $html = $response->body();

    // Find JSON-LD
    preg_match('/<script type="application\/ld\+json"[^>]*>(.*?)<\/script>/s', $html, $matches);

    if ($matches) {
        return response()->json(json_decode($matches[1], true));
    }

    return 'No JSON-LD found';
});
```

Access: `http://localhost:8000/test-ig/DD7dC-TyKNu`

### Test oEmbed

```bash
curl "https://api.instagram.com/oembed/?url=https://www.instagram.com/p/DD7dC-TyKNu/"
```

## Expected Behavior

### Success Response

```json
{
  "success": true,
  "data": {
    "type": "image",
    "caption": "Post caption...",
    "thumbnail": "https://...",
    "author": "username",
    "media": [
      {
        "type": "image",
        "url": "https://..."
      }
    ],
    "shortcode": "DD7dC-TyKNu"
  }
}
```

### Error Response

```json
{
  "success": false,
  "message": "Error message..."
}
```

## Updating When Instagram Changes

If methods stop working:

1. **Check logs first** - See which method failed
2. **Test manually** - Use curl or browser
3. **Update regex patterns** - If HTML structure changed
4. **Check JSON structure** - May need to update parsing

Example: Update JSON-LD regex if format changes:

```php
// Old
preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', ...)

// New (if attributes change)
preg_match('/<script[^>]*type="application\/ld\+json"[^>]*>(.*?)<\/script>/s', ...)
```

## Production Recommendations

For production with high reliability:

1. **Use RapidAPI** - See RAPIDAPI_INTEGRATION.md
2. **Implement caching**:
```php
use Illuminate\Support\Facades\Cache;

$content = Cache::remember('ig_' . $shortcode, 3600, function() use ($shortcode) {
    return $this->fetchInstagramContent($shortcode);
});
```

3. **Add rate limiting**:
```php
// In routes/web.php
Route::middleware('throttle:30,1')->group(function() {
    // API routes
});
```

4. **Queue processing** for heavy tasks:
```bash
php artisan queue:work
```

## Troubleshooting Checklist

- [ ] URL is valid and public
- [ ] Not rate limited (429 error)
- [ ] Logs show detailed error messages
- [ ] Tried multiple different URLs
- [ ] Cache is cleared
- [ ] Debug mode enabled
- [ ] PHP version >= 8.2
- [ ] Guzzle/HTTP client working
- [ ] Internet connection stable
- [ ] Instagram not blocking server IP

## Need Help?

1. Check logs: `storage/logs/laravel.log`
2. Enable debug mode
3. Test with known working URL
4. Check README.md troubleshooting section
5. Consider RapidAPI for production reliability
