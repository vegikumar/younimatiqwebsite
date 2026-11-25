@extends('layouts.app')

@section('seo_title', 'Page not found (404) | Younimatiq')
@section('seo_description', 'The page you are looking for could not be found. Explore Younimatiq courses, programs and resources instead.')
@section('seo_robots', 'noindex, follow')

@section('content')
<div class="px-4 md:px-6 xl:px-8 max-w-3xl mx-auto py-24 text-center">
    <p class="font-head text-7xl md:text-9xl font-extrabold text-primary">404</p>
    <h1 class="font-head text-3xl md:text-4xl font-extrabold text-on-surface mt-4">Page not found</h1>
    <p class="text-on-surface mt-4 max-w-xl mx-auto">
        Sorry — the page you were looking for doesn’t exist, may have moved, or is temporarily unavailable.
        Let’s get you back on track.
    </p>
    <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
        <a href="{{ url('/') }}" class="btn-enroll">Back to Home</a>
        <a href="{{ route('courses.index') }}" class="btn-primary">Browse Courses</a>
        <a href="{{ route('contact') }}" class="btn-secondary">Contact Support</a>
    </div>
</div>
@endsection
