<?php

use Ceniver\Blog\Http\Controllers\Api\ArticleReceiverController;
use Ceniver\Blog\Http\Controllers\Api\CategoryReceiverController;
use Ceniver\Blog\Http\Controllers\Api\HealthStatusController;
use Ceniver\Blog\Http\Controllers\Api\PageListController;
use Ceniver\Blog\Http\Controllers\Api\PageSeoController;
use Ceniver\Blog\Http\Controllers\Api\RedirectsController;
use Ceniver\Blog\Http\Controllers\Api\SeoConfigController;
use Ceniver\Blog\Http\Controllers\Api\SiteConfigController;
use Ceniver\Blog\Http\Controllers\Api\SitemapController;
use Ceniver\Blog\Http\Middleware\ApiKeyMiddleware;
use Illuminate\Support\Facades\Route;

$middleware = config('blog.api_middleware', ['api']);
$prefix = config('blog.api_prefix', 'api');

Route::middleware($middleware)->prefix($prefix)->group(function () {
    Route::middleware(ApiKeyMiddleware::class)->group(function () {
        Route::get('/articles', [ArticleReceiverController::class, 'index']);
        Route::post('/articles', [ArticleReceiverController::class, 'store']);
        Route::delete('/articles/{masterArticleId}', [ArticleReceiverController::class, 'destroy']);
        Route::post('/site-config', [SiteConfigController::class, 'update']);
        Route::post('/seo-config', [SeoConfigController::class, 'update']);
        Route::post('/page-seo', [PageSeoController::class, 'update']);
        Route::post('/redirects', [RedirectsController::class, 'update']);
        Route::post('/sitemap/generate', [SitemapController::class, 'generate']);
        Route::get('/categories', [CategoryReceiverController::class, 'index']);
        Route::post('/categories', [CategoryReceiverController::class, 'store']);
        Route::delete('/categories/{masterCategoryId}', [CategoryReceiverController::class, 'destroy']);
        Route::get('/pages', [PageListController::class, 'index']);
        Route::get('/health-status', [HealthStatusController::class, 'index']);
    });
});
