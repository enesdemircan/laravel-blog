<?php

namespace Ceniver\Blog\Http\Controllers\Api;

use Ceniver\Blog\Services\SiteConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SiteConfigController extends Controller
{
    public function __construct(private SiteConfigService $siteConfig) {}

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'supported_locales'   => 'required|array|min:1',
            'supported_locales.*' => 'string|size:2',
            'default_locale'      => 'required|string|size:2',
            'blog_name'           => 'nullable|string|max:255',
        ]);

        $this->siteConfig->update([
            'supported_locales' => $request->supported_locales,
            'default_locale'    => $request->default_locale,
            'blog_name'         => $request->blog_name,
        ]);

        return response()->json(['message' => 'Site config güncellendi.']);
    }
}
