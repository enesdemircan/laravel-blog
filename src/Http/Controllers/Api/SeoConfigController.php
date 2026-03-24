<?php

namespace Ceniver\Blog\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;

class SeoConfigController extends Controller
{
    private const FILE = 'seo_config.json';

    public function update(Request $request): JsonResponse
    {
        $data = $request->only([
            'site_display_name',
            'site_description',
            'author_name',
            'og_default_image',
            'ga4_id',
            'gsc_verification',
            'bing_verification',
            'twitter_handle',
            'twitter_card_type',
            'facebook_url',
            'instagram_url',
            'linkedin_url',
            'youtube_url',
            'robots_txt',
            'sitemap_article_priority',
            'sitemap_category_priority',
            'sitemap_article_frequency',
            'sitemap_category_frequency',
            'schema_organization',
            'hreflang_enabled',
        ]);

        $current = Storage::exists(self::FILE)
            ? (json_decode(Storage::get(self::FILE), true) ?? [])
            : [];

        Storage::put(self::FILE, json_encode(array_merge($current, $data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return response()->json(['message' => 'SEO config güncellendi.']);
    }
}
