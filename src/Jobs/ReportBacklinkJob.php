<?php

namespace Ceniver\Blog\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReportBacklinkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 15;

    public function __construct(
        private string $referringUrl,
        private string $targetUrl,
        private ?string $anchorText = null,
    ) {}

    public function handle(): void
    {
        $masterUrl = rtrim(config('blog.master_url', ''), '/');
        $apiKey    = config('blog.master_api_key', '');

        if (!$masterUrl || !$apiKey) {
            return;
        }

        try {
            Http::timeout(10)
                ->withHeaders(['X-Api-Key' => $apiKey])
                ->post("{$masterUrl}/api/slave/backlinks", [
                    'backlinks' => [[
                        'referring_url' => $this->referringUrl,
                        'target_url'    => $this->targetUrl,
                        'anchor_text'   => $this->anchorText,
                    ]],
                ]);
        } catch (\Throwable $e) {
            Log::warning('Backlink raporu gönderilemedi: ' . $e->getMessage());
            throw $e;
        }
    }
}
