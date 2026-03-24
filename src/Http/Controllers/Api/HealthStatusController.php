<?php

namespace Ceniver\Blog\Http\Controllers\Api;

use Ceniver\Blog\Models\BlogArticle;
use Ceniver\Blog\Models\BlogCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class HealthStatusController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'status'           => 'ok',
            'php_version'      => PHP_VERSION,
            'laravel_version'  => app()->version(),
            'disk_free_mb'     => (int) (disk_free_space(base_path()) / 1024 / 1024),
            'article_count'    => BlogArticle::count(),
            'category_count'   => BlogCategory::count(),
            'last_article_at'  => BlogArticle::latest('updated_at')->value('updated_at'),
            'timestamp'        => now()->toIso8601String(),
        ]);
    }
}
