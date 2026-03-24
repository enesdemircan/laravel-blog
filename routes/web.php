<?php

use Ceniver\Blog\Http\Controllers\AiDiscoveryController;
use Ceniver\Blog\Http\Controllers\BlogController;
use Ceniver\Blog\Http\Controllers\SetupController;
use Ceniver\Blog\Http\Middleware\EnsureBlogConfigured;
use Illuminate\Support\Facades\Route;

$middleware = config('blog.middleware', ['web']);
$prefix = config('blog.route_prefix', '');

Route::middleware($middleware)->prefix($prefix)->group(function () {

    // AI & SEO Discovery dosyaları
    Route::get('/llms.txt',      [AiDiscoveryController::class, 'llmsTxt']);
    Route::get('/llms-full.txt', [AiDiscoveryController::class, 'llmsFullTxt']);
    Route::get('/feed.xml',      [AiDiscoveryController::class, 'feed']);
    Route::get('/robots.txt',    [AiDiscoveryController::class, 'robots']);

    // Kurulum wizard
    Route::get('/blog/setup',  [SetupController::class, 'index'])->name('blog.setup');
    Route::post('/blog/setup', [SetupController::class, 'store'])->name('blog.setup.store');

    // Blog rotaları — kurulum kontrolü ile
    Route::prefix('{locale}')->where(['locale' => '[a-z]{2}'])->middleware(EnsureBlogConfigured::class)->group(function () {
        Route::get('/blog',                 [BlogController::class, 'index'])->name('blog.index');
        Route::get('/blog/kategori/{slug}', [BlogController::class, 'category'])->name('blog.category');
        Route::get('/blog/{slug}',          [BlogController::class, 'show'])->name('blog.show');
    });
});
