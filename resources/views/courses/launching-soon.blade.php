@extends('layouts.app')

@section('seo_title', $seoTitle)
@section('seo_description', $seoDescription)
@section('seo_image', $seoImage)

@section('content')
<main class="min-h-screen pt-24 pb-20 bg-surface flex flex-col items-center justify-center">
    <div class="max-w-xl mx-auto px-4 text-center">
        @if($category->image)
            <img src="{{ route('secure.asset', ['path' => $category->image]) }}" alt="{{ $category->name }}" class="w-32 h-32 object-cover rounded-full mx-auto mb-8 shadow-lg">
        @else
            <div class="w-32 h-32 bg-primary/10 text-primary rounded-full mx-auto mb-8 flex items-center justify-center shadow-lg">
                <span class="material-symbols-outlined text-5xl">rocket_launch</span>
            </div>
        @endif

        <h1 class="text-4xl md:text-5xl font-bold text-on-surface mb-4">
            {{ $category->name }}
        </h1>
        
        <p class="text-xl text-primary font-bold mb-6">Launching Soon!</p>
        
        <p class="text-lg text-on-surface-variant mb-10">
            We're currently building out exciting new courses for {{ $category->name }}. Join the waitlist to be the first to know when we launch and secure your spot!
        </p>

        @if(session('success'))
            <div class="bg-tertiary-fixed text-tertiary p-4 rounded-2xl mb-8 flex items-center gap-3">
                <span class="material-symbols-outlined text-on-tertiary-container">check_circle</span>
                <span class="font-medium text-left">{{ session('success') }}</span>
            </div>
        @else
            <form action="{{ route('courses.waitlist', $category) }}" method="POST" class="clay-card p-6 md:p-8 bg-white max-w-md mx-auto">
                @csrf
                <div class="mb-4 text-left">
                    <label for="email" class="form-label text-sm font-bold text-on-surface mb-2 block">Enter your email address</label>
                    <input type="email" id="email" name="email" required placeholder="you@example.com" autocomplete="email" inputmode="email" class="form-input w-full px-4 py-3 rounded-xl border border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all" value="{{ old('email') }}">
                    @error('email')
                        <p class="text-error text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <button type="submit" class="btn-primary w-full py-3.5 rounded-xl font-bold text-lg flex items-center justify-center gap-2 group shadow-lg hover:shadow-xl transition-all">
                    <span>Notify Me</span>
                    <span class="material-symbols-outlined group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </button>
            </form>
        @endif

        <div class="mt-12">
            <a href="{{ route('home') }}" class="text-primary font-bold hover:underline inline-flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">arrow_back</span>
                Back to Home
            </a>
        </div>
    </div>
</main>
@endsection
