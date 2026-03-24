<?php

namespace Ceniver\Blog\Services;

use Ceniver\Blog\Models\BlogArticle;
use Ceniver\Blog\Models\BlogCategory;
use Illuminate\Support\Facades\Log;

class SitemapService
{
    private const MAX_URLS_PER_FILE = 5000;

    private SeoService $seo;
    private SiteConfigService $siteConfig;

    public function __construct(SeoService $seo, SiteConfigService $siteConfig)
    {
        $this->seo = $seo;
        $this->siteConfig = $siteConfig;
    }

    public function generate(): bool
    {
        try {
            $baseUrl = rtrim(config('app.url'), '/');
            $locales = $this->siteConfig->supportedLocales();
            $lastmod = now()->toAtomString();

            // Ayarları seo_config.json'dan oku (master'dan gelir), yoksa config/blog.php fallback
            $defaults = config('blog.sitemap', []);
            $homePriority  = $this->seo->get('sitemap_homepage_priority',  $defaults['homepage_priority']  ?? '1.0');
            $homeFrequency = $this->seo->get('sitemap_homepage_frequency', $defaults['homepage_frequency'] ?? 'daily');
            $artPriority   = $this->seo->get('sitemap_article_priority',   $defaults['article_priority']   ?? '0.8');
            $artFrequency  = $this->seo->get('sitemap_article_frequency',  $defaults['article_frequency']  ?? 'weekly');
            $catPriority   = $this->seo->get('sitemap_category_priority',  $defaults['category_priority']  ?? '0.6');
            $catFrequency  = $this->seo->get('sitemap_category_frequency', $defaults['category_frequency'] ?? 'monthly');

            // ─── 1. Sabit sayfalar (homepage + custom) ───
            $pageUrls = [];

            // Ana sayfa
            $pageUrls[] = [
                'loc'        => $baseUrl . '/',
                'changefreq' => (string) $homeFrequency,
                'priority'   => (string) $homePriority,
                'lastmod'    => $lastmod,
            ];

            // Config'deki ek URL'ler (geliştirici tarafından eklenen)
            $configUrls = config('blog.sitemap_urls', []);
            foreach ($configUrls as $custom) {
                $loc = $custom['loc'] ?? null;
                if (!$loc || $loc === '/') continue; // homepage zaten eklendi

                if (str_contains($loc, '{locale}')) {
                    foreach ($locales as $locale) {
                        $pageUrls[] = [
                            'loc'        => $baseUrl . '/' . ltrim(str_replace('{locale}', $locale, $loc), '/'),
                            'changefreq' => $custom['changefreq'] ?? 'monthly',
                            'priority'   => $custom['priority'] ?? '0.5',
                            'lastmod'    => $lastmod,
                        ];
                    }
                } else {
                    $pageUrls[] = [
                        'loc'        => $baseUrl . '/' . ltrim($loc, '/'),
                        'changefreq' => $custom['changefreq'] ?? 'monthly',
                        'priority'   => $custom['priority'] ?? '0.5',
                        'lastmod'    => $lastmod,
                    ];
                }
            }

            // ─── 2. Blog sayfaları ───
            $blogUrls = [];
            foreach ($locales as $locale) {
                $blogUrls[] = [
                    'loc'        => "{$baseUrl}/{$locale}/blog",
                    'changefreq' => 'daily',
                    'priority'   => '0.8',
                    'lastmod'    => $lastmod,
                ];
            }

            // ─── 3. Kategori sayfaları ───
            $categoryUrls = [];
            BlogCategory::where('is_active', true)
                ->get()
                ->each(function ($category) use (&$categoryUrls, $baseUrl, $locales, $lastmod, $catPriority, $catFrequency) {
                    foreach ($locales as $locale) {
                        $slug = $category->translations[$locale]['slug'] ?? null;
                        if (!$slug) continue;
                        $categoryUrls[] = [
                            'loc'        => "{$baseUrl}/{$locale}/blog/kategori/{$slug}",
                            'changefreq' => (string) $catFrequency,
                            'priority'   => (string) $catPriority,
                            'lastmod'    => $lastmod,
                        ];
                    }
                });

            // ─── 4. Makale sayfaları ───
            $articleUrls = [];
            BlogArticle::with('translations')
                ->chunkById(500, function ($articles) use (&$articleUrls, $baseUrl, $locales, $lastmod, $artPriority, $artFrequency) {
                    foreach ($articles as $article) {
                        foreach ($article->translations as $translation) {
                            if (!in_array($translation->locale, $locales)) continue;
                            $articleLastmod = ($article->updated_at ?? $article->published_at)?->toAtomString() ?? $lastmod;
                            $articleUrls[] = [
                                'loc'        => "{$baseUrl}/{$translation->locale}/blog/{$translation->slug}",
                                'changefreq' => (string) $artFrequency,
                                'priority'   => (string) $artPriority,
                                'lastmod'    => $articleLastmod,
                            ];
                        }
                    }
                });

            // ─── Dosya oluşturma stratejisi ───
            $totalUrls = count($pageUrls) + count($blogUrls) + count($categoryUrls) + count($articleUrls);

            if ($totalUrls <= self::MAX_URLS_PER_FILE) {
                // Tek sitemap.xml yeterli
                $allUrls = array_merge($pageUrls, $blogUrls, $categoryUrls, $articleUrls);
                file_put_contents(public_path('sitemap.xml'), $this->buildUrlsetXml($allUrls));
                // Eski parça dosyaları varsa temizle
                $this->cleanOldSitemapFiles();
            } else {
                // Sitemap index — her tip ayrı dosya, büyük tipler chunk'lanır
                $sitemaps = [];

                // Sayfalar
                if (!empty($pageUrls)) {
                    $sitemaps = array_merge($sitemaps, $this->writeChunkedSitemap('sitemap-pages', $pageUrls, $baseUrl));
                }

                // Blog + Kategoriler
                $blogCatUrls = array_merge($blogUrls, $categoryUrls);
                if (!empty($blogCatUrls)) {
                    $sitemaps = array_merge($sitemaps, $this->writeChunkedSitemap('sitemap-categories', $blogCatUrls, $baseUrl));
                }

                // Makaleler (en büyük — chunk'lanır)
                if (!empty($articleUrls)) {
                    $sitemaps = array_merge($sitemaps, $this->writeChunkedSitemap('sitemap-articles', $articleUrls, $baseUrl));
                }

                file_put_contents(public_path('sitemap.xml'), $this->buildSitemapIndexXml($sitemaps));
            }

            Log::info("Sitemap oluşturuldu: {$totalUrls} URL ({$this->formatCount($pageUrls, $categoryUrls, $articleUrls)})");
            return true;

        } catch (\Throwable $e) {
            Log::error('Sitemap oluşturma hatası: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Büyük URL listesini chunk'layıp dosyalara yazar.
     * @return array Oluşturulan sitemap URL'leri (index için)
     */
    private function writeChunkedSitemap(string $prefix, array $urls, string $baseUrl): array
    {
        $sitemaps = [];
        $chunks = array_chunk($urls, self::MAX_URLS_PER_FILE);

        foreach ($chunks as $i => $chunk) {
            $filename = count($chunks) === 1 ? "{$prefix}.xml" : "{$prefix}-{$i}.xml";
            file_put_contents(public_path($filename), $this->buildUrlsetXml($chunk));
            $sitemaps[] = [
                'loc'     => "{$baseUrl}/{$filename}",
                'lastmod' => now()->toAtomString(),
            ];
        }

        return $sitemaps;
    }

    private function cleanOldSitemapFiles(): void
    {
        $patterns = ['sitemap-pages*.xml', 'sitemap-categories*.xml', 'sitemap-articles*.xml'];
        foreach ($patterns as $pattern) {
            foreach (glob(public_path($pattern)) as $file) {
                @unlink($file);
            }
        }
    }

    private function buildUrlsetXml(array $urls): string
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

    private function buildSitemapIndexXml(array $sitemaps): string
    {
        $lines   = [];
        $lines[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $lines[] = '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($sitemaps as $sitemap) {
            $lines[] = '  <sitemap>';
            $lines[] = '    <loc>' . htmlspecialchars($sitemap['loc'], ENT_XML1) . '</loc>';
            $lines[] = '    <lastmod>' . $sitemap['lastmod'] . '</lastmod>';
            $lines[] = '  </sitemap>';
        }

        $lines[] = '</sitemapindex>';

        return implode("\n", $lines) . "\n";
    }

    private function formatCount(array $pages, array $categories, array $articles): string
    {
        return count($pages) . ' sayfa, ' . count($categories) . ' kategori, ' . count($articles) . ' makale';
    }
}
