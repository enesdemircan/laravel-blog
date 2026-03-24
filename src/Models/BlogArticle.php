<?php

namespace Ceniver\Blog\Models;

use Illuminate\Database\Eloquent\Model;

class BlogArticle extends Model
{
    protected $table = 'blog_articles';

    protected $fillable = ['master_article_id', 'category_id', 'published_at'];

    protected $casts = ['published_at' => 'datetime'];

    public function translations()
    {
        return $this->hasMany(BlogArticleTranslation::class, 'article_id');
    }

    public function translation(string $locale)
    {
        return $this->translations()->where('locale', $locale)->first();
    }

    public function category()
    {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }
}
