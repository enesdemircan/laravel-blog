<?php

namespace Ceniver\Blog\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class PageListController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(config('blog.pages', []));
    }
}
