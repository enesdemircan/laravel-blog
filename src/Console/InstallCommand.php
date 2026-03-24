<?php

namespace Ceniver\Blog\Console;

use Ceniver\Blog\Models\BlogArticle;
use Ceniver\Blog\Models\BlogCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class InstallCommand extends Command
{
    protected $signature = 'blog:install';
    protected $description = 'Install the Ceniver Blog package';

    public function handle(): int
    {
        $this->info('');
        $this->info('  ╔══════════════════════════════════════╗');
        $this->info('  ║     Ceniver Blog - Kurulum Wizard    ║');
        $this->info('  ╚══════════════════════════════════════╝');
        $this->info('');

        // 1. Master URL
        $masterUrl = $this->ask('Master Panel URL (örn: https://panel.example.com)');
        if (!$masterUrl) {
            $this->error('Master URL gereklidir.');
            return self::FAILURE;
        }
        $masterUrl = rtrim($masterUrl, '/');

        // 2. API Key
        $apiKey = $this->ask('API Key (master panelden alınan)');
        if (!$apiKey || strlen($apiKey) < 10) {
            $this->error('API Key en az 10 karakter olmalıdır.');
            return self::FAILURE;
        }

        // 3. Default Locale
        $locale = $this->ask('Varsayılan dil kodu (örn: tr, en)', 'tr');

        // 4. .env güncelle
        $this->info('');
        $this->info('  .env dosyası güncelleniyor...');
        $this->updateEnv([
            'BLOG_MASTER_URL'     => $masterUrl,
            'BLOG_MASTER_API_KEY' => $apiKey,
            'BLOG_DEFAULT_LOCALE' => $locale,
        ]);
        $this->info('  .env güncellendi.');

        // 5. Config publish
        $this->info('  Config dosyası yayınlanıyor...');
        $this->call('vendor:publish', [
            '--tag' => 'blog-config',
            '--force' => true,
        ]);

        // 6. Migrations
        if ($this->confirm('Migration\'ları çalıştırmak ister misiniz?', true)) {
            $this->info('  Migration\'lar çalıştırılıyor...');
            $this->call('migrate');
        }

        // 7. Eski site verilerini temizle
        if (Schema::hasTable('blog_categories')) {
            $oldArticles = BlogArticle::count();
            $oldCategories = BlogCategory::count();
            if ($oldArticles > 0 || $oldCategories > 0) {
                if ($this->confirm("Veritabanında {$oldArticles} makale ve {$oldCategories} kategori bulundu. Eski verileri temizlemek ister misiniz?", true)) {
                    BlogArticle::query()->delete();
                    BlogCategory::query()->delete();
                    $this->info('  Eski veriler temizlendi.');
                }
            }
        }

        // Eski config JSON'ları temizle
        foreach (['seo_config.json', 'site_config.json', 'page_seo.json', 'redirects.json'] as $file) {
            if (Storage::exists($file)) {
                Storage::delete($file);
            }
        }

        // Eski sitemap dosyalarını temizle
        foreach (glob(public_path('sitemap*.xml')) as $sitemapFile) {
            @unlink($sitemapFile);
        }
        $this->info('  Eski config ve sitemap dosyaları temizlendi.');

        // 8. Laravel default robots.txt varsa kaldır (paketin route'u kullanılır)
        $defaultRobots = public_path('robots.txt');
        if (file_exists($defaultRobots)) {
            $content = file_get_contents($defaultRobots);
            if (str_contains($content, 'User-agent: *') && str_contains($content, 'Disallow:') && strlen($content) < 50) {
                unlink($defaultRobots);
                $this->info('  Laravel default robots.txt kaldırıldı (paket AI-optimized robots.txt sağlar).');
            }
        }

        // 8. Storage link
        if (!file_exists(public_path('storage'))) {
            $this->info('  Storage link oluşturuluyor...');
            $this->call('storage:link');
        }

        $this->info('');
        $this->info('  ✓ Ceniver Blog başarıyla kuruldu!');
        $this->info('');
        $this->info('  Rotalar:');
        $this->info("    Blog:  /{$locale}/blog");
        $this->info('    API:   /api/articles');
        $this->info('    Setup: /blog/setup');
        $this->info('');
        $this->info('  View\'ları özelleştirmek için:');
        $this->info('    php artisan vendor:publish --tag=blog-views');
        $this->info('');

        return self::SUCCESS;
    }

    private function updateEnv(array $values): void
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            $this->warn('.env dosyası bulunamadı. Lütfen manuel ekleyin.');
            return;
        }

        $content = file_get_contents($envPath);

        foreach ($values as $key => $value) {
            $escaped = str_contains($value, ' ') ? '"' . $value . '"' : $value;

            if (preg_match("/^{$key}=.*/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$escaped}", $content);
            } else {
                $content .= "\n{$key}={$escaped}";
            }
        }

        file_put_contents($envPath, $content);
    }
}
