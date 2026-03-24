<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kurulum Tamamlandı</title>
    <meta name="robots" content="noindex, nofollow">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900 antialiased min-h-screen flex items-center justify-center p-4">

    <div class="text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-6">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Kurulum Tamamlandı!</h1>
        <p class="text-gray-500 mb-1">Blog yapılandırması kaydedildi.</p>
        <p class="text-sm text-gray-400" id="countdown">Yönlendiriliyorsunuz<span class="loading-dots">...</span></p>
    </div>

    <script>
        const target = '/{{ $locale }}/blog';
        let attempts = 0;

        function tryRedirect() {
            attempts++;
            fetch(target, { method: 'HEAD' })
                .then(r => {
                    if (r.ok || r.status === 302) {
                        window.location.href = target;
                    } else if (attempts < 20) {
                        setTimeout(tryRedirect, 500);
                    } else {
                        window.location.href = target;
                    }
                })
                .catch(() => {
                    if (attempts < 20) {
                        setTimeout(tryRedirect, 500);
                    } else {
                        window.location.href = target;
                    }
                });
        }

        setTimeout(tryRedirect, 1500);
    </script>

</body>
</html>
