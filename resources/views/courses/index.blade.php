@extends('layouts.app')

@section('seo_title', 'Online Courses for Kids')
@section('seo_description', 'Explore online courses for kids in coding, AI, robotics, Minecraft, math, science, and more. Help your child learn, create, and build skills with Younimatiq.')

@section('content')
<x-brand-hero class="mt-8 mb-8" eyebrow="Course Catalog">
    <h1 class="brand-title">Explore <span class="text-brand-accent">All Courses</span></h1>
    <p class="brand-description">Find the perfect online class for your child's age and interests.</p>
</x-brand-hero>

<div class="pb-20 px-4 md:px-6 xl:px-8 max-w-[1440px] mx-auto">
    
    <div class="flex flex-col md:flex-row gap-8">
        <!-- Sidebar filters would go here (omitted for brevity) -->
        <div class="w-full md:w-1/4">
            <h3 class="font-bold text-lg mb-4 text-on-surface">Filter By Category</h3>
            <ul class="space-y-2">
                @foreach($categories as $category)
                    <li>
                        <a href="{{ route('courses.category', $category->slug) }}" class="text-primary hover:underline">
                            {{ $category->name }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        
        <div class="w-full md:w-3/4">
            <p class="text-on-surface-variant font-medium mb-6">
                Showing <span class="font-bold text-primary">{{ $courses->firstItem() ?? 0 }}–{{ $courses->lastItem() ?? 0 }}</span>
                of <span class="font-bold text-primary">{{ $courses->total() }}</span>
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @forelse($courses as $course)
                    <x-course-card :course="$course" :show-demo="true" />
                @empty
                    <div class="col-span-full clay-card p-6 md:p-16 text-center text-on-surface-variant">
                        <p class="font-bold text-lg">No courses found matching your criteria.</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-8">
                <x-course-pagination :paginator="$courses" />
            </div>
        </div>
    </div>
</div>
@endsection
