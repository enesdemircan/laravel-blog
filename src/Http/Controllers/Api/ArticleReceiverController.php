<?php

namespace Ceniver\Blog\Http\Controllers\Api;

use Ceniver\Blog\Models\BlogArticle;
use Ceniver\Blog\Models\BlogArticleTranslation;
use Ceniver\Blog\Models\BlogCategory;
use Ceniver\Blog\Services\SitemapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ArticleReceiverController extends Controller
{
    public function index(): JsonResponse
    {
        $articles = BlogArticle::with('translations', 'category')->get();

        $result = $articles->map(function ($article) {
            $translations = $article->translations->map(function ($t) {
                return [
                    'locale'           => $t->locale,
                    'title'            => $t->title,
                    'slug'             => $t->slug,
                    'content'          => $t->content,
                    'excerpt'          => $t->excerpt,
                    'meta_title'       => $t->meta_title,
                    'meta_description' => $t->meta_description,
                    'featured_image_url' => $t->featured_image
                        ? asset('storage/' . $t->featured_image)
                        : null,
                    'is_ai_generated'  => $t->is_ai_generated,
                    'focus_keyword'    => $t->focus_keyword,
                    'og_title'         => $t->og_title,
                    'og_description'   => $t->og_description,
                    'canonical_url'    => $t->canonical_url,
                    'schema_type'      => $t->schema_type,
                    'schema_json'      => $t->schema_json,
                    'robots_noindex'   => $t->robots_noindex,
                    'robots_nofollow'  => $t->robots_nofollow,
                ];
            });

            return [
                'master_article_id'  => $article->master_article_id,
                'master_category_id' => $article->category?->master_category_id,
                'category_slug'      => $article->category
                    ? (collect($article->category->translations)->first()['slug'] ?? null)
                    : null,
                'published_at'       => $article->published_at?->toIso8601String(),
                'translations'       => $translations,
            ];
        });

        return response()->json(['data' => $result]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'article_id'               => 'required|integer',
            'translations'             => 'required|array|min:1',
            'translations.*.locale'    => 'required|string|size:2',
            'translations.*.title'     => 'required|string',
            'translations.*.slug'      => 'required|string',
            'translations.*.content'   => 'required|string',
        ]);

        try {
            $categoryId = null;
            if ($request->filled('category') && !empty($request->category['translations'])) {
                $categoryId = $this->syncCategory($request->category);
            }

            $article = BlogArticle::updateOrCreate(
                ['master_article_id' => $request->article_id],
                [
                    'category_id'  => $categoryId,
                    'published_at' => now(),
                ]
            );

            foreach ($request->translations as $trans) {
                $locale = $trans['locale'];

                $imagePath = null;
                if (!empty($trans['featured_image'])) {
                    $imagePath = $this->saveImage(
                        $trans['featured_image'],
                        $trans['image_extension'] ?? 'jpg',
                        $article->id,
                        $locale
                    );
                }

                $content = $trans['content'] ?? null;
                if ($content && trim(strip_tags($content)) === '') {
                    $content = null;
                }

                BlogArticleTranslation::updateOrCreate(
                    ['article_id' => $article->id, 'locale' => $locale],
                    [
                        'title'            => $trans['title'],
                        'slug'             => $trans['slug'],
                        'content'          => $content ?? '',
                        'excerpt'          => $trans['excerpt'] ?? null,
                        'meta_title'       => $trans['meta_title'] ?? $trans['title'],
                        'meta_description' => $trans['meta_description'] ?? null,
                        'featured_image'   => $imagePath,
                        'is_ai_generated'  => $trans['is_ai_generated'] ?? false,
                        'focus_keyword'    => $trans['focus_keyword'] ?? null,
                        'og_title'         => $trans['og_title'] ?? null,
                        'og_description'   => $trans['og_description'] ?? null,
                        'canonical_url'    => $trans['canonical_url'] ?? null,
                        'schema_type'      => $trans['schema_type'] ?? null,
                        'schema_json'      => $trans['schema_json'] ?? null,
                        'robots_noindex'   => $trans['robots_noindex'] ?? false,
                        'robots_nofollow'  => $trans['robots_nofollow'] ?? false,
                    ]
                );
            }

            Log::info("Article #{$request->article_id} received and saved successfully.");

            app(SitemapService::class)->generate();

            return response()->json([
                'message'    => 'Makale başarıyla alındı.',
                'article_id' => $article->id,
            ]);

        } catch (\Throwable $e) {
            Log::error("Article receive error: " . $e->getMessage());
            return response()->json(['message' => 'Sunucu hatası: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(int $masterArticleId): JsonResponse
    {
        $article = BlogArticle::where('master_article_id', $masterArticleId)->first();

        if (!$article) {
            return response()->json(['message' => 'Makale bulunamadı.'], 404);
        }

        foreach ($article->translations as $translation) {
            if ($translation->featured_image) {
                Storage::disk('public')->delete($translation->featured_image);
            }
        }

        $article->delete();

        app(SitemapService::class)->generate();

        Log::info("Article (master_id:{$masterArticleId}) deleted from slave.");

        return response()->json(['message' => 'Makale silindi.']);
    }

    private function syncCategory(array $categoryData): int
    {
        $translations = [];
        foreach ($categoryData['translations'] as $ct) {
            $translations[$ct['locale']] = [
                'name' => $ct['name'],
                'slug' => $ct['slug'],
            ];
        }

        $masterCategoryId = $categoryData['category_id'] ?? null;

        $category = BlogCategory::updateOrCreate(
            $masterCategoryId
                ? ['master_category_id' => $masterCategoryId]
                : ['master_category_id' => null],
            [
                'master_category_id' => $masterCategoryId,
                'translations'       => $translations,
                'is_active'          => true,
            ]
        );

        return $category->id;
    }

    private function saveImage(string $base64Data, string $extension, int $articleId, string $locale): ?string
    {
        try {
            $filename = "blog/articles/{$articleId}/{$locale}.{$extension}";
            Storage::disk('public')->put($filename, base64_decode($base64Data));
            return $filename;
        } catch (\Throwable $e) {
            Log::warning("Image save failed for article #{$articleId}: " . $e->getMessage());
            return null;
        }
    }
}
