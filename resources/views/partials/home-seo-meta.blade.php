@php
    $homeUrl = url('/');
    $homeTitle = 'Online Classes for Kids: Coding, AI & Math | Younimatiq';
    $homeDescription = 'Younimatiq offers live 1:1 and small-group online classes for K–12 kids in Coding, AI, Robotics, Math, and more. Free trial with background-checked tutors.';
    $homeImage = asset('images/younimatiq-hero-poster-800.webp').'?v=20261114c';
@endphp
<title>{{ $homeTitle }}</title>
<meta name="description" content="{{ $homeDescription }}">
<link rel="canonical" href="{{ $homeUrl }}">
<meta property="og:type" content="website">
<meta property="og:site_name" content="Younimatiq">
<meta property="og:url" content="{{ $homeUrl }}">
<meta property="og:title" content="{{ $homeTitle }}">
<meta property="og:description" content="{{ $homeDescription }}">
<meta property="og:image" content="{{ $homeImage }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $homeTitle }}">
<meta name="twitter:description" content="{{ $homeDescription }}">
<meta name="twitter:image" content="{{ $homeImage }}">
<style>
    /* Hide crawlable AEO copy from sighted users without using .sr-only (many SEO tools strip that class). */
    #geo-crawler-home {
        position: absolute !important;
        width: 1px !important;
        height: 1px !important;
        padding: 0 !important;
        margin: -1px !important;
        overflow: hidden !important;
        clip: rect(0, 0, 0, 0) !important;
        white-space: nowrap !important;
        border: 0 !important;
    }
</style>
