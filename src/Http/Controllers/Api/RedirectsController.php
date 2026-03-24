<?php

namespace Ceniver\Blog\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;

class RedirectsController extends Controller
{
    private const FILE = 'redirects.json';

    public function update(Request $request): JsonResponse
    {
        $redirects = $request->input('redirects', []);

        Storage::put(self::FILE, json_encode($redirects, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return response()->json(['message' => 'Redirects güncellendi.', 'count' => count($redirects)]);
    }
}
