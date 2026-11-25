@extends('layouts.app')

@section('seo_title', $seoTitle)
@section('seo_description', $seoDescription)
@section('seo_image', $seoImage)

@section('content')
<x-brand-hero class="mt-8 mb-8" eyebrow="{{ $page->title }}">
    <h1 class="brand-title">{{ $page->title }}</h1>
</x-brand-hero>

<div class="pb-20 px-4 md:px-8 max-w-4xl mx-auto">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 bg-white">
        <div class="prose prose-lg max-w-none text-on-surface leading-relaxed space-y-4">
            {!! $page->content !!}
        </div>
    </div>

    <div class="mt-8 text-center">
        <a href="{{ url('/') }}" class="link-primary inline-flex items-center gap-2">
            <span class="material-symbols-outlined">home</span> Back to Home
        </a>
    </div>
</div>
@endsection
