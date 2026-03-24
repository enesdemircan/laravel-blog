<?php

namespace Ceniver\Blog\Services;

class HeadBuilder
{
    private SeoService $seo;
    private PageSeoService $pageSeo;
    private SiteConfigService $siteConfig;

    public function __construct(SeoService $seo, PageSeoService $pageSeo, SiteConfigService $siteConfig)
    {
        $this->seo = $seo;
        $this->pageSeo = $pageSeo;
        $this->siteConfig = $siteConfig;
    }

    public function resolvePage(string $pageType, string $locale, array $vars = []): array
    {
        return $this->pageSeo->forPage($pageType, $locale, $vars);
    }

    public function render(string $pageType, string $locale, array $vars = [], array $overrides = []): string
    {
        $pageSeo = $this->pageSeo->forPage($pageType, $locale, $vars);
        $seo = $this->seo;
        $siteName = $seo->siteName();

        // Değerleri çözümle (override > pageSeo > seo config > fallback)
        $title       = $overrides['title']       ?? $pageSeo['meta_title']       ?? $siteName;
        $description = $overrides['description'] ?? $pageSeo['meta_description'] ?? $seo->site_description ?? '';
        $keywords    = $overrides['keywords']    ?? $pageSeo['meta_keywords']    ?? null;
        $author      = $overrides['author']      ?? $pageSeo['meta_author']      ?? $seo->author_name ?? null;
        $canonical   = $overrides['canonical']   ?? $pageSeo['canonical_url']    ?? url()->current();

        // Open Graph
        $ogTitle = $overrides['og_title']       ?? $pageSeo['og_title']       ?? $title;
        $ogDesc  = $overrides['og_description'] ?? $pageSeo['og_description'] ?? $description;
        $ogImage = $overrides['og_image']       ?? $pageSeo['og_image_url']   ?? $seo->ogDefaultImage();
        $ogType  = $overrides['og_type']        ?? $pageSeo['og_type']        ?? 'website';
        $ogLocale = $overrides['og_locale']     ?? $pageSeo['og_locale']      ?? $locale . '_' . strtoupper($locale);

        // Twitter
        $twTitle = $overrides['twitter_title']       ?? $pageSeo['twitter_title']       ?? $ogTitle;
        $twDesc  = $overrides['twitter_description'] ?? $pageSeo['twitter_description'] ?? $ogDesc;
        $twImage = $overrides['twitter_image']       ?? $pageSeo['twitter_image_url']   ?? $ogImage;
        $twCard  = $seo->twitter_card_type ?? 'summary_large_image';
        $twHandle = $seo->twitterHandle();

        // Robots
        $robots = $overrides['robots'] ?? null;
        if (!$robots) {
            $parts = [];
            $parts[] = ($pageSeo['robots_noindex'] ?? false) ? 'noindex' : 'index';
            $parts[] = ($pageSeo['robots_nofollow'] ?? false) ? 'nofollow' : 'follow';
            if ($pageSeo['robots_advanced'] ?? null) {
                $parts[] = $pageSeo['robots_advanced'];
            }
            $robots = implode(', ', $parts);
        }

        // Schema JSON-LD
        $schemaJson = $overrides['schema_json'] ?? $pageSeo['schema_json'] ?? null;

        // Hreflang
        $hreflang = $overrides['hreflang'] ?? [];

        // Supported locales
        $supportedLocales = $this->siteConfig->supportedLocales();

        // HTML oluştur
        $html = '';

        // Primary SEO
        $html .= '<title>' . e($title) . '</title>' . "\n";
        if ($description) {
            $html .= '    <meta name="description" content="' . e($description) . '">' . "\n";
        }
        if ($keywords) {
            $html .= '    <meta name="keywords" content="' . e($keywords) . '">' . "\n";
        }
        if ($author) {
            $html .= '    <meta name="author" content="' . e($author) . '">' . "\n";
        }
        $html .= '    <meta name="robots" content="' . e($robots) . '">' . "\n";
        $html .= '    <link rel="canonical" href="' . e($canonical) . '">' . "\n";

        // Open Graph
        $html .= '    <meta property="og:type" content="' . e($ogType) . '">' . "\n";
        $html .= '    <meta property="og:url" content="' . e($canonical) . '">' . "\n";
        $html .= '    <meta property="og:title" content="' . e($ogTitle) . '">' . "\n";
        if ($ogDesc) {
            $html .= '    <meta property="og:description" content="' . e($ogDesc) . '">' . "\n";
        }
        if ($ogImage) {
            $html .= '    <meta property="og:image" content="' . e($ogImage) . '">' . "\n";
            $html .= '    <meta property="og:image:width" content="1200">' . "\n";
            $html .= '    <meta property="og:image:height" content="630">' . "\n";
        }
        $html .= '    <meta property="og:locale" content="' . e($ogLocale) . '">' . "\n";
        $html .= '    <meta property="og:site_name" content="' . e($siteName) . '">' . "\n";

        // Twitter Card
        $html .= '    <meta name="twitter:card" content="' . e($twCard) . '">' . "\n";
        $html .= '    <meta name="twitter:url" content="' . e($canonical) . '">' . "\n";
        $html .= '    <meta name="twitter:title" content="' . e($twTitle) . '">' . "\n";
        if ($twDesc) {
            $html .= '    <meta name="twitter:description" content="' . e($twDesc) . '">' . "\n";
        }
        if ($twImage) {
            $html .= '    <meta name="twitter:image" content="' . e($twImage) . '">' . "\n";
        }
        if ($twHandle) {
            $html .= '    <meta name="twitter:site" content="' . e($twHandle) . '">' . "\n";
        }

        // Hreflang
        if (!empty($hreflang)) {
            foreach ($hreflang as $lang => $url) {
                $html .= '    <link rel="alternate" hreflang="' . e($lang) . '" href="' . e($url) . '">' . "\n";
            }
        } elseif (count($supportedLocales) > 1) {
            $currentPath = request()->path();
            foreach ($supportedLocales as $loc) {
                $altPath = preg_replace('#^' . preg_quote($locale, '#') . '/#', $loc . '/', $currentPath);
                $html .= '    <link rel="alternate" hreflang="' . e($loc) . '" href="' . e(url('/' . $altPath)) . '">' . "\n";
            }
        }

        // RSS Feed & AI Discovery
        $html .= '    <link rel="alternate" type="application/rss+xml" title="' . e($siteName) . ' RSS" href="' . e(url('/feed.xml')) . '">' . "\n";

        // Verification
        if ($seo->gsc_verification) {
            $html .= '    <meta name="google-site-verification" content="' . e($seo->gsc_verification) . '">' . "\n";
        }
        if ($seo->bing_verification) {
            $html .= '    <meta name="msvalidate.01" content="' . e($seo->bing_verification) . '">' . "\n";
        }

        // GA4
        if ($seo->ga4Id()) {
            $ga4 = e($seo->ga4Id());
            $html .= '    <script async src="https://www.googletagmanager.com/gtag/js?id=' . $ga4 . '"></script>' . "\n";
            $html .= '    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag(\'js\',new Date());gtag(\'config\',\'' . $ga4 . '\');</script>' . "\n";
        }

        // Schema JSON-LD
        if ($schemaJson) {
            $schemas = json_decode($schemaJson, true);
            if ($schemas) {
                $list = (isset($schemas['@type']) || isset($schemas['@context'])) ? [$schemas] : $schemas;
                foreach ($list as $s) {
                    if ($s) {
                        $html .= '    <script type="application/ld+json">' . json_encode($s, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
                    }
                }
            }
        }

        // Extra raw HTML
        if ($overrides['extra'] ?? null) {
            $html .= '    ' . $overrides['extra'] . "\n";
        }

        return $html;
    }
}
