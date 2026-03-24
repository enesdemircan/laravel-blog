<?php

namespace Ceniver\Blog\Models;

use Illuminate\Database\Eloquent\Model;

class BlogCategory extends Model
{
    protected $table = 'blog_categories';

    protected $fillable = ['master_category_id', 'parent_id', 'sort_order', 'is_active', 'translations'];

    protected $casts = [
        'translations' => 'array',
        'is_active'    => 'boolean',
    ];

    public function getTranslation(string $locale): ?array
    {
        return $this->translations[$locale] ?? null;
    }

    public function articles()
    {
        return $this->hasMany(BlogArticle::class, 'category_id');
    }
}
