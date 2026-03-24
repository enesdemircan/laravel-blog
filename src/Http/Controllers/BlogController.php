<?php

namespace Ceniver\Blog\Http\Controllers;

use Ceniver\Blog\Models\BlogArticle;
use Ceniver\Blog\Models\BlogArticleTranslation;
use Ceniver\Blog\Models\BlogCategory;
use Ceniver\Blog\Services\HeadBuilder;
use Ceniver\Blog\Services\SeoService;
use Ceniver\Blog\Services\SiteConfigService;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function __construct(
        private SiteConfigService $siteConfig,
        private SeoService        $seo,
        private HeadBuilder       $head,
    ) {}

    public function index(string $locale)
    {
        abort_if(!in_array($locale, $this->siteConfig->supportedLocales()), 404);

        $articles = BlogArticle::with(['translations' => fn($q) => $q->where('locale', $locale), 'category'])
            ->whereHas('translations', fn($q) => $q->where('locale', $locale))
            ->orderByDesc('published_at')
            ->paginate(12);

        $headHtml = $this->head->render('blog_index', $locale, [
            'site_name' => $this->seo->siteName(),
            'locale'    => $locale,
        ]);

        return view('blog::blog.index', compact('articles', 'locale', 'headHtml'));
    }

    public function category(string $locale, string $slug)
    {
        abort_if(!in_array($locale, $this->siteConfig->supportedLocales()), 404);

        $categories = BlogCategory::where('is_active', true)->orderBy('sort_order')->get();
        $category = $categories->first(fn($cat) => ($cat->translations[$locale]['slug'] ?? null) === $slug);
        abort_if(!$category, 404);

        $catName = $category->translations[$locale]['name'] ?? '';

        $articles = BlogArticle::with(['translations' => fn($q) => $q->where('locale', $locale), 'category'])
            ->where('category_id', $category->id)
            ->whereHas('translations', fn($q) => $q->where('locale', $locale))
            ->orderByDesc('published_at')
            ->paginate(12);

        $headHtml = $this->head->render('blog_category', $locale, [
            'site_name'     => $this->seo->siteName(),
            'category'      => $catName,
            'category_slug' => $slug,
            'locale'        => $locale,
        ]);

        return view('blog::blog.category', compact('articles', 'category', 'catName', 'locale', 'headHtml'));
    }

    public function show(string $locale, string $slug)
    {
        abort_if(!in_array($locale, $this->siteConfig->supportedLocales()), 404);

        $translation = BlogArticleTranslation::where('locale', $locale)
            ->where('slug', $slug)
            ->firstOrFail();

        $article = $translation->article()->with('category')->first();
        $otherTranslations = $article->translations()->get();
        $catTrans = $article->category?->translations[$locale] ?? null;
        $imageUrl = $translation->featured_image ? asset('storage/' . $translation->featured_image) : null;

        $hreflang = [];
        foreach ($otherTranslations as $alt) {
            $hreflang[$alt->locale] = route('blog.show', [$alt->locale, $alt->slug]);
        }

        $pageSeoData = $this->head->resolvePage('blog_article', $locale, [
            'site_name' => $this->seo->siteName(),
            'title'     => $translation->title,
            'excerpt'   => $translation->excerpt ?? '',
            'locale'    => $locale,
        ]);

        $overrideTitle = $pageSeoData['meta_title'] ? null : ($translation->meta_title ?: null);
        $overrideDesc  = $pageSeoData['meta_description'] ? null : ($translation->meta_description ?: null);

        $headHtml = $this->head->render('blog_article', $locale, [
            'site_name'     => $this->seo->siteName(),
            'title'         => $translation->title,
            'excerpt'       => $translation->excerpt ?? '',
            'locale'        => $locale,
            'category'      => $catTrans['name'] ?? '',
            'category_slug' => $catTrans['slug'] ?? '',
        ], [
            'title'       => $overrideTitle,
            'description' => $overrideDesc,
            'og_title'    => $pageSeoData['og_title'] ? null : ($translation->og_title ?: null),
            'og_description' => $pageSeoData['og_description'] ? null : ($translation->og_description ?: null),
            'og_image'    => $imageUrl,
            'og_type'     => 'article',
            'canonical'   => $translation->canonical_url ?: null,
            'robots'      => ($translation->robots_noindex || $translation->robots_nofollow)
                ? (($translation->robots_noindex ? 'noindex' : 'index') . ', ' . ($translation->robots_nofollow ? 'nofollow' : 'follow'))
                : null,
            'hreflang'    => $hreflang,
            'schema_json' => json_encode($this->buildArticleSchemas(
                $article, $translation, $locale, $imageUrl, $catTrans
            )),
        ]);

        return view('blog::blog.show', compact('article', 'translation', 'otherTranslations', 'locale', 'headHtml'));
    }

    /**
     * Makale için BlogPosting + FAQPage schema array döndürür.
     */
    private function buildArticleSchemas($article, $translation, string $locale, ?string $imageUrl, ?array $catTrans): array
    {
        $url = route('blog.show', [$locale, $translation->slug]);

        $schemas = [];

        // BlogPosting
        $schemas[] = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $url],
            'headline' => $translation->title,
            'description' => Str::limit($translation->excerpt ?? '', 160),
            'datePublished' => $article->published_at?->toIso8601String(),
            'dateModified' => $article->updated_at?->toIso8601String(),
            'url' => $url,
            'inLanguage' => $locale,
            'image' => $imageUrl ? ['@type' => 'ImageObject', 'url' => $imageUrl] : null,
            'author' => $this->seo->author_name ? ['@type' => 'Person', 'name' => $this->seo->author_name] : null,
            'publisher' => ['@type' => 'Organization', 'name' => $this->seo->siteName()],
            'articleSection' => $catTrans['name'] ?? null,
            'keywords' => $translation->focus_keyword ?? null,
        ];

        // FAQPage — içerikten h2/h3 + paragraf çiftlerini çıkar
        $faqItems = $this->extractFaqFromContent($translation->content ?? '');
        if (count($faqItems) >= 2) {
            $schemas[] = [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => array_map(fn($item) => [
                    '@type' => 'Question',
                    'name' => $item['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $item['answer'],
                    ],
                ], $faqItems),
            ];
        }

        return $schemas;
    }

    /**
     * HTML içeriğinden h2/h3 başlıkları ve sonrasındaki paragrafları FAQ olarak çıkarır.
     * Soru işareti (?) içeren başlıklar önceliklidir.
     */
    private function extractFaqFromContent(string $html): array
    {
        if (empty($html)) return [];

        $faq = [];

        // h2/h3 + sonraki <p> eşleştirmesi
        if (preg_match_all('/<h[23][^>]*>(.*?)<\/h[23]>\s*<p[^>]*>(.*?)<\/p>/si', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $question = trim(strip_tags($match[1]));
                $answer = trim(strip_tags($match[2]));

                if (strlen($question) > 10 && strlen($answer) > 20) {
                    $faq[] = [
                        'question' => $question,
                        'answer' => Str::limit($answer, 300),
                    ];
                }
            }
        }

        // Soru işareti olanları öne al
        usort($faq, fn($a, $b) => str_contains($b['question'], '?') <=> str_contains($a['question'], '?'));

        return array_slice($faq, 0, 10); // Max 10 FAQ
    }
}
