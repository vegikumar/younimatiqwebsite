@extends('layouts.dashboard')

@section('title', 'Wishlist')

@section('content')
<div class="p-5 md:p-8 space-y-8">
    <div>
        <h1 class="font-headline-lg text-headline-lg font-bold mb-2">My Wishlist ❤️</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant">Courses you've saved for your future adventures.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($wishlists as $item)
            <div class="bg-white rounded-2xl border border-surface-container-high shadow-sm overflow-hidden group">
                <div class="h-48 bg-surface-container relative">
                    <!-- Course Thumbnail Placeholder -->
                    <div class="absolute inset-0 bg-gradient-to-br from-tertiary/20 to-primary/20 flex items-center justify-center">
                        <span class="material-symbols-outlined text-6xl text-on-surface-variant opacity-50">image</span>
                    </div>
                    <button class="absolute top-4 right-4 w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-sm text-error hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">favorite</span>
                    </button>
                </div>
                <div class="p-6">
                    <div class="text-xs font-bold text-tertiary uppercase tracking-wider mb-2">Course</div>
                    <h3 class="font-headline-md text-headline-md font-bold mb-2 group-hover:text-primary transition-colors">{{ $item->course->title }}</h3>
                    <p class="text-sm text-on-surface-variant mb-4 line-clamp-2">{{ $item->course->description }}</p>
                    <div class="flex justify-between items-center">
                        <span class="font-bold text-lg">${{ number_format($item->course->price, 2) }}</span>
                        <button class="btn-primary btn-sm">Add to Cart</button>
                    </div>
                </div>
            </div>
        @empty
            <!-- Empty State -->
            <div class="col-span-full bg-surface-container-low border-2 border-dashed border-surface-container-high rounded-2xl flex flex-col items-center justify-center p-5 md:p-8 text-center text-on-surface-variant">
                <span class="material-symbols-outlined text-4xl mb-2">search</span>
                <p class="font-bold mb-2">Looking for more?</p>
                <a href="{{ route('courses.index') }}" class="text-primary font-bold hover:underline">Browse Courses</a>
            </div>
        @endforelse
    </div>
</div>
@endsection
