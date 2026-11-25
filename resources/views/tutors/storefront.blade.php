@extends('layouts.app')

@section('title', $tutor->name . ' - Teacher Storefront')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <!-- Storefront Banner -->
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden bg-white">
        <div class="flex flex-col md:flex-row items-center md:items-start gap-5 md:p-8">
            <div class="w-32 h-32 rounded-full bg-primary text-white font-extrabold flex items-center justify-center text-4xl shadow-md shrink-0">
                {{ substr($tutor->name, 0, 1) }}
            </div>
            <div class="flex-1 text-center md:text-left space-y-3">
                <div class="flex flex-wrap items-center justify-center md:justify-start gap-3">
                    <h1 class="font-headline-xl text-3xl font-extrabold text-on-surface">{{ $tutor->name }}</h1>
                    <span class="px-3 py-1 bg-warning-container text-warning rounded-full text-xs font-bold flex items-center gap-1">
                        ★ {{ $averageRating }} ({{ $reviews->count() }} reviews)
                    </span>
                    <span class="px-3 py-1 bg-primary-container text-on-primary-container rounded-full text-xs font-bold">
                        Verified Educator
                    </span>
                </div>
                <p class="text-sm text-on-surface-variant max-w-2xl">{{ $tutor->bio ?? 'Passionate educator inspiring young explorers in technology, coding, and creative problem solving.' }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-1 md:grid-cols-3 gap-5 md:p-10">
        <!-- Left: Teaching Style & Video Intro -->
        <div class="space-y-6">
            <div class="clay-card p-6 bg-white space-y-3">
                <h3 class="font-bold text-base text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">psychology</span> Teaching Philosophy & Style
                </h3>
                <p class="text-xs text-on-surface-variant leading-relaxed">
                    {{ $tutor->teaching_style ?? 'Interactive, project-based learning with hands-on exercises and supportive guidance.' }}
                </p>
            </div>

            @if($tutor->intro_video_url)
                <div class="clay-card p-6 bg-white space-y-3">
                    <h3 class="font-bold text-base text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">movie</span> Teacher Video Intro
                    </h3>
                    <a href="{{ $tutor->intro_video_url }}" target="_blank" class="btn-primary w-full py-3 text-xs font-bold flex items-center justify-center gap-1">
                        <span class="material-symbols-outlined text-sm">play_circle</span> Watch Teacher Intro Video
                    </a>
                </div>
            @endif
        </div>

        <!-- Right: Storefront Classes & Parent Reviews -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Active Classes Offered -->
            <div class="clay-card p-6 md:p-8 bg-white space-y-6">
                <h3 class="font-bold text-xl text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">school</span> Classes Offered by {{ $tutor->name }}
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @forelse($courses as $course)
                        <div class="p-5 border border-outline-variant/20 rounded-2xl bg-surface-container-low/30 flex flex-col justify-between space-y-4">
                            <div>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-primary-container text-on-primary-container inline-block mb-2">
                                    {{ $course->format === 'tutoring' ? '1:1 Tutoring' : 'Group Class' }}
                                </span>
                                <h4 class="font-bold text-base text-on-surface mb-1">{{ $course->title }}</h4>
                                <p class="text-xs text-on-surface-variant line-clamp-2">{{ $course->description }}</p>
                            </div>
                            <div class="flex items-center justify-between pt-2 border-t border-outline-variant/20">
                                <span class="font-extrabold text-primary text-base">${{ number_format($course->price, 2) }}</span>
                                <a href="{{ route('courses.show', $course) }}" class="btn-enroll py-2 px-4 text-xs font-bold">Explore & Book</a>
                            </div>
                        </div>
                    @empty
                        <p class="col-span-2 text-xs text-on-surface-variant py-6 text-center">No active class listings currently available.</p>
                    @endforelse
                </div>
            </div>

            <!-- Parent Reviews & Testimonials -->
            <div class="clay-card p-6 md:p-8 bg-white space-y-6">
                <h3 class="font-bold text-xl text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-warning">rate_review</span> Parent & Student Reviews ({{ $reviews->count() }})
                </h3>

                <div class="space-y-4">
                    @forelse($reviews as $rev)
                        <div class="p-4 border border-outline-variant/20 rounded-2xl bg-surface-container-low/30 space-y-2 text-xs">
                            <div class="flex justify-between items-baseline font-bold text-on-surface">
                                <span>{{ $rev->user->name ?? 'Parent' }}</span>
                                <span class="text-warning font-bold">★ {{ $rev->rating }} / 5</span>
                            </div>
                            <p class="text-on-surface-variant leading-relaxed">{{ $rev->comment }}</p>
                        </div>
                    @empty
                        <p class="text-xs text-on-surface-variant py-6 text-center">No parent reviews published yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
