@extends('layouts.app')

@section('seo_title', 'Blog | Younimatiq')
@section('seo_description', 'Tips, stories, and insights from the Younimatiq learning community.')
@section('seo_image', asset('logo.webp'))

@section('content')
<x-brand-hero class="mt-8 mb-8" eyebrow="Learning Hub">
    <h1 class="brand-title">Younimatiq <span class="text-brand-accent">Blog</span></h1>
    <p class="brand-description">Tips, stories, and insights from our learning community.</p>
</x-brand-hero>

<div class="pb-20 px-4 md:px-8 max-w-7xl mx-auto">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 md:p-8">
        @forelse($blogs as $blog)
            <article class="clay-card overflow-hidden flex flex-col">
                @if($blog->featured_image)
                    <img src="{{ $blog->featured_image }}" alt="{{ $blog->title }}" class="w-full h-auto">
                @else
                    <div class="w-full aspect-video bg-gradient-to-br from-primary/20 to-secondary/20 flex items-center justify-center">
                        <span class="material-symbols-outlined text-6xl text-primary/40">article</span>
                    </div>
                @endif
                <div class="p-6 flex-1 flex flex-col">
                    <p class="text-xs font-bold text-secondary uppercase tracking-wider mb-2">{{ $blog->published_at?->format('M d, Y') }}</p>
                    <h2 class="font-headline-md font-bold text-on-surface mb-3 line-clamp-2">{{ $blog->title }}</h2>
                    <p class="text-on-surface-variant text-sm mb-4 flex-1 line-clamp-3">{{ $blog->excerpt ?? Str::limit(strip_tags($blog->content), 120) }}</p>
                    <a href="{{ route('blog.show', $blog->slug) }}" class="link-primary inline-flex items-center gap-2">
                        Read More <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </a>
                </div>
            </article>
        @empty
            <div class="col-span-full clay-card p-6 md:p-16 text-center text-on-surface-variant">
                <span class="material-symbols-outlined text-6xl mb-4">article</span>
                <p class="font-bold text-lg">No blog posts published yet.</p>
            </div>
        @endforelse
    </div>

    @if($blogs->hasPages())
        <div class="mt-12 flex justify-center">{{ $blogs->links() }}</div>
    @endif
</div>
@endsection
