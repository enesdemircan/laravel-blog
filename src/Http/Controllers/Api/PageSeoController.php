<?php

namespace Ceniver\Blog\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;

class PageSeoController extends Controller
{
    private const FILE = 'page_seo.json';

    public function update(Request $request): JsonResponse
    {
        $pages = $request->input('pages', []);

        Storage::put(self::FILE, json_encode($pages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return response()->json(['message' => 'Page SEO güncellendi.', 'count' => count($pages)]);
    }
}
