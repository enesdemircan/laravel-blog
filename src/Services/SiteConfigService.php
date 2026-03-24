<?php

namespace Ceniver\Blog\Services;

use Illuminate\Support\Facades\Storage;

class SiteConfigService
{
    private const FILE = 'site_config.json';

    public function get(): array
    {
        if (!Storage::exists(self::FILE)) {
            return [
                'supported_locales' => [config('blog.default_locale', 'tr')],
                'default_locale'    => config('blog.default_locale', 'tr'),
            ];
        }

        return json_decode(Storage::get(self::FILE), true) ?? [];
    }

    public function update(array $config): void
    {
        $current = $this->get();

        $merged = array_merge($current, array_filter([
            'supported_locales' => $config['supported_locales'] ?? null,
            'default_locale'    => $config['default_locale'] ?? null,
            'blog_name'         => $config['blog_name'] ?? null,
        ], fn ($v) => $v !== null));

        Storage::put(self::FILE, json_encode($merged, JSON_PRETTY_PRINT));
    }

    public function supportedLocales(): array
    {
        return $this->get()['supported_locales'] ?? [config('blog.default_locale', 'tr')];
    }

    public function defaultLocale(): string
    {
        return $this->get()['default_locale'] ?? config('blog.default_locale', 'tr');
    }

    public function blogName(): ?string
    {
        return $this->get()['blog_name'] ?? null;
    }
}
