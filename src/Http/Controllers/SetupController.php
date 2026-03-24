<?php

namespace Ceniver\Blog\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;

class SetupController extends Controller
{
    public function index()
    {
        if (!empty(config('blog.master_api_key')) && !empty(config('blog.master_url'))) {
            $locale = config('blog.default_locale', 'tr');
            return redirect()->route('blog.index', $locale);
        }

        return view('blog::blog.setup');
    }

    public function store(Request $request)
    {
        $request->validate([
            'master_url'     => 'required|url',
            'master_api_key' => 'required|string|min:10',
        ]);

        $masterUrl = rtrim($request->master_url, '/');
        $apiKey    = $request->master_api_key;

        // Bağlantıyı test et
        try {
            $response = Http::timeout(10)->get($masterUrl);

            if (!$response->successful() && $response->status() !== 302) {
                return back()->withInput()->withErrors([
                    'connection' => 'Master sunucuya erişilemiyor. URL\'yi kontrol edin. (HTTP ' . $response->status() . ')',
                ]);
            }
        } catch (\Exception $e) {
            return back()->withInput()->withErrors([
                'connection' => 'Master sunucuya bağlanılamadı: ' . $e->getMessage(),
            ]);
        }

        // .env dosyasını güncelle
        $this->updateEnv([
            'BLOG_MASTER_URL'     => $masterUrl,
            'BLOG_MASTER_API_KEY' => $apiKey,
        ]);

        $locale = config('blog.default_locale', 'tr');
        return view('blog::blog.setup-success', ['locale' => $locale]);
    }

    private function updateEnv(array $values): void
    {
        $envPath = base_path('.env');
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
