@extends('layouts.app')

@section('seo_title', 'Back to School Programs | Younimatiq')
@section('seo_description', 'Fun, hands-on back-to-school programs in coding, robotics, art, and more for K-12 students.')

@section('content')
<x-brand-hero class="mt-8 mb-8" eyebrow="Back to School 2026">
    <h1 class="brand-title">Back to School <span class="text-brand-accent">Programs</span></h1>
    @php $demoPrice = (float) (\App\Models\Setting::where('key', 'demo_class_price')->value('value') ?? 0); @endphp
    <p class="brand-description">Fun, hands-on programs in coding, robotics, art, and more. 
        @if($demoPrice <= 0)
            Book a Free demo or enroll today.
        @else
            Book a {{ \App\Services\CurrencyService::format($demoPrice) }} demo or enroll today.
        @endif
    </p>
</x-brand-hero>

<div class="pb-20 px-4 md:px-6 xl:px-8 max-w-[1440px] mx-auto">
    @if(($campCategories ?? collect())->isNotEmpty())
        <div class="flex flex-wrap gap-2 mb-10 justify-center">
            <a href="{{ route('back-to-school') }}" class="px-4 py-2 rounded-full text-sm font-bold bg-primary text-white">All Programs</a>
            @foreach($campCategories as $cat)
                <a href="{{ route('courses.category', $cat->slug) }}" class="px-4 py-2 rounded-full text-sm font-bold bg-surface-container-high text-on-surface hover:bg-primary/10 transition-colors">{{ $cat->name }}</a>
            @endforeach
        </div>
    @endif

    <p class="text-on-surface-variant font-medium mb-6 text-center sm:text-left">
        Showing <span class="font-bold text-primary">{{ $courses->firstItem() ?? 0 }}–{{ $courses->lastItem() ?? 0 }}</span>
        of <span class="font-bold text-primary">{{ $courses->total() }}</span>
    </p>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($courses as $course)
            <x-course-card :course="$course" badge="Camp" :show-demo="true" />
        @empty
            <div class="col-span-full clay-card p-6 md:p-16 text-center text-on-surface-variant">
                <span class="text-6xl mb-4 block">🏕️</span>
                <p class="font-bold text-lg">Back to school programs coming soon!</p>
                <a href="{{ route('contact') }}" class="mt-4 link-primary">Get notified</a>
            </div>
        @endforelse
    </div>

    <x-course-pagination :paginator="$courses" />
</div>
@endsection
