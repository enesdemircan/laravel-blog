<!DOCTYPE html>
<html lang="{{ $currentLocale }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {!! $headHtml ?? '' !!}

    <link rel="icon" href="/favicon.ico">
    @yield('head')

    {{-- Tailwind CDN (paket kullanıcısı kendi asset pipeline'ını kullanabilir) --}}
    <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
</head>
<body class="bg-white text-gray-900 antialiased min-h-screen flex flex-col">

    {{-- Header --}}
    <header class="border-b border-gray-100 bg-white sticky top-0 z-40">
        <div class="max-w-5xl mx-auto px-4 sm:px-6">
            <div class="flex items-center justify-between h-16">
                {{-- Logo / Blog Name --}}
                <a href="{{ route('blog.index', $currentLocale) }}" class="text-xl font-bold text-gray-900 hover:text-gray-700 transition-colors">
                    {{ $siteConfig->blogName() ?? $seo->siteName() }}
                </a>

                {{-- Navigation --}}
                <nav class="hidden md:flex items-center gap-6">
                    <a href="{{ route('blog.index', $currentLocale) }}"
                       class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">Ana Sayfa</a>
                    @foreach($navCategories as $cat)
                        @php $catTrans = $cat->translations[$currentLocale] ?? null; @endphp
                        @if($catTrans)
                        <a href="{{ route('blog.category', [$currentLocale, $catTrans['slug']]) }}"
                           class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">{{ $catTrans['name'] }}</a>
                        @endif
                    @endforeach
                </nav>

                <div class="flex items-center gap-3">
                    {{-- Language Switcher --}}
                    @if(count($supportedLocales) > 1)
                    <div class="flex items-center gap-1">
                        @foreach($supportedLocales as $loc)
                            <a href="{{ url('/' . $loc . '/' . ltrim(str_replace('/' . $currentLocale, '', request()->path()), '/')) }}"
                               class="px-2 py-1 text-xs font-medium rounded uppercase {{ $loc === $currentLocale ? 'bg-gray-900 text-white' : 'text-gray-500 hover:text-gray-900' }}">{{ $loc }}</a>
                        @endforeach
                    </div>
                    @endif

                    {{-- Mobile Menu --}}
                    <button onclick="document.getElementById('mobileMenu').classList.toggle('hidden')" class="md:hidden p-2 text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                </div>
            </div>

            {{-- Mobile Nav --}}
            <div id="mobileMenu" class="hidden md:hidden pb-4 border-t border-gray-100 pt-3">
                <a href="{{ route('blog.index', $currentLocale) }}" class="block py-2 text-sm text-gray-700">Ana Sayfa</a>
                @foreach($navCategories as $cat)
                    @php $catTrans = $cat->translations[$currentLocale] ?? null; @endphp
                    @if($catTrans)
                    <a href="{{ route('blog.category', [$currentLocale, $catTrans['slug']]) }}" class="block py-2 text-sm text-gray-700">{{ $catTrans['name'] }}</a>
                    @endif
                @endforeach
            </div>
        </div>
    </header>

    {{-- Content --}}
    <main class="flex-1">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="border-t border-gray-100 mt-16">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-gray-500">
                <p>&copy; {{ date('Y') }} {{ $siteConfig->blogName() ?? $seo->siteName() }}</p>
                <div class="flex items-center gap-4">
                    @if($seo->twitter_handle)<a href="https://twitter.com/{{ ltrim($seo->twitter_handle, '@') }}" target="_blank" rel="noopener" class="hover:text-gray-700">Twitter</a>@endif
                    @if($seo->facebook_url)<a href="{{ $seo->facebook_url }}" target="_blank" rel="noopener" class="hover:text-gray-700">Facebook</a>@endif
                    @if($seo->instagram_url)<a href="{{ $seo->instagram_url }}" target="_blank" rel="noopener" class="hover:text-gray-700">Instagram</a>@endif
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
