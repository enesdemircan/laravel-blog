<?php

namespace Ceniver\Blog\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureBlogConfigured
{
    public function handle(Request $request, Closure $next)
    {
        $apiKey    = config('blog.master_api_key');
        $masterUrl = config('blog.master_url');

        if (empty($apiKey) || empty($masterUrl)) {
            return redirect()->route('blog.setup');
        }

        return $next($request);
    }
}
