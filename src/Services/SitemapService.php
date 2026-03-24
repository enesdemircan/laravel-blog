<?php

namespace Ceniver\Blog\Services;

use Ceniver\Blog\Models\BlogArticle;
use Ceniver\Blog\Models\BlogCategory;
use Illuminate\Support\Facades\Log;

class SitemapService
{
    private const CHUNK_SIZE = 5000;

    public function generate(): bool
    {
        try {
            $baseUrl = rtrim(config('app.url'), '/');
            $locales = app(SiteConfigService::class)->supportedLocales();
            $lastmod = now()->toAtomString();

            $urls = [];

            // Config'den ek URL'ler (ana sayfa, özel sayfalar vs.)
            $customUrls = config('blog.sitemap_urls', []);
            foreach ($customUrls as $custom) {
                $loc = $custom['loc'] ?? null;
                if (!$loc) continue;

                if (str_contains($loc, '{locale}')) {
                    // {locale} varsa her dil için genişlet
                    foreach ($locales as $locale) {
                        $urls[] = [
                            'loc'        => $baseUrl . '/' . ltrim(str_replace('{locale}', $locale, $loc), '/'),
                            'changefreq' => $custom['changefreq'] ?? 'monthly',
                            'priority'   => $custom['priority'] ?? '0.5',
                            'lastmod'    => $lastmod,
                        ];
                    }
                } else {
                    $urls[] = [
                        'loc'        => $baseUrl . '/' . ltrim($loc, '/'),
                        'changefreq' => $custom['changefreq'] ?? 'monthly',
                        'priority'   => $custom['priority'] ?? '0.5',
                        'lastmod'    => $lastmod,
                    ];
                }
            }

            // Ana blog sayfaları (her dil için)
            foreach ($locales as $locale) {
                $urls[] = [
                    'loc'        => "{$baseUrl}/{$locale}/blog",
                    'changefreq' => 'daily',
                    'priority'   => '0.8',
                    'lastmod'    => $lastmod,
                ];
            }

            // Kategori sayfaları
            BlogCategory::where('is_active', true)
                ->get()
                ->each(function ($category) use (&$urls, $baseUrl, $locales, $lastmod) {
                    foreach ($locales as $locale) {
                        $slug = $category->translations[$locale]['slug'] ?? null;
                        if (! $slug) continue;
                        $urls[] = [
                            'loc'        => "{$baseUrl}/{$locale}/blog/kategori/{$slug}",
                            'changefreq' => 'weekly',
                            'priority'   => '0.6',
                            'lastmod'    => $lastmod,
                        ];
                    }
                });

            // Makale sayfaları
            BlogArticle::with('translations')
                ->chunkById(500, function ($articles) use (&$urls, $baseUrl, $locales, $lastmod) {
                    foreach ($articles as $article) {
                        foreach ($article->translations as $translation) {
                            if (! in_array($translation->locale, $locales)) continue;
                            $articleLastmod = ($article->updated_at ?? $article->published_at)?->toAtomString() ?? $lastmod;
                            $urls[] = [
                                'loc'        => "{$baseUrl}/{$translation->locale}/blog/{$translation->slug}",
                                'changefreq' => 'monthly',
                                'priority'   => '0.9',
                                'lastmod'    => $articleLastmod,
                            ];
                        }
                    }
                });

            if (count($urls) <= self::CHUNK_SIZE) {
                file_put_contents(public_path('sitemap.xml'), $this->buildXml($urls));
                Log::info('Sitemap oluşturuldu. ' . count($urls) . ' URL yazıldı.');
            } else {
                $this->buildIndexedSitemap($urls, $baseUrl);
                Log::info('Sitemap index oluşturuldu. ' . count($urls) . ' URL, ' . ceil(count($urls) / self::CHUNK_SIZE) . ' parça.');
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Sitemap oluşturma hatası: ' . $e->getMessage());
            return false;
        }
    }

    private function buildIndexedSitemap(array $urls, string $baseUrl): void
    {
        $chunks    = array_chunk($urls, self::CHUNK_SIZE);
        $indexUrls = [];

        foreach ($chunks as $i => $chunk) {
            $filename  = "sitemap-{$i}.xml";
            $indexUrls[] = "{$baseUrl}/{$filename}";
            file_put_contents(public_path($filename), $this->buildXml($chunk));
        }

        file_put_contents(public_path('sitemap.xml'), $this->buildSitemapIndex($indexUrls));
    }

    private function buildXml(array $urls): string
    {
        $lines   = [];
        $lines[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $lines[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($urls as $url) {
            $lines[] = '  <url>';
            $lines[] = '    <loc>' . htmlspecialchars($url['loc'], ENT_XML1) . '</loc>';
            $lines[] = '    <lastmod>' . $url['lastmod'] . '</lastmod>';
            $lines[] = '    <changefreq>' . $url['changefreq'] . '</changefreq>';
            $lines[] = '    <priority>' . $url['priority'] . '</priority>';
            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines) . "\n";
    }

    private function buildSitemapIndex(array $sitemapUrls): string
    {
        $lastmod = now()->toAtomString();
        $lines   = [];
        $lines[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $lines[] = '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($sitemapUrls as $url) {
            $lines[] = '  <sitemap>';
            $lines[] = '    <loc>' . htmlspecialchars($url, ENT_XML1) . '</loc>';
            $lines[] = '    <lastmod>' . $lastmod . '</lastmod>';
            $lines[] = '  </sitemap>';
        }

        $lines[] = '</sitemapindex>';

        return implode("\n", $lines) . "\n";
    }
}
