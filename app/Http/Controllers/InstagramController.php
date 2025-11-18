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

            // Fetch Instagram content
            $content = $this->fetchInstagramContent($shortcode);

            if (!$content) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể lấy nội dung từ Instagram. Vui lòng thử lại.'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $content
            ]);

        } catch (\Exception $e) {
            Log::error('Instagram fetch error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi xử lý yêu cầu. Vui lòng thử lại.'
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
     * Fetch Instagram content using Instagram's public API
     * Note: This is a simplified version. In production, you might want to use:
     * - RapidAPI Instagram API
     * - Apify Instagram Scraper
     * - Or similar services
     */
    private function fetchInstagramContent($shortcode)
    {
        try {
            // Using Instagram's oEmbed API (public, no auth required)
            $url = "https://www.instagram.com/p/{$shortcode}/?__a=1&__d=dis";

            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
                'Sec-Fetch-Dest' => 'document',
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-Site' => 'none',
                'Cache-Control' => 'max-age=0',
            ])->timeout(30)->get($url);

            if (!$response->successful()) {
                // Fallback to oEmbed API
                return $this->fetchUsingOEmbed($shortcode);
            }

            $data = $response->json();

            if (!$data || !isset($data['items']) || empty($data['items'])) {
                return $this->fetchUsingOEmbed($shortcode);
            }

            $item = $data['items'][0];

            return $this->parseInstagramData($item);

        } catch (\Exception $e) {
            Log::error('Instagram content fetch error: ' . $e->getMessage());
            // Fallback to oEmbed
            return $this->fetchUsingOEmbed($shortcode);
        }
    }

    /**
     * Fallback method using Instagram oEmbed API
     */
    private function fetchUsingOEmbed($shortcode)
    {
        try {
            $url = "https://api.instagram.com/oembed/?url=https://www.instagram.com/p/{$shortcode}/";

            $response = Http::timeout(10)->get($url);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();

            return [
                'type' => 'post',
                'thumbnail' => $data['thumbnail_url'] ?? null,
                'title' => $data['title'] ?? 'Instagram Post',
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
            Log::error('oEmbed fetch error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Parse Instagram data into a standardized format
     */
    private function parseInstagramData($item)
    {
        $media = [];
        $type = 'post';

        // Determine content type
        if (isset($item['video_versions']) && !empty($item['video_versions'])) {
            $type = 'video';
            $media[] = [
                'type' => 'video',
                'url' => $item['video_versions'][0]['url'],
                'thumbnail' => $item['image_versions2']['candidates'][0]['url'] ?? null,
            ];
        } elseif (isset($item['carousel_media']) && !empty($item['carousel_media'])) {
            $type = 'carousel';
            foreach ($item['carousel_media'] as $carouselItem) {
                if (isset($carouselItem['video_versions'])) {
                    $media[] = [
                        'type' => 'video',
                        'url' => $carouselItem['video_versions'][0]['url'],
                        'thumbnail' => $carouselItem['image_versions2']['candidates'][0]['url'] ?? null,
                    ];
                } else {
                    $media[] = [
                        'type' => 'image',
                        'url' => $carouselItem['image_versions2']['candidates'][0]['url'] ?? null,
                    ];
                }
            }
        } else {
            $type = 'image';
            $media[] = [
                'type' => 'image',
                'url' => $item['image_versions2']['candidates'][0]['url'] ?? null,
            ];
        }

        return [
            'type' => $type,
            'caption' => $item['caption']['text'] ?? '',
            'thumbnail' => $item['image_versions2']['candidates'][0]['url'] ?? null,
            'author' => $item['user']['username'] ?? 'Unknown',
            'media' => $media,
            'shortcode' => $item['code'] ?? null,
        ];
    }
}
