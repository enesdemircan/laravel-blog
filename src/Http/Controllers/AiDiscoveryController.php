<?php

namespace Ceniver\Blog\Http\Controllers;

use Ceniver\Blog\Models\BlogArticle;
use Ceniver\Blog\Models\BlogCategory;
use Ceniver\Blog\Services\SeoService;
use Ceniver\Blog\Services\SiteConfigService;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class AiDiscoveryController extends Controller
{
    public function __construct(
        private SeoService $seo,
        private SiteConfigService $siteConfig,
    ) {}

    public function llmsTxt()
    {
        $siteName = $this->seo->siteName();
        $siteDesc = $this->seo->site_description ?? '';
        $locale = $this->siteConfig->defaultLocale();
        $baseUrl = config('app.url');

        $lines = [];
        $lines[] = "# {$siteName}";
        $lines[] = "";
        $lines[] = "> {$siteDesc}";
        $lines[] = "";
        $lines[] = "## Site Bilgileri";
        $lines[] = "- Dil: {$locale}";
        $lines[] = "- URL: {$baseUrl}";
        if ($this->seo->author_name) {
            $lines[] = "- Yazar: {$this->seo->author_name}";
        }
        $lines[] = "";

        $lines[] = "## Sayfalar";
        $lines[] = "- [Ana Sayfa]({$baseUrl})";
        $lines[] = "- [Blog]({$baseUrl}/{$locale}/blog)";

        $categories = BlogCategory::where('is_active', true)->orderBy('sort_order')->get();
        if ($categories->count()) {
            $lines[] = "";
            $lines[] = "## Blog Kategorileri";
            foreach ($categories as $cat) {
                $catTrans = $cat->translations[$locale] ?? null;
                if ($catTrans) {
                    $lines[] = "- [{$catTrans['name']}]({$baseUrl}/{$locale}/blog/kategori/{$catTrans['slug']})";
                }
            }
        }

        $articles = BlogArticle::with(['translations' => fn($q) => $q->where('locale', $locale)])
            ->whereHas('translations', fn($q) => $q->where('locale', $locale))
            ->orderByDesc('published_at')
            ->take(20)
            ->get();

        if ($articles->count()) {
            $lines[] = "";
            $lines[] = "## Son Makaleler";
            foreach ($articles as $article) {
                $t = $article->translations->first();
                if ($t) {
                    $lines[] = "- [{$t->title}]({$baseUrl}/{$locale}/blog/{$t->slug}): {$t->excerpt}";
                }
            }
        }

        $lines[] = "";
        $lines[] = "## Detaylı İçerik";
        $lines[] = "- [Tüm içerik (llms-full.txt)]({$baseUrl}/llms-full.txt)";
        $lines[] = "- [RSS Feed]({$baseUrl}/feed.xml)";
        $lines[] = "- [Sitemap]({$baseUrl}/sitemap.xml)";

        return response(implode("\n", $lines), 200)
            ->header('Content-Type', 'text/plain; charset=utf-8');
    }

    public function llmsFullTxt()
    {
        $siteName = $this->seo->siteName();
        $locale = $this->siteConfig->defaultLocale();
        $baseUrl = config('app.url');

        $lines = [];
        $lines[] = "# {$siteName} — Tüm İçerikler";
        $lines[] = "";

        $articles = BlogArticle::with([
            'translations' => fn($q) => $q->where('locale', $locale),
            'category',
        ])
            ->whereHas('translations', fn($q) => $q->where('locale', $locale))
            ->orderByDesc('published_at')
            ->get();

        foreach ($articles as $article) {
            $t = $article->translations->first();
            if (!$t) continue;

            $catTrans = $article->category?->translations[$locale] ?? null;
            $plainContent = strip_tags($t->content);
            $url = "{$baseUrl}/{$locale}/blog/{$t->slug}";

            $lines[] = "---";
            $lines[] = "";
            $lines[] = "## {$t->title}";
            $lines[] = "";
            $lines[] = "- URL: {$url}";
            $lines[] = "- Tarih: {$article->published_at?->format('Y-m-d')}";
            if ($catTrans) {
                $lines[] = "- Kategori: {$catTrans['name']}";
            }
            if ($t->focus_keyword) {
                $lines[] = "- Anahtar Kelime: {$t->focus_keyword}";
            }
            $lines[] = "";
            $lines[] = $plainContent;
            $lines[] = "";
        }

        return response(implode("\n", $lines), 200)
            ->header('Content-Type', 'text/plain; charset=utf-8');
    }

    public function feed()
    {
        $siteName = $this->seo->siteName();
        $siteDesc = $this->seo->site_description ?? '';
        $locale = $this->siteConfig->defaultLocale();
        $baseUrl = config('app.url');

        $articles = BlogArticle::with([
            'translations' => fn($q) => $q->where('locale', $locale),
            'category',
        ])
            ->whereHas('translations', fn($q) => $q->where('locale', $locale))
            ->orderByDesc('published_at')
            ->take(50)
            ->get();

        $items = '';
        foreach ($articles as $article) {
            $t = $article->translations->first();
            if (!$t) continue;

            $url = e("{$baseUrl}/{$locale}/blog/{$t->slug}");
            $title = e($t->title);
            $desc = e(Str::limit(strip_tags($t->content), 500));
            $date = $article->published_at?->toRfc2822String();
            $catTrans = $article->category?->translations[$locale] ?? null;
            $category = $catTrans ? '<category>' . e($catTrans['name']) . '</category>' : '';

            $items .= <<<XML
        <item>
            <title>{$title}</title>
            <link>{$url}</link>
            <guid isPermaLink="true">{$url}</guid>
            <description>{$desc}</description>
            <pubDate>{$date}</pubDate>
            {$category}
        </item>
XML;
        }

        $lastBuild = $articles->first()?->published_at?->toRfc2822String() ?? now()->toRfc2822String();
        $siteName = e($siteName);
        $siteDesc = e($siteDesc);

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>{$siteName}</title>
        <link>{$baseUrl}</link>
        <description>{$siteDesc}</description>
        <language>{$locale}</language>
        <lastBuildDate>{$lastBuild}</lastBuildDate>
        <atom:link href="{$baseUrl}/feed.xml" rel="self" type="application/rss+xml"/>
{$items}
    </channel>
</rss>
XML;

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }

    public function robots()
    {
        $baseUrl = config('app.url');
        $customRobots = $this->seo->robotsTxt();

        if ($customRobots) {
            return response($customRobots, 200)
                ->header('Content-Type', 'text/plain; charset=utf-8');
        }

        $robots = <<<TXT
User-agent: *
Allow: /

# AI Crawlers — İzin Ver
User-agent: GPTBot
Allow: /

User-agent: ChatGPT-User
Allow: /

User-agent: Claude-Web
Allow: /

User-agent: ClaudeBot
Allow: /

User-agent: Perplexity-Bot
Allow: /

User-agent: Google-Extended
Allow: /

User-agent: Bingbot
Allow: /

User-agent: Googlebot
Allow: /

# Sitemap & AI Discovery
Sitemap: {$baseUrl}/sitemap.xml
TXT;

        return response($robots, 200)
            ->header('Content-Type', 'text/plain; charset=utf-8');
    }
}
