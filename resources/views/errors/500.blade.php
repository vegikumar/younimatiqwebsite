@extends('layouts.app')

@section('seo_title', 'Something went wrong (500) | Younimatiq')
@section('seo_description', 'An unexpected error occurred. Please try again or contact Younimatiq support.')
@section('seo_robots', 'noindex, nofollow')

@section('content')
<div class="px-4 md:px-6 xl:px-8 max-w-3xl mx-auto py-24 text-center">
    <p class="font-head text-7xl md:text-9xl font-extrabold text-primary">500</p>
    <h1 class="font-head text-3xl md:text-4xl font-extrabold text-on-surface mt-4">Something went wrong</h1>
    <p class="text-on-surface mt-4 max-w-xl mx-auto">
        We hit an unexpected error while loading this page. Please try again in a moment — if it keeps happening,
        our team is happy to help.
    </p>
    <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
        <a href="{{ url('/') }}" class="btn-enroll">Back to Home</a>
        <a href="{{ route('contact') }}" class="btn-primary">Contact Support</a>
    </div>
</div>
@endsection
