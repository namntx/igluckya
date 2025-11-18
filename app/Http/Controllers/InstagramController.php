<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramController extends Controller
{
    /**
     * Fetch Instagram content information
     */
    public function fetch(Request $request)
    {
        $request->validate([
            'url' => 'required|url'
        ]);

        $url = $request->input('url');

        try {
            // Extract shortcode from URL
            $shortcode = $this->extractShortcode($url);

            if (!$shortcode) {
                return response()->json([
                    'success' => false,
                    'message' => 'URL Instagram không hợp lệ'
                ], 400);
            }

            // Fetch Instagram content using multiple methods
            $content = $this->fetchInstagramContent($shortcode, $url);

            if (!$content) {
                Log::error('All methods failed for shortcode: ' . $shortcode);
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể lấy nội dung từ Instagram. Vui lòng thử lại sau hoặc sử dụng URL khác.'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $content
            ]);

        } catch (\Exception $e) {
            Log::error('Instagram fetch error: ' . $e->getMessage(), [
                'url' => $url ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi xử lý yêu cầu. Vui lòng thử lại sau.'
            ], 500);
        }
    }

    /**
     * Download Instagram content
     */
    public function download(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
            'type' => 'required|in:image,video'
        ]);

        try {
            $url = $request->input('url');
            $type = $request->input('type');

            // Fetch the content from Instagram
            $response = Http::timeout(30)->get($url);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể tải xuống nội dung'
                ], 400);
            }

            // Determine content type and extension
            $contentType = $type === 'video' ? 'video/mp4' : 'image/jpeg';
            $extension = $type === 'video' ? 'mp4' : 'jpg';
            $filename = 'instagram_' . time() . '.' . $extension;

            // Stream the content directly to the user
            return response($response->body(), 200)
                ->header('Content-Type', $contentType)
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');

        } catch (\Exception $e) {
            Log::error('Instagram download error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi tải xuống'
            ], 500);
        }
    }

    /**
     * Extract shortcode from Instagram URL
     */
    private function extractShortcode($url)
    {
        // Patterns for different Instagram URL formats
        $patterns = [
            '/instagram\.com\/p\/([A-Za-z0-9_-]+)/',
            '/instagram\.com\/reel\/([A-Za-z0-9_-]+)/',
            '/instagram\.com\/reels\/([A-Za-z0-9_-]+)/',
            '/instagram\.com\/tv\/([A-Za-z0-9_-]+)/',
            '/instagram\.com\/stories\/[^\/]+\/([0-9]+)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Fetch Instagram content using multiple methods
     *
     * Updated 2025: Simplified approach with working methods
     * Methods tried in order:
     * 1. Page scraping with JSON-LD (most reliable)
     * 2. oEmbed API (limited but official)
     *
     * For production reliability, consider RapidAPI: See RAPIDAPI_INTEGRATION.md
     */
    private function fetchInstagramContent($shortcode, $fullUrl)
    {
        Log::info('Fetching Instagram content', ['shortcode' => $shortcode]);

        // Method 1: Page scraping (most reliable)
        $content = $this->fetchUsingPageScraping($fullUrl, $shortcode);
        if ($content) {
            Log::info('Success with page scraping method');
            return $content;
        }

        // Method 2: oEmbed API (limited data but reliable)
        $content = $this->fetchUsingOEmbed($shortcode);
        if ($content) {
            Log::info('Success with oEmbed method');
            return $content;
        }

        return null;
    }

    /**
     * Method 1: Fetch by scraping Instagram page
     * This method fetches the actual Instagram page and extracts JSON-LD structured data
     */
    private function fetchUsingPageScraping($url, $shortcode)
    {
        try {
            // Clean URL - ensure it's the standard format
            $cleanUrl = "https://www.instagram.com/p/{$shortcode}/";

            Log::info('Trying page scraping', ['url' => $cleanUrl]);

            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Accept-Encoding' => 'gzip, deflate, br',
                'DNT' => '1',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
                'Sec-Fetch-Dest' => 'document',
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-Site' => 'none',
                'Cache-Control' => 'max-age=0',
            ])->timeout(20)->get($cleanUrl);

            if (!$response->successful()) {
                Log::warning('Page scraping failed - HTTP error', ['status' => $response->status()]);
                return null;
            }

            $html = $response->body();

            // Method 1a: Extract JSON-LD data (most common in embed pages)
            if (preg_match('/<script type="application\/ld\+json"[^>]*>(.*?)<\/script>/s', $html, $matches)) {
                $jsonData = json_decode($matches[1], true);

                if ($jsonData && isset($jsonData['@type'])) {
                    Log::info('Found JSON-LD data', ['type' => $jsonData['@type']]);
                    return $this->parseJsonLdData($jsonData, $html, $shortcode);
                }
            }

            // Method 1b: Try to find window._sharedData
            if (preg_match('/window\._sharedData\s*=\s*({.+?});<\/script>/s', $html, $matches)) {
                $sharedData = json_decode($matches[1], true);

                if ($sharedData && isset($sharedData['entry_data'])) {
                    Log::info('Found window._sharedData');
                    return $this->parseSharedData($sharedData, $shortcode);
                }
            }

            // Method 1c: Try embed page variant
            return $this->fetchUsingEmbedPage($shortcode);

        } catch (\Exception $e) {
            Log::error('Page scraping error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Method 1c: Fetch using embed page (more reliable for some posts)
     */
    private function fetchUsingEmbedPage($shortcode)
    {
        try {
            $embedUrl = "https://www.instagram.com/p/{$shortcode}/embed/captioned/";

            Log::info('Trying embed page', ['url' => $embedUrl]);

            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ])->timeout(15)->get($embedUrl);

            if (!$response->successful()) {
                Log::warning('Embed page failed', ['status' => $response->status()]);
                return null;
            }

            $html = $response->body();

            // Extract JSON-LD from embed page
            if (preg_match('/<script type="application\/ld\+json"[^>]*>(.*?)<\/script>/s', $html, $matches)) {
                $jsonData = json_decode($matches[1], true);

                if ($jsonData && isset($jsonData['@type'])) {
                    Log::info('Found JSON-LD in embed page');
                    return $this->parseJsonLdData($jsonData, $html, $shortcode);
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Embed page error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Method 2: Fallback using Instagram oEmbed API (limited data)
     */
    private function fetchUsingOEmbed($shortcode)
    {
        try {
            $url = "https://api.instagram.com/oembed/?url=https://www.instagram.com/p/{$shortcode}/";

            Log::info('Trying oEmbed API', ['url' => $url]);

            $response = Http::timeout(10)->get($url);

            if (!$response->successful()) {
                Log::warning('oEmbed failed', ['status' => $response->status()]);
                return null;
            }

            $data = $response->json();

            if (!$data) {
                return null;
            }

            return [
                'type' => 'post',
                'caption' => $data['title'] ?? 'Instagram Post',
                'thumbnail' => $data['thumbnail_url'] ?? null,
                'author' => $data['author_name'] ?? 'Unknown',
                'media' => [
                    [
                        'type' => 'image',
                        'url' => $data['thumbnail_url'] ?? null,
                    ]
                ],
                'shortcode' => $shortcode,
            ];

        } catch (\Exception $e) {
            Log::error('oEmbed error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Parse JSON-LD structured data
     */
    private function parseJsonLdData($jsonData, $html, $shortcode)
    {
        try {
            $media = [];
            $type = 'image';

            // Extract media URLs from HTML since JSON-LD might not have them
            $videoUrl = null;
            $imageUrl = null;

            // Try to find video URL in HTML
            if (preg_match('/"video_url":"([^"]+)"/', $html, $matches)) {
                $videoUrl = json_decode('"' . $matches[1] . '"'); // Decode escaped string
                $type = 'video';
            }

            // Try to find display URL in HTML
            if (preg_match('/"display_url":"([^"]+)"/', $html, $matches)) {
                $imageUrl = json_decode('"' . $matches[1] . '"');
            }

            // Construct media array
            if ($videoUrl) {
                $media[] = [
                    'type' => 'video',
                    'url' => $videoUrl,
                    'thumbnail' => $imageUrl ?? $jsonData['thumbnailUrl'] ?? null,
                ];
            } elseif ($imageUrl) {
                $media[] = [
                    'type' => 'image',
                    'url' => $imageUrl,
                ];
            } else {
                // Fallback to JSON-LD data
                $media[] = [
                    'type' => 'image',
                    'url' => $jsonData['thumbnailUrl'] ?? $jsonData['image'] ?? null,
                ];
            }

            // Get caption
            $caption = '';
            if (isset($jsonData['caption'])) {
                $caption = $jsonData['caption'];
            } elseif (isset($jsonData['articleBody'])) {
                $caption = $jsonData['articleBody'];
            } elseif (isset($jsonData['headline'])) {
                $caption = $jsonData['headline'];
            }

            // Get author
            $author = 'Unknown';
            if (isset($jsonData['author']['name'])) {
                $author = $jsonData['author']['name'];
            } elseif (isset($jsonData['author']['alternateName'])) {
                $author = $jsonData['author']['alternateName'];
            } elseif (isset($jsonData['author'])) {
                $author = is_string($jsonData['author']) ? $jsonData['author'] : 'Unknown';
            }

            return [
                'type' => $type,
                'caption' => $caption,
                'thumbnail' => $jsonData['thumbnailUrl'] ?? $jsonData['image'] ?? $imageUrl ?? null,
                'author' => $author,
                'media' => $media,
                'shortcode' => $shortcode,
            ];

        } catch (\Exception $e) {
            Log::error('Error parsing JSON-LD data: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Parse window._sharedData (older Instagram format)
     */
    private function parseSharedData($sharedData, $shortcode)
    {
        try {
            // Navigate through the shared data structure
            $postData = null;

            // Try PostPage first
            if (isset($sharedData['entry_data']['PostPage'][0]['graphql']['shortcode_media'])) {
                $postData = $sharedData['entry_data']['PostPage'][0]['graphql']['shortcode_media'];
            }

            if (!$postData) {
                return null;
            }

            $media = [];
            $type = 'post';

            // Check if it's a video
            if (isset($postData['is_video']) && $postData['is_video'] === true) {
                $type = 'video';
                $media[] = [
                    'type' => 'video',
                    'url' => $postData['video_url'] ?? null,
                    'thumbnail' => $postData['display_url'] ?? null,
                ];
            }
            // Check if it's a carousel
            elseif (isset($postData['edge_sidecar_to_children']['edges'])) {
                $type = 'carousel';
                foreach ($postData['edge_sidecar_to_children']['edges'] as $edge) {
                    $node = $edge['node'];
                    if (isset($node['is_video']) && $node['is_video'] === true) {
                        $media[] = [
                            'type' => 'video',
                            'url' => $node['video_url'] ?? null,
                            'thumbnail' => $node['display_url'] ?? null,
                        ];
                    } else {
                        $media[] = [
                            'type' => 'image',
                            'url' => $node['display_url'] ?? null,
                        ];
                    }
                }
            }
            // Single image
            else {
                $type = 'image';
                $media[] = [
                    'type' => 'image',
                    'url' => $postData['display_url'] ?? null,
                ];
            }

            // Get caption
            $caption = '';
            if (isset($postData['edge_media_to_caption']['edges'][0]['node']['text'])) {
                $caption = $postData['edge_media_to_caption']['edges'][0]['node']['text'];
            }

            return [
                'type' => $type,
                'caption' => $caption,
                'thumbnail' => $postData['display_url'] ?? null,
                'author' => $postData['owner']['username'] ?? 'Unknown',
                'media' => $media,
                'shortcode' => $shortcode,
            ];

        } catch (\Exception $e) {
            Log::error('Error parsing shared data: ' . $e->getMessage());
            return null;
        }
    }
}
