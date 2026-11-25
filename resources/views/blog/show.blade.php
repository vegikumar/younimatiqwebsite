@extends('layouts.app')

@section('seo_title', $seoTitle)
@section('seo_description', $seoDescription)
@section('seo_image', $seoImage)

@section('content')
<x-brand-hero class="mt-8 mb-8" eyebrow="{{ $blog->published_at?->format('F d, Y') }} · {{ $blog->author ?? 'Younimatiq' }}">
    <h1 class="brand-title">{{ $blog->title }}</h1>
    @if($blog->excerpt)
        <p class="brand-description">{{ $blog->excerpt }}</p>
    @endif
</x-brand-hero>

<article class="pb-20 px-4 md:px-8 max-w-4xl mx-auto">
    @if($blog->featured_image)
        <img src="{{ $blog->featured_image }}" alt="{{ $blog->title }}" class="w-full rounded-[2rem] shadow-xl mb-8">
    @endif

    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 bg-white">
        <div class="prose prose-lg max-w-none text-on-surface leading-relaxed space-y-4">
            {!! $blog->content !!}
        </div>
    </div>

    <div class="mt-16 pt-8 border-t border-outline-variant/20 flex justify-between items-center flex-wrap gap-4">
        <a href="{{ route('blog.index') }}" class="link-primary inline-flex items-center gap-2">
            <span class="material-symbols-outlined">arrow_back</span> Back to Blog
        </a>
        <a href="{{ route('register.student') }}" class="btn-primary">Join Younimatiq</a>
    </div>
</article>
@endsection
