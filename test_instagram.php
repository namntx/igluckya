<?php

// Test script to debug Instagram fetching
// Run: php test_instagram.php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Test URL - replace with your test URL
$testUrl = 'https://www.instagram.com/p/DD7dC-TyKNu/'; // Example public post

echo "Testing Instagram fetching methods...\n\n";

// Extract shortcode
preg_match('/instagram\.com\/(?:p|reel|reels)\/([A-Za-z0-9_-]+)/', $testUrl, $matches);
$shortcode = $matches[1] ?? null;

if (!$shortcode) {
    die("Invalid URL\n");
}

echo "Shortcode: $shortcode\n\n";

// Method 1: Try simple page fetch to get shared data
echo "=== Method 1: Page fetch with shared data ===\n";
try {
    $response = Http::withHeaders([
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language' => 'en-US,en;q=0.9',
    ])->timeout(15)->get("https://www.instagram.com/p/{$shortcode}/");

    if ($response->successful()) {
        $html = $response->body();

        // Try to find shared data in script tags
        if (preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $jsonMatches)) {
            $data = json_decode($jsonMatches[1], true);
            echo "✓ Found JSON-LD data\n";
            echo "Type: " . ($data['@type'] ?? 'unknown') . "\n";
            echo "Author: " . ($data['author']['name'] ?? 'unknown') . "\n";
            print_r($data);
        }

        // Try to find window._sharedData
        if (preg_match('/window\._sharedData\s*=\s*({.+?});/s', $html, $sharedMatches)) {
            echo "✓ Found window._sharedData\n";
            $sharedData = json_decode($sharedMatches[1], true);
            if ($sharedData) {
                echo "Has entry_data: " . (isset($sharedData['entry_data']) ? 'Yes' : 'No') . "\n";
            }
        }

        echo "\n";
    } else {
        echo "✗ Failed: " . $response->status() . "\n\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Method 2: oEmbed API
echo "=== Method 2: oEmbed API ===\n";
try {
    $response = Http::timeout(10)->get("https://api.instagram.com/oembed/?url=https://www.instagram.com/p/{$shortcode}/");

    if ($response->successful()) {
        $data = $response->json();
        echo "✓ Success!\n";
        echo "Title: " . ($data['title'] ?? 'N/A') . "\n";
        echo "Author: " . ($data['author_name'] ?? 'N/A') . "\n";
        echo "Thumbnail: " . (isset($data['thumbnail_url']) ? 'Yes' : 'No') . "\n";
        print_r($data);
    } else {
        echo "✗ Failed: " . $response->status() . "\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Method 3: Embed page
echo "=== Method 3: Embed page ===\n";
try {
    $response = Http::withHeaders([
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    ])->timeout(15)->get("https://www.instagram.com/p/{$shortcode}/embed/captioned/");

    if ($response->successful()) {
        $html = $response->body();
        echo "✓ Page loaded\n";

        // Try JSON-LD
        if (preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches)) {
            $data = json_decode($matches[1], true);
            echo "✓ Found JSON-LD\n";
            print_r($data);
        }

        // Try to find video or image URLs
        if (preg_match('/"video_url":"([^"]+)"/', $html, $matches)) {
            echo "✓ Found video_url: " . json_decode('"' . $matches[1] . '"') . "\n";
        }
        if (preg_match('/"display_url":"([^"]+)"/', $html, $matches)) {
            echo "✓ Found display_url: " . json_decode('"' . $matches[1] . '"') . "\n";
        }
    } else {
        echo "✗ Failed: " . $response->status() . "\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

echo "Test completed!\n";
