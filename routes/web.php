<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InstagramController;

Route::get('/', function () {
    return view('instagram-downloader');
});

// Placeholder image route
Route::get('/placeholder.jpg', function () {
    $svg = '<svg width="400" height="400" xmlns="http://www.w3.org/2000/svg"><rect width="400" height="400" fill="#f3f4f6"/><text x="50%" y="50%" font-family="Arial" font-size="20" fill="#9ca3af" text-anchor="middle" dominant-baseline="middle">No Image</text></svg>';
    return response($svg)->header('Content-Type', 'image/svg+xml');
})->name('placeholder');

// API Routes for Instagram
Route::prefix('api/instagram')->group(function () {
    Route::post('/fetch', [InstagramController::class, 'fetch'])->name('instagram.fetch');
    Route::post('/download', [InstagramController::class, 'download'])->name('instagram.download');
});
