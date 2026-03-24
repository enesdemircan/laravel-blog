<?php

namespace Ceniver\Blog\Http\Middleware;

use Ceniver\Blog\Jobs\ReportBacklinkJob;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BacklinkReporterMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Sadece başarılı blog sayfalarında çalış (2xx, GET, HTML)
        if (
            $request->method() !== 'GET' ||
            !$response->isSuccessful() ||
            !str_contains($response->headers->get('Content-Type', ''), 'text/html')
        ) {
            return $response;
        }

        $referer = $request->header('Referer');
        if (!$referer) {
            return $response;
        }

        // Kendi sitesinden gelen istekleri atla
        $refererHost = parse_url($referer, PHP_URL_HOST);
        $ownHost     = parse_url(config('app.url', ''), PHP_URL_HOST);

        if (!$refererHost || $refererHost === $ownHost) {
            return $response;
        }

        // localhost / IP adreslerini atla
        if (
            str_starts_with($refererHost, '127.') ||
            str_starts_with($refererHost, '192.168.') ||
            $refererHost === 'localhost' ||
            filter_var($refererHost, FILTER_VALIDATE_IP)
        ) {
            return $response;
        }

        // Kuyruğa ekle
        ReportBacklinkJob::dispatch(
            referringUrl: $referer,
            targetUrl:    $request->url(),
        )->onQueue('default');

        return $response;
    }
}
