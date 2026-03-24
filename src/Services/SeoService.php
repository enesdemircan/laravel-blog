<?php

namespace Ceniver\Blog\Services;

use Illuminate\Support\Facades\Storage;

class SeoService
{
    private array $data;

    public function __construct()
    {
        $this->data = Storage::exists('seo_config.json')
            ? (json_decode(Storage::get('seo_config.json'), true) ?? [])
            : [];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function __get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function siteName(): string
    {
        $name = $this->data['site_display_name'] ?? null;

        if (!$name || strtolower($name) === 'laravel') {
            return request()->getHost();
        }

        return $name;
    }

    public function siteDescription(): string
    {
        return $this->data['site_description'] ?? '';
    }

    public function authorName(): string
    {
        return $this->data['author_name'] ?? $this->siteName();
    }

    public function ga4Id(): ?string
    {
        return !empty($this->data['ga4_id']) ? $this->data['ga4_id'] : null;
    }

    public function gscVerification(): ?string
    {
        return !empty($this->data['gsc_verification']) ? $this->data['gsc_verification'] : null;
    }

    public function bingVerification(): ?string
    {
        return !empty($this->data['bing_verification']) ? $this->data['bing_verification'] : null;
    }

    public function twitterHandle(): ?string
    {
        $handle = $this->data['twitter_handle'] ?? null;
        if (!$handle) return null;
        return str_starts_with($handle, '@') ? $handle : '@' . $handle;
    }

    public function twitterCardType(): string
    {
        return $this->data['twitter_card_type'] ?? 'summary_large_image';
    }

    public function ogDefaultImage(): ?string
    {
        $img = $this->data['og_default_image'] ?? null;
        return $img ? asset('storage/' . $img) : null;
    }

    public function hreflangEnabled(): bool
    {
        return (bool) ($this->data['hreflang_enabled'] ?? false);
    }

    public function robotsTxt(): ?string
    {
        return !empty($this->data['robots_txt']) ? $this->data['robots_txt'] : null;
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
