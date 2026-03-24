<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Master Panel Connection
    |--------------------------------------------------------------------------
    */
    'master_url' => env('BLOG_MASTER_URL'),
    'master_api_key' => env('BLOG_MASTER_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Locale
    |--------------------------------------------------------------------------
    */
    'default_locale' => env('BLOG_DEFAULT_LOCALE', 'tr'),

    /*
    |--------------------------------------------------------------------------
    | Route Prefixes & Middleware
    |--------------------------------------------------------------------------
    */
    'route_prefix' => env('BLOG_ROUTE_PREFIX', ''),
    'api_prefix' => env('BLOG_API_PREFIX', 'api'),
    'middleware' => ['web'],
    'api_middleware' => ['api'],

    /*
    |--------------------------------------------------------------------------
    | Sitemap Defaults
    |--------------------------------------------------------------------------
    | Master panel'den ayar gelmediğinde bu default'lar kullanılır.
    | Master'dan push geldiğinde seo_config.json üzerinden override edilir.
    */
    'sitemap' => [
        'homepage_priority'  => '1.0',
        'homepage_frequency' => 'daily',
        'article_priority'   => '0.8',
        'article_frequency'  => 'weekly',
        'category_priority'  => '0.6',
        'category_frequency' => 'monthly',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sitemap — Ek URL'ler (Geliştirici)
    |--------------------------------------------------------------------------
    | Blog dışındaki sayfaları sitemap'e dahil etmek için buraya ekleyin.
    | {locale} placeholder'ı desteklenen tüm diller için otomatik genişler.
    |
    | Örnek:
    |   ['loc' => '/{locale}/about', 'changefreq' => 'monthly', 'priority' => '0.5'],
    |   ['loc' => '/pricing',        'changefreq' => 'weekly',  'priority' => '0.7'],
    */
    'sitemap_urls' => [],

    /*
    |--------------------------------------------------------------------------
    | Page Definitions (for master panel SEO templates)
    |--------------------------------------------------------------------------
    */
    'pages' => [
        [
            'page_type' => 'homepage',
            'name'      => 'Ana Sayfa',
            'variables' => ['{site_name}', '{locale}'],
        ],
        [
            'page_type' => 'blog_index',
            'name'      => 'Blog Listesi',
            'variables' => ['{site_name}', '{locale}'],
        ],
        [
            'page_type' => 'blog_category',
            'name'      => 'Kategori Sayfası',
            'variables' => ['{site_name}', '{category}', '{locale}'],
        ],
        [
            'page_type' => 'blog_article',
            'name'      => 'Makale Detay',
            'variables' => ['{site_name}', '{title}', '{excerpt}', '{locale}'],
        ],
    ],

];
