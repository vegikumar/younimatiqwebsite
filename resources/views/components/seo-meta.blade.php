@props([
    'title',
    'description',
    'keywords' => null,
    'image' => null,
    'imageAlt' => null,
    'type' => 'website',
    'robots' => 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1',
])

@php
    $siteName = config('app.name', 'Younimatiq');
    $absoluteImage = \App\Support\SeoHelper::absoluteImageUrl($image);
    $imageAltText = $imageAlt ?: $title;
    $canonical = url()->current();
@endphp

<title>{{ $title }}</title>
<meta name="description" content="{{ $description }}">
@if($keywords)
<meta name="keywords" content="{{ $keywords }}">
@endif
<meta name="robots" content="{{ $robots }}">
<meta name="author" content="Younimatiq">
<link rel="canonical" href="{{ $canonical }}">

<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:locale" content="{{ str_replace('_', '-', app()->getLocale()) }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:type" content="{{ $type }}">
<meta property="og:image" content="{{ $absoluteImage }}">
<meta property="og:image:alt" content="{{ $imageAltText }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $absoluteImage }}">
<meta name="twitter:image:alt" content="{{ $imageAltText }}">
