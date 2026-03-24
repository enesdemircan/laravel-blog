<?php

namespace Ceniver\Blog\Services;

use Illuminate\Support\Facades\Storage;

class PageSeoService
{
    private array $index = [];

    public function __construct()
    {
        if (!Storage::exists('page_seo.json')) {
            return;
        }

        $rows = json_decode(Storage::get('page_seo.json'), true) ?? [];

        foreach ($rows as $row) {
            $key = ($row['page_type'] ?? '') . '||' . ($row['locale'] ?? '');
            $this->index[$key] = $row;
        }
    }

    public function forPage(string $pageType, string $locale, array $vars = []): array
    {
        $key  = $pageType . '||' . $locale;
        $row  = $this->index[$key] ?? [];

        return [
            'meta_title'        => $this->resolve($row['meta_title_template']       ?? null, $vars),
            'meta_description'  => $this->resolve($row['meta_description_template'] ?? null, $vars),
            'meta_keywords'     => $this->resolve($row['meta_keywords'] ?? null, $vars),
            'meta_author'       => $row['meta_author']    ?? null,
            'og_title'          => $this->resolve($row['og_title_template']          ?? null, $vars),
            'og_description'    => $this->resolve($row['og_description_template']   ?? null, $vars),
            'og_image_url'      => $row['og_image_url']   ?? null,
            'og_locale'         => $row['og_locale']      ?? null,
            'og_type'           => $row['og_type']        ?? null,
            'twitter_title'     => $this->resolve($row['twitter_title']       ?? null, $vars),
            'twitter_description' => $this->resolve($row['twitter_description'] ?? null, $vars),
            'twitter_image_url' => $row['twitter_image_url'] ?? null,
            'canonical_url'     => $row['canonical_url']  ?? null,
            'schema_type'       => $row['schema_type']    ?? null,
            'schema_json'       => $row['schema_json']    ?? null,
            'robots_noindex'    => (bool) ($row['robots_noindex']  ?? false),
            'robots_nofollow'   => (bool) ($row['robots_nofollow'] ?? false),
            'robots_advanced'   => $row['robots_advanced'] ?? null,
        ];
    }

    private function resolve(?string $template, array $vars): ?string
    {
        if (!$template) {
            return null;
        }

        $search  = array_map(fn($k) => '{' . $k . '}', array_keys($vars));
        $replace = array_values($vars);

        return str_replace($search, $replace, $template);
    }
}
