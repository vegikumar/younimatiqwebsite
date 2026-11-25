@extends('layouts.app')

@section('seo_title', 'Sitemap | Younimatiq')
@section('seo_description', 'Browse all main pages, programs, and courses on Younimatiq.')
@section('seo_robots', 'noindex, follow')

@section('content')
<section class="py-12 px-4 md:px-8 max-w-5xl mx-auto">
    <x-brand-hero class="mb-10" eyebrow="Site map">
        <h1 class="brand-title">Explore <span class="text-brand-accent">Younimatiq</span></h1>
        <p class="brand-description">A human-friendly map of our pages. Search engines still use <a class="text-primary font-semibold underline" href="{{ route('sitemap') }}">sitemap.xml</a>.</p>
    </x-brand-hero>

    <div class="space-y-10">
        @foreach($groups as $label => $items)
            <div class="clay-card p-6 md:p-8">
                <h2 class="font-bold text-xl text-on-surface mb-4">{{ $label }}</h2>
                <ul class="grid sm:grid-cols-2 gap-2">
                    @foreach($items as $item)
                        @php
                            $path = parse_url($item['loc'], PHP_URL_PATH) ?: '/';
                            $title = $path === '/' ? 'Home' : str_replace(['-', '/'], [' ', ' / '], trim($path, '/'));
                            $title = ucwords($title);
                        @endphp
                        <li>
                            <a href="{{ $item['loc'] }}" class="text-primary hover:underline font-medium text-sm md:text-base">
                                {{ $title }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
</section>
@endsection
