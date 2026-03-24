@extends('blog::layouts.blog')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-12">

    {{-- Breadcrumb --}}
    <nav class="text-sm text-gray-500 mb-6">
        <a href="{{ route('blog.index', $locale) }}" class="hover:text-gray-900">{{ __('blog::blog.blog') }}</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900 font-medium">{{ $catName }}</span>
    </nav>

    <h1 class="text-2xl font-bold text-gray-900 mb-8">{{ $catName }}</h1>

    @if($articles->count())
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
        @foreach($articles as $article)
            @php $t = $article->translations->first(); @endphp
            @if($t)
            <article class="group">
                @if($t->featured_image)
                <a href="{{ route('blog.show', [$locale, $t->slug]) }}" class="block aspect-[16/10] rounded-xl overflow-hidden bg-gray-100 mb-4">
                    <img src="{{ asset('storage/' . $t->featured_image) }}" alt="{{ $t->title }}"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">
                </a>
                @endif
                <div>
                    <h2>
                        <a href="{{ route('blog.show', [$locale, $t->slug]) }}"
                           class="text-lg font-semibold text-gray-900 group-hover:text-blue-600 transition-colors line-clamp-2">{{ $t->title }}</a>
                    </h2>
                    @if($t->excerpt)
                    <p class="mt-2 text-sm text-gray-500 line-clamp-2">{{ $t->excerpt }}</p>
                    @endif
                    <time class="mt-2 block text-xs text-gray-400">{{ $article->published_at?->format('d.m.Y') }}</time>
                </div>
            </article>
            @endif
        @endforeach
    </div>

    <div class="mt-12">
        {{ $articles->links() }}
    </div>
    @else
    <div class="text-center py-20 text-gray-400">
        <p class="text-lg">{{ __('blog::blog.no_content_category') }}</p>
    </div>
    @endif
</div>
@endsection
