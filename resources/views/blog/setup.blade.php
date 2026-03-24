<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('blog::blog.setup_title') }}</title>
    <meta name="robots" content="noindex, nofollow">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900 antialiased min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        {{-- Logo / Icon --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-900 rounded-2xl mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('blog::blog.setup_title') }}</h1>
            <p class="mt-2 text-sm text-gray-500">{{ __('blog::blog.setup_description') }}</p>
        </div>

        {{-- Form Card --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">

            {{-- Errors --}}
            @if($errors->any())
            <div class="mb-6 p-4 bg-red-50 border border-red-100 rounded-xl">
                @foreach($errors->all() as $error)
                <p class="text-sm text-red-600">{{ $error }}</p>
                @endforeach
            </div>
            @endif

            <form action="{{ route('blog.setup.store') }}" method="POST" class="space-y-5">
                @csrf

                {{-- Master URL --}}
                <div>
                    <label for="master_url" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('blog::blog.master_panel_url') }}</label>
                    <input type="url" name="master_url" id="master_url" value="{{ old('master_url') }}"
                           placeholder="https://panel.example.com"
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent transition" required>
                    <p class="mt-1 text-xs text-gray-400">{{ __('blog::blog.master_panel_url_help') }}</p>
                </div>

                {{-- API Key --}}
                <div>
                    <label for="master_api_key" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('blog::blog.api_key') }}</label>
                    <input type="text" name="master_api_key" id="master_api_key" value="{{ old('master_api_key') }}"
                           placeholder="eJK5vpubISKjoqs4NPwN1..."
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent transition" required>
                    <p class="mt-1 text-xs text-gray-400">{{ __('blog::blog.api_key_help') }}</p>
                </div>

                {{-- Submit --}}
                <button type="submit"
                        class="w-full px-4 py-2.5 bg-gray-900 text-white text-sm font-medium rounded-xl hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2 transition">
                    {{ __('blog::blog.test_and_save') }}
                </button>
            </form>
        </div>

        {{-- Help --}}
        <div class="mt-6 text-center">
            <p class="text-xs text-gray-400">
                {{ __('blog::blog.api_key_info') }}
            </p>
        </div>
    </div>

</body>
</html>
