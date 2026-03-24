<?php

namespace Ceniver\Blog\Models;

use Illuminate\Database\Eloquent\Model;

class BlogArticleTranslation extends Model
{
    protected $table = 'blog_article_translations';

    protected $fillable = [
        'article_id', 'locale', 'title', 'slug', 'content',
        'excerpt', 'meta_title', 'meta_description', 'featured_image', 'is_ai_generated',
        'focus_keyword', 'og_title', 'og_description', 'canonical_url',
        'schema_type', 'schema_json', 'robots_noindex', 'robots_nofollow',
    ];

    protected $casts = [
        'is_ai_generated' => 'boolean',
        'robots_noindex'  => 'boolean',
        'robots_nofollow' => 'boolean',
    ];

    public function article()
    {
        return $this->belongsTo(BlogArticle::class, 'article_id');
    }
}
