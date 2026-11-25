<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.favicon')
    @php
        $isPortal = request()->is('admin*') || request()->is('tutor*') || request()->is('student*') || request()->is('parent*');
    @endphp
    @unless ($isPortal)
        @include('partials.google-analytics')
        @include('partials.meta-pixel')
        @include('partials.microsoft-clarity')
    @endunless
    @if (! $isPortal && request()->routeIs('home'))
        @include('partials.home-seo-meta')
        <link rel="preload" as="image" type="image/webp" href="{{ asset('images/younimatiq-hero-poster-800.webp') }}?v=20261114c" fetchpriority="high">
        @include('partials.home-aeo-schema')
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    {{-- Non-blocking fonts (same pattern as marketing layout) --}}
    @if ($isPortal)
        <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=Instrument+Sans:wght@400;500;600&family=Baloo+2:wght@700;800&family=Nunito:wght@500;600;700;800&display=swap" onload="this.onload=null;this.rel='stylesheet'">
        <noscript>
            <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=Instrument+Sans:wght@400;500;600&family=Baloo+2:wght@700;800&family=Nunito:wght@500;600;700;800&display=swap" rel="stylesheet">
        </noscript>
    @else
        <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@700;800&family=Nunito:wght@500;600;700;800&display=swap" onload="this.onload=null;this.rel='stylesheet'">
        <noscript>
            <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@700;800&family=Nunito:wght@500;600;700;800&display=swap" rel="stylesheet">
        </noscript>
    @endif
    {{-- Do not preload the full Material Symbols file (~3.9MB); load async with swap --}}
    <style>
        @font-face {
            font-family: 'Material Symbols Outlined';
            font-style: normal;
            font-weight: 100 700;
            font-display: swap;
            src: url('{{ asset('fonts/material-symbols/material-symbols-outlined.min.woff2') }}?v=20261104e') format('woff2');
        }
        .material-symbols-outlined {
            font-family: 'Material Symbols Outlined' !important;
            font-weight: normal !important;
            font-style: normal;
            font-size: 1.25rem;
            line-height: 1;
            letter-spacing: normal;
            text-transform: none !important;
            display: inline-block;
            white-space: nowrap;
            direction: ltr;
            -webkit-font-smoothing: antialiased;
            font-feature-settings: 'liga';
            -webkit-font-feature-settings: 'liga';
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }
    </style>
    {{-- portal-asset-bust: staff-shell-v3 --}}
    @viteReactRefresh
    @if ($isPortal)
        @vite(['resources/css/app.css', 'resources/js/app-portal.jsx'])
    @else
        @vite(['resources/css/app.css', 'resources/css/public-chrome.css', 'resources/js/app.jsx', 'resources/js/marketing.js'])
    @endif
    @inertiaHead
</head>
<body class="font-sans antialiased text-on-surface bg-surface-container-low min-h-screen flex flex-col">
    @if (request()->routeIs('home'))
        @include('partials.geo-crawler-home')
    @endif
    @inertia
    <script>
        (function () {
            var meta = document.querySelector('meta[name="csrf-token"]');
            if (!meta || meta.content !== 'deferred') return;
            fetch('/csrf-token', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (data) {
                    if (!data || !data.token) return;
                    meta.setAttribute('content', data.token);
                    document.querySelectorAll('input[name="_token"]').forEach(function (input) {
                        if (input.value === 'deferred') input.value = data.token;
                    });
                })
                .catch(function () {});
        })();
    </script>
</body>
</html>
