<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @include('partials.google-analytics')
    @include('partials.meta-pixel')
    @include('partials.microsoft-clarity')

    {{-- Homepage LCP: start hero fetch before SEO/GA/fonts (critical for Slow 4G) --}}
    @if(request()->routeIs('home'))
        <link rel="preload" as="image" type="image/webp" href="{{ asset('images/younimatiq-hero-poster-800.webp') }}?v=20261114c" fetchpriority="high">
    @endif

    @php
        $globalSeo = \App\Support\SeoHelper::globalDefaults();
        $pageTitle = View::hasSection('seo_title') ? View::getSection('seo_title') : $globalSeo['title'];
        $pageDescription = View::hasSection('seo_description') ? View::getSection('seo_description') : $globalSeo['description'];
        $pageKeywords = View::hasSection('seo_keywords') ? View::getSection('seo_keywords') : $globalSeo['keywords'];
        $pageImage = View::hasSection('seo_image') ? View::getSection('seo_image') : null;
        $pageOgType = View::hasSection('og_type') ? View::getSection('og_type') : 'website';
        $schemaType = View::hasSection('schema_type') ? View::getSection('schema_type') : 'WebPage';
        $pageRobots = View::hasSection('seo_robots')
            ? View::getSection('seo_robots')
            : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
        $schemaFaqs = $schemaFaqs ?? [];
        $courseSchema = $courseSchema ?? null;
        $breadcrumbs = $breadcrumbs ?? [];
    @endphp

    @include('partials.favicon')

    <x-seo-meta
        :title="$pageTitle"
        :description="$pageDescription"
        :keywords="$pageKeywords"
        :image="$pageImage"
        :imageAlt="$pageTitle"
        :type="$pageOgType"
        :robots="$pageRobots"
    />

    <x-schema-org
        :type="$schemaType"
        :name="$pageTitle"
        :description="$pageDescription"
        :url="request()->url()"
        :faqs="$schemaFaqs"
        :course="$courseSchema"
        :breadcrumbs="$breadcrumbs"
    />

    @yield('schema_faq')
    @stack('head')

    <!-- Display fonts: optional = no late swap CLS on Slow 4G -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@700;800&family=Nunito:wght@500;600;700;800&display=optional" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@700;800&family=Nunito:wght@500;600;700;800&display=optional" rel="stylesheet">
    </noscript>

    <!-- Self-hosted Quicksand: optional fallback -->
    <link rel="preload" as="font" type="font/woff2" href="{{ asset('fonts/quicksand/quicksand-latin.woff2') }}" crossorigin>
    <style>
        @font-face {
            font-family: 'Quicksand';
            font-style: normal;
            font-weight: 400 700;
            font-display: optional;
            src: url('{{ asset('fonts/quicksand/quicksand-vietnamese.woff2') }}') format('woff2');
            unicode-range: U+0102-0103, U+0110-0111, U+0128-0129, U+0168-0169, U+01A0-01A1, U+01AF-01B0, U+0300-0301, U+0303-0304, U+0308-0309, U+0323, U+0329, U+1EA0-1EF9, U+20AB;
        }
        @font-face {
            font-family: 'Quicksand';
            font-style: normal;
            font-weight: 400 700;
            font-display: optional;
            src: url('{{ asset('fonts/quicksand/quicksand-latin-ext.woff2') }}') format('woff2');
            unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF;
        }
        @font-face {
            font-family: 'Quicksand';
            font-style: normal;
            font-weight: 400 700;
            font-display: optional;
            src: url('{{ asset('fonts/quicksand/quicksand-latin.woff2') }}') format('woff2');
            unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
        }
        :root {
            --color-primary: #7130D8;
            --color-on-primary: #fff;
            --color-on-surface: #1F1635;
            --color-background: #FFFDF8;
            --color-surface-container-low: #FFFDF8;
            --radius: 9999px;
            --ym-purple: #7130D8;
            --ym-purple-deep: #4D1FA3;
            --ym-orange: #FF9800;
            --ym-orange-deep: #C77700;
            --ym-blue: #087FF5;
            --ym-green: #38B91F;
            --ym-pink: #F52B76;
            --ym-yellow: #FFC400;
            --brand-gradient: linear-gradient(145deg, #FFF9F0 0%, #F4ECFF 28%, #E8F5FF 55%, #FFF4E8 78%, #F0FFF0 100%);
            --brand-gradient-cta: linear-gradient(115deg, #5A22C0 0%, #7130D8 40%, #8B4FF0 75%, #087FF5 100%);
            --brand-panel-shadow: 0 24px 48px -18px rgba(113, 48, 216, 0.28);
        }
        /* Critical above-the-fold shell — reduce FOUC / CLS before full CSS */
        body { margin: 0; font-family: 'Quicksand', system-ui, sans-serif; background: #FFFDF8; color: #1F1635; }
        .custom-header { position: sticky; top: 0; z-index: 50; background: rgba(255,253,248,.92); min-height: 64px; }
        .hero-panel { background: var(--brand-gradient); }
        .hero-media { width: 100%; max-width: 420px; }
        .hero-media img,
        .hero-media video { width: 100%; height: auto; aspect-ratio: 16 / 9; display: block; object-fit: cover; }
        @media (min-width: 1024px) {
            .hero-media { max-width: none; }
        }
        .btn-enroll, .see-how {
            display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
            min-height: 48px; padding: .75rem 1.5rem; border-radius: 9999px;
            font-weight: 800; text-decoration: none; box-sizing: border-box;
        }
        .btn-enroll { background: #7130D8; color: #fff; border: none; box-shadow: 0 5px 0 #4D1FA3; }
        .see-how { background: #fff; color: #7130D8; border: 2px solid #7130D8; box-shadow: 0 4px 0 #4D1FA3; }
        /* Hide ligature text until icon font is ready (avoids "arrow_forward" flash) */
        .material-symbols-outlined {
            font-family: 'Material Symbols Outlined', sans-serif;
            font-weight: normal;
            font-style: normal;
            font-size: 1.25rem;
            line-height: 1;
            letter-spacing: normal;
            text-transform: none;
            display: inline-block;
            white-space: nowrap;
            word-wrap: normal;
            direction: ltr;
            -webkit-font-smoothing: antialiased;
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            width: 1.25em;
            height: 1.25em;
            overflow: hidden;
            vertical-align: middle;
            visibility: hidden;
        }
        html.ms-icons-ready .material-symbols-outlined { visibility: visible; }
        @media (max-width: 1023px) {
            .hero-float, .hero-blob, .hero-ring, .hero-media { animation: none !important; }
        }
    </style>

    <!-- Material Symbols: self-hosted full variable font (ligatures for all icons) -->
    <link rel="preload" as="font" type="font/woff2" href="{{ asset('fonts/material-symbols/material-symbols-outlined.woff2') }}?v=20261104f" crossorigin>
    <style>
        @font-face {
            font-family: 'Material Symbols Outlined';
            font-style: normal;
            font-weight: 100 700;
            font-display: block;
            src: url('{{ asset('fonts/material-symbols/material-symbols-outlined.woff2') }}?v=20261104f') format('woff2');
        }
        .material-symbols-outlined {
            font-family: 'Material Symbols Outlined' !important;
            font-weight: normal !important;
            font-feature-settings: 'liga';
            -webkit-font-feature-settings: 'liga';
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
    <script>
        (function () {
            function markReady() {
                document.documentElement.classList.add('ms-icons-ready');
            }
            // Font is tiny + preloaded: reveal icons as soon as it is parsed/ready.
            if (document.fonts && document.fonts.load) {
                document.fonts.load('24px "Material Symbols Outlined"').then(markReady).catch(markReady);
            } else {
                markReady();
            }
            // Safety: reveal icons even if font API hangs
            setTimeout(markReady, 1500);
        })();
    </script>
    <noscript>
        <style>.material-symbols-outlined { visibility: visible !important; }</style>
    </noscript>

    @vite(['resources/css/app.css', 'resources/js/marketing.js'])
    {{-- Alpine is bundled into marketing.js (self-hosted, no third-party CDN dependency) --}}

    {{-- Edge-cached pages carry a placeholder CSRF token; refresh it once loaded. --}}
    <script>
        (function () {
            if (document.querySelector('meta[name="csrf-token"]')?.content !== 'deferred') return;

            function applyToken(token) {
                if (!token) return;
                var meta = document.querySelector('meta[name="csrf-token"]');
                if (meta) meta.setAttribute('content', token);
                document.querySelectorAll('input[name="_token"]').forEach(function (input) {
                    if (input.value === 'deferred') input.value = token;
                });
            }

            fetch('/csrf-token', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (data) { if (data && data.token) applyToken(data.token); })
                .catch(function () {});
        })();
    </script>

</head>
<body class="font-sans antialiased text-on-surface bg-surface-container-low min-h-screen flex flex-col overflow-x-clip">
    @includeIf('partials.header')

    <main class="flex-grow min-w-0 max-w-full">
        @yield('content')
    </main>

    @includeIf('partials.footer')
    @includeIf('partials.lead_modal')
    @includeIf('partials.floating-buttons')
    @include('partials.anti_copy')
    @include('partials.content-protection')
    @if(request()->routeIs('student.courses.player'))
        <x-content-watermark />
    @endif
    {{-- mautic tracking disabled: emails.younimatiq.com has no DNS --}}
</body>
</html>
