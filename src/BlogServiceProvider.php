<?php

namespace Ceniver\Blog;

use Ceniver\Blog\Console\InstallCommand;
use Ceniver\Blog\Http\Middleware\BacklinkReporterMiddleware;
use Ceniver\Blog\Models\BlogCategory;
use Ceniver\Blog\Services\HeadBuilder;
use Ceniver\Blog\Services\PageSeoService;
use Ceniver\Blog\Services\SeoService;
use Ceniver\Blog\Services\SiteConfigService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class BlogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/blog.php', 'blog');

        $this->app->singleton(SeoService::class);
        $this->app->singleton(PageSeoService::class);
        $this->app->singleton(HeadBuilder::class);
        $this->app->singleton(SiteConfigService::class);
    }

    public function boot(): void
    {
        // Routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        // Migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'blog');

        // View Composer
        View::composer(['blog::layouts.blog', 'blog::blog.*'], function ($view) {
            $siteConfig = app(SiteConfigService::class);
            $seo = app(SeoService::class);
            $locale = request()->route('locale') ?? $siteConfig->defaultLocale();

            // Migration çalışmamış olabilir — tablo yoksa boş collection döndür
            try {
                $categories = BlogCategory::where('is_active', true)->orderBy('sort_order')->get();
            } catch (\Exception $e) {
                $categories = collect();
            }

            $view->with([
                'seo'              => $seo,
                'siteConfig'       => $siteConfig,
                'navCategories'    => $categories,
                'currentLocale'    => $locale,
                'supportedLocales' => $siteConfig->supportedLocales(),
            ]);
        });

        // Backlink reporter middleware (web group)
        $this->app['router']->pushMiddlewareToGroup('web', BacklinkReporterMiddleware::class);

        // Artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);

            // Publishable resources
            $this->publishes([
                __DIR__ . '/../config/blog.php' => config_path('blog.php'),
            ], 'blog-config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/blog'),
            ], 'blog-views');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'blog-migrations');
        }
    }
}
