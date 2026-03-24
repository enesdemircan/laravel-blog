@extends('blog::layouts.blog')

@php
    $catTrans = $article->category?->translations[$locale] ?? null;
    $imageUrl = $translation->featured_image ? asset('storage/' . $translation->featured_image) : null;
@endphp

@section('content')
<article class="max-w-3xl mx-auto px-4 sm:px-6 py-12" itemscope itemtype="https://schema.org/BlogPosting">

    {{-- Breadcrumb --}}
    <nav aria-label="Breadcrumb" class="text-sm text-gray-500 mb-6">
        <ol class="flex items-center gap-1" itemscope itemtype="https://schema.org/BreadcrumbList">
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <a href="{{ route('blog.index', $locale) }}" itemprop="item" class="hover:text-gray-900">
                    <span itemprop="name">{{ __('blog::blog.blog') }}</span>
                </a>
                <meta itemprop="position" content="1">
            </li>
            @if($catTrans)
            <li class="mx-1">/</li>
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <a href="{{ route('blog.category', [$locale, $catTrans['slug']]) }}" itemprop="item" class="hover:text-gray-900">
                    <span itemprop="name">{{ $catTrans['name'] }}</span>
                </a>
                <meta itemprop="position" content="2">
            </li>
            @endif
            <li class="mx-1">/</li>
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <span itemprop="name" class="text-gray-900 font-medium">{{ Str::limit($translation->title, 40) }}</span>
                <meta itemprop="position" content="{{ $catTrans ? 3 : 2 }}">
            </li>
        </ol>
    </nav>

    {{-- Header --}}
    <header class="mb-8">
        <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 leading-tight" itemprop="headline">{{ $translation->title }}</h1>
        <div class="mt-4 flex flex-wrap items-center gap-3 text-sm text-gray-500">
            <time datetime="{{ $article->published_at?->toIso8601String() }}" itemprop="datePublished">{{ $article->published_at?->format('d.m.Y') }}</time>
            @if($catTrans)
            <span>&middot;</span>
            <a href="{{ route('blog.category', [$locale, $catTrans['slug']]) }}" class="text-blue-600 hover:underline" rel="tag">{{ $catTrans['name'] }}</a>
            @endif
            @if($seo->author_name)
            <span>&middot;</span>
            <span itemprop="author" itemscope itemtype="https://schema.org/Person">
                <span itemprop="name">{{ $seo->author_name }}</span>
            </span>
            @endif
            @if($translation->is_ai_generated)
            <span>&middot;</span>
            <span class="inline-flex items-center gap-1 text-xs text-purple-600 bg-purple-50 px-2 py-0.5 rounded-full font-medium">AI</span>
            @endif
        </div>
    </header>

    {{-- Featured Image --}}
    @if($imageUrl)
    <figure class="mb-10">
        <img src="{{ $imageUrl }}" alt="{{ $translation->title }}" class="w-full rounded-xl" loading="lazy">
    </figure>
    @endif

    {{-- Content --}}
    <div class="prose prose-lg prose-gray max-w-none
                prose-headings:font-bold prose-headings:text-gray-900
                prose-a:text-blue-600 prose-a:no-underline hover:prose-a:underline
                prose-img:rounded-xl" itemprop="articleBody">
        {!! $translation->content !!}
    </div>

    {{-- Language alternatives --}}
    @if($otherTranslations->count() > 1)
    <div class="mt-12 pt-6 border-t border-gray-100">
        <p class="text-sm text-gray-500 mb-2">{{ __('blog::blog.available_in_other_languages') }}</p>
        <div class="flex gap-2">
            @foreach($otherTranslations as $alt)
                @if($alt->locale !== $locale)
                <a href="{{ route('blog.show', [$alt->locale, $alt->slug]) }}"
                   class="px-3 py-1 text-sm font-medium rounded bg-gray-100 text-gray-700 hover:bg-gray-200 uppercase" hreflang="{{ $alt->locale }}">{{ $alt->locale }}</a>
                @endif
            @endforeach
        </div>
    </div>
    @endif
</article>
@endsection
