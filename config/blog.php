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
