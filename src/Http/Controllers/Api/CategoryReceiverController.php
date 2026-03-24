<?php

namespace Ceniver\Blog\Http\Controllers\Api;

use Ceniver\Blog\Models\BlogCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class CategoryReceiverController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = BlogCategory::all()->map(function ($cat) {
            $defaultLocale = config('app.locale', 'tr');
            $translations  = $cat->translations ?? [];
            $name = $translations[$defaultLocale]['name']
                ?? collect($translations)->first()['name']
                ?? '(İsimsiz)';
            $slug = $translations[$defaultLocale]['slug']
                ?? collect($translations)->first()['slug']
                ?? '';

            $parentMasterId = null;
            if ($cat->parent_id) {
                $parentMasterId = BlogCategory::where('id', $cat->parent_id)->value('master_category_id');
            }

            return [
                'master_category_id' => $cat->master_category_id,
                'parent_id'          => $parentMasterId,
                'name'               => $name,
                'slug'               => $slug,
                'locale'             => $defaultLocale,
                'is_active'          => $cat->is_active,
                'sort_order'         => $cat->sort_order,
            ];
        });

        return response()->json(['categories' => $categories->values()]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'category_id'  => 'required|integer',
            'parent_id'    => 'nullable|integer',
            'is_active'    => 'boolean',
            'sort_order'   => 'integer',
            'translations' => 'required|array|min:1',
        ]);

        try {
            $translations = [];
            foreach ($request->translations as $locale => $trans) {
                $translations[$locale] = [
                    'name' => $trans['name'],
                    'slug' => $trans['slug'],
                ];
            }

            $parentId = null;
            if ($request->parent_id) {
                $parentCat = BlogCategory::where('master_category_id', $request->parent_id)->first();
                $parentId = $parentCat?->id;
            }

            BlogCategory::updateOrCreate(
                ['master_category_id' => $request->category_id],
                [
                    'parent_id'    => $parentId,
                    'is_active'    => $request->boolean('is_active', true),
                    'sort_order'   => $request->input('sort_order', 0),
                    'translations' => $translations,
                ]
            );

            Log::info("Category #{$request->category_id} received and saved.");

            return response()->json(['message' => 'Kategori kaydedildi.']);
        } catch (\Throwable $e) {
            Log::error("Category receive error: " . $e->getMessage());
            return response()->json(['message' => 'Sunucu hatası: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(int $masterCategoryId): JsonResponse
    {
        $category = BlogCategory::where('master_category_id', $masterCategoryId)->first();

        if (!$category) {
            return response()->json(['message' => 'Kategori bulunamadı.'], 404);
        }

        $category->articles()->update(['category_id' => null]);
        $category->delete();

        Log::info("Category (master_id:{$masterCategoryId}) deleted from slave.");

        return response()->json(['message' => 'Kategori silindi.']);
    }
}
