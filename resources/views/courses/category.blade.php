@extends('layouts.app')

@section('seo_title', $seoTitle)
@section('seo_description', $seoDescription)
@section('seo_image', $seoImage)

@section('content')
<x-brand-hero class="mt-8 mb-8" :eyebrow="$category->name">
    <h1 class="brand-title"><span class="text-brand-accent">{{ $category->name }}</span> Courses</h1>
    @if($category->description)
        <p class="brand-description">{{ $category->description }}</p>
    @endif
</x-brand-hero>

<div class="pb-20 px-4 md:px-6 xl:px-8 max-w-[1440px] mx-auto">
    <p class="text-on-surface-variant font-medium mb-6">
        Showing <span class="font-bold text-primary">{{ $courses->firstItem() ?? 0 }}–{{ $courses->lastItem() ?? 0 }}</span>
        of <span class="font-bold text-primary">{{ $courses->total() }}</span>
    </p>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($courses as $course)
            <x-course-card :course="$course" :show-demo="true" />
        @empty
            <div class="col-span-full clay-card p-6 md:p-16 text-center text-on-surface-variant">
                <p class="font-bold text-lg">No courses in this category yet.</p>
                <a href="{{ route('courses.index') }}" class="mt-4 link-primary">Browse all courses</a>
            </div>
        @endforelse
    </div>

    <x-course-pagination :paginator="$courses" />
</div>
@endsection
