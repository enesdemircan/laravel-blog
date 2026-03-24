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
                       class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">{{ __('blog::blog.home') }}</a>
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
                    <div class="relative" id="blogLangSwitcher">
                        <button onclick="document.getElementById('blogLangDropdown').classList.toggle('hidden')"
                                class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg border border-gray-200 text-gray-600 hover:border-gray-400 hover:text-gray-900 transition-colors uppercase">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
                            {{ strtoupper($currentLocale) }}
                            <svg class="w-3 h-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div id="blogLangDropdown" class="absolute right-0 top-full mt-1 bg-white border border-gray-200 rounded-lg py-1 hidden min-w-[130px] z-50 shadow-lg">
                            @foreach($supportedLocales as $loc)
                                @php
                                    // Makale sayfasındaysa o dilin slug'ını kullan
                                    if (isset($otherTranslations) && $otherTranslations->count()) {
                                        $alt = $otherTranslations->firstWhere('locale', $loc);
                                        $langUrl = $alt ? route('blog.show', [$loc, $alt->slug]) : route('blog.index', $loc);
                                    } else {
                                        $path = request()->path();
                                        $newPath = preg_replace('#^' . preg_quote($currentLocale, '#') . '(/|$)#', $loc . '$1', $path);
                                        $langUrl = url('/' . $newPath);
                                    }
                                @endphp
                                <a href="{{ $langUrl }}"
                                   class="block px-4 py-2 text-sm {{ $loc === $currentLocale ? 'text-blue-600 bg-blue-50 font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }} transition-colors uppercase">{{ $loc }}</a>
                            @endforeach
                        </div>
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
                <a href="{{ route('blog.index', $currentLocale) }}" class="block py-2 text-sm text-gray-700">{{ __('blog::blog.home') }}</a>
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
<script>document.addEventListener('click',e=>{const s=document.getElementById('blogLangSwitcher'),d=document.getElementById('blogLangDropdown');if(s&&d&&!s.contains(e.target))d.classList.add('hidden')});</script>
</body>
</html>
