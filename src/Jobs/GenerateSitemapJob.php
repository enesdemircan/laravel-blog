<?php

namespace Ceniver\Blog\Jobs;

use Ceniver\Blog\Services\SitemapService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateSitemapJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $timeout = 600;
    public int $tries = 2;

    public function handle(SitemapService $sitemapService): void
    {
        Log::info('GenerateSitemapJob başladı.');
        $sitemapService->generate();
        Log::info('GenerateSitemapJob tamamlandı.');
    }
}
