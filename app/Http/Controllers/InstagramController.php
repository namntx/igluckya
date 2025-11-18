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
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể lấy nội dung từ Instagram. Đây có thể là nội dung riêng tư hoặc đã bị xóa.'
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
     * Methods tried in order:
     * 1. GraphQL API (recommended 2025)
     * 2. Embed scraping
     * 3. oEmbed API (limited data)
     *
     * For production with high reliability, consider:
     * - RapidAPI Instagram API: https://rapidapi.com/instagram-downloader
     * - Apify Instagram Scraper: https://apify.com/apilabs/instagram-downloader
     */
    private function fetchInstagramContent($shortcode, $fullUrl)
    {
        // Method 1: Try GraphQL API (Working 2025)
        $content = $this->fetchUsingGraphQL($shortcode);
        if ($content) {
            return $content;
        }

        // Method 2: Try embed scraping
        $content = $this->fetchUsingEmbedScraping($fullUrl);
        if ($content) {
            return $content;
        }

        // Method 3: Fallback to oEmbed (limited data only)
        return $this->fetchUsingOEmbed($shortcode);
    }

    /**
     * Method 1: Fetch using Instagram GraphQL API (Recommended 2025)
     * This is the most reliable method as of 2025
     */
    private function fetchUsingGraphQL($shortcode)
    {
        try {
            // Instagram GraphQL endpoint
            $graphqlUrl = 'https://www.instagram.com/api/graphql';

            // Common GraphQL document IDs for posts (may need updating periodically)
            // These are obtained through reverse engineering Instagram's web app
            $docId = '8845758582119845'; // This may change over time

            $variables = json_encode([
                'shortcode' => $shortcode,
                'child_comment_count' => 3,
                'fetch_comment_count' => 40,
                'parent_comment_count' => 24,
                'has_threaded_comments' => true
            ]);

            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                'Accept' => '*/*',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Accept-Encoding' => 'gzip, deflate, br',
                'X-IG-App-ID' => '936619743392459', // Instagram web app ID
                'X-ASBD-ID' => '129477',
                'X-IG-WWW-Claim' => '0',
                'Origin' => 'https://www.instagram.com',
                'Referer' => "https://www.instagram.com/p/{$shortcode}/",
                'Sec-Fetch-Dest' => 'empty',
                'Sec-Fetch-Mode' => 'cors',
                'Sec-Fetch-Site' => 'same-origin',
            ])->asForm()->post($graphqlUrl, [
                'doc_id' => $docId,
                'variables' => $variables,
            ]);

            if (!$response->successful()) {
                Log::warning('GraphQL fetch failed', ['status' => $response->status()]);
                return null;
            }

            $data = $response->json();

            // Parse GraphQL response
            if (isset($data['data']['xdt_shortcode_media'])) {
                return $this->parseGraphQLData($data['data']['xdt_shortcode_media']);
            } elseif (isset($data['data']['shortcode_media'])) {
                return $this->parseGraphQLData($data['data']['shortcode_media']);
            }

            return null;

        } catch (\Exception $e) {
            Log::error('GraphQL fetch error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Method 2: Fetch by scraping embed page
     */
    private function fetchUsingEmbedScraping($url)
    {
        try {
            $embedUrl = $url . 'embed/captioned/';

            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            ])->timeout(15)->get($embedUrl);

            if (!$response->successful()) {
                return null;
            }

            $html = $response->body();

            // Extract JSON data from script tag
            if (preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches)) {
                $jsonData = json_decode($matches[1], true);

                if ($jsonData && isset($jsonData['@type'])) {
                    return $this->parseEmbedData($jsonData, $html);
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Embed scraping error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Method 3: Fallback using Instagram oEmbed API (limited data)
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
            Log::error('oEmbed fetch error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Parse GraphQL response data
     */
    private function parseGraphQLData($item)
    {
        $media = [];
        $type = 'post';

        try {
            // Check if it's a video
            if (isset($item['is_video']) && $item['is_video'] === true) {
                $type = 'video';
                $media[] = [
                    'type' => 'video',
                    'url' => $item['video_url'] ?? null,
                    'thumbnail' => $item['display_url'] ?? $item['thumbnail_src'] ?? null,
                ];
            }
            // Check if it's a carousel (multiple images/videos)
            elseif (isset($item['edge_sidecar_to_children']['edges']) && !empty($item['edge_sidecar_to_children']['edges'])) {
                $type = 'carousel';
                foreach ($item['edge_sidecar_to_children']['edges'] as $edge) {
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
                    'url' => $item['display_url'] ?? $item['thumbnail_src'] ?? null,
                ];
            }

            // Get caption
            $caption = '';
            if (isset($item['edge_media_to_caption']['edges'][0]['node']['text'])) {
                $caption = $item['edge_media_to_caption']['edges'][0]['node']['text'];
            } elseif (isset($item['caption'])) {
                $caption = is_array($item['caption']) ? ($item['caption']['text'] ?? '') : $item['caption'];
            }

            // Get author
            $author = $item['owner']['username'] ?? 'Unknown';

            // Get thumbnail
            $thumbnail = $item['display_url'] ?? $item['thumbnail_src'] ?? null;

            return [
                'type' => $type,
                'caption' => $caption,
                'thumbnail' => $thumbnail,
                'author' => $author,
                'media' => $media,
                'shortcode' => $item['shortcode'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('Error parsing GraphQL data: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Parse embed page data
     */
    private function parseEmbedData($jsonData, $html)
    {
        try {
            $media = [];
            $type = 'image';

            // Try to extract media URLs from HTML
            if (preg_match('/"video_url":"([^"]+)"/', $html, $matches)) {
                $type = 'video';
                $videoUrl = json_decode('"' . $matches[1] . '"');
                $media[] = [
                    'type' => 'video',
                    'url' => $videoUrl,
                    'thumbnail' => $jsonData['thumbnailUrl'] ?? null,
                ];
            } elseif (preg_match('/"display_url":"([^"]+)"/', $html, $matches)) {
                $imageUrl = json_decode('"' . $matches[1] . '"');
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

            return [
                'type' => $type,
                'caption' => $jsonData['caption'] ?? $jsonData['articleBody'] ?? '',
                'thumbnail' => $jsonData['thumbnailUrl'] ?? $jsonData['image'] ?? null,
                'author' => $jsonData['author']['name'] ?? $jsonData['author']['alternateName'] ?? 'Unknown',
                'media' => $media,
                'shortcode' => null,
            ];

        } catch (\Exception $e) {
            Log::error('Error parsing embed data: ' . $e->getMessage());
            return null;
        }
    }
}
