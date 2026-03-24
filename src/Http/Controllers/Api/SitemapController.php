<?php

namespace Ceniver\Blog\Http\Controllers\Api;

use Ceniver\Blog\Jobs\GenerateSitemapJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class SitemapController extends Controller
{
    public function generate(): JsonResponse
    {
        GenerateSitemapJob::dispatch();

        return response()->json([
            'message' => 'Sitemap oluşturma kuyruğa alındı.',
            'queued'  => true,
        ], 202);
    }
}
