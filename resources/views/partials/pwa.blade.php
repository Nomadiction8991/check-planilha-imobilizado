@php
    $pwaThemeColor = '#1f6f5f';
@endphp

<link rel="manifest" href="{{ asset('manifest.json') }}">
<meta name="theme-color" content="{{ $pwaThemeColor }}">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
<meta name="application-name" content="{{ config('app.name') }}">
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="apple-touch-icon" href="{{ asset('icons/pwa-180.png') }}">
<script>
    (() => {
        if (!('serviceWorker' in navigator)) {
            return;
        }

        const registerServiceWorker = () => {
            navigator.serviceWorker.register(@json(asset('sw.js')), { scope: '/' }).catch(() => {});
        };

        if (document.readyState === 'complete') {
            registerServiceWorker();
            return;
        }

        window.addEventListener('load', registerServiceWorker, { once: true });
    })();
</script>
