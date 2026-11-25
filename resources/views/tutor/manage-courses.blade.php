@extends('layouts.dashboard')

@section('title', 'My Assigned Classes')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="font-headline-xl text-headline-xl text-on-surface mb-2">My Assigned Classes</h1>
            <p class="font-body-lg text-body-lg text-on-surface-variant">View the classes you have been assigned to and access the course materials.</p>
        </div>
        <div>
            <a href="{{ route('tutor.courses.create') }}" class="btn-primary flex items-center gap-2">
                <span class="material-symbols-outlined">add</span> Create Course
            </a>
        </div>
    </div>

    <div>
        <div class="space-y-6">
            @forelse($courses as $course)
                <div class="clay-card bg-white p-6 md:p-8 space-y-6">
                    
                    <!-- Course Header -->
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 border-b border-outline-variant/20 pb-6">
                        <div>
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="font-headline-md text-2xl font-bold text-on-surface">{{ $course->title }}</h3>
                                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider {{ $course->format === 'tutoring' ? 'bg-warning-container text-warning' : 'bg-primary-container text-on-primary-container' }}">
                                    {{ $course->format === 'tutoring' ? '1:1 Tutoring' : 'Group Class' }}
                                </span>
                                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-surface-container-high text-on-surface-variant">
                                    {{ $course->course_type }}
                                </span>
                                @if($course->status === 'published')
                                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-tertiary-fixed text-tertiary">Published</span>
                                @elseif($course->status === 'pending')
                                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-warning-container text-warning">Under Review</span>
                                @elseif($course->status === 'rejected')
                                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-error-container text-error">Changes Required</span>
                                @else
                                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-surface-container-low text-on-surface">Draft</span>
                                @endif
                            </div>
                            <p class="text-on-surface-variant text-sm mt-2">Price: <strong class="text-primary font-bold">${{ number_format($course->price, 2) }}</strong></p>
                            
                            @if($course->nextSession)
                                <div class="mt-4 p-4 bg-primary-container/30 rounded-xl border border-primary/20 flex items-center gap-4 flex-wrap">
                                    <div class="flex items-center gap-2 text-primary font-bold">
                                        <span class="material-symbols-outlined">event</span>
                                        <span>Next Session: {{ $course->nextSession->start_time->format('M d, Y @ h:i A') }} ({{ $course->nextSession->duration_minutes }} min)</span>
                                    </div>
                                    @if($course->nextSession->start_url || $course->nextSession->join_url)
                                        <a href="{{ $course->nextSession->start_url ?? $course->nextSession->join_url }}" target="_blank" class="btn-primary py-2 px-5 text-sm flex items-center gap-2 shadow-md">
                                            <span class="material-symbols-outlined text-sm">videocam</span> Start Class (Host)
                                        </a>
                                    @else
                                        <span class="text-sm text-on-surface-variant italic">Class link pending</span>
                                    @endif
                                </div>
                            @else
                                <div class="mt-4 p-3 bg-surface-container-low rounded-xl border border-outline-variant/30 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-on-surface-variant">schedule</span>
                                    <span class="text-sm text-on-surface-variant">No upcoming sessions scheduled.</span>
                                </div>
                            @endif
                        </div>
                        
                        <div class="flex flex-col gap-3">
                            <a href="{{ route('tutor.dashboard') }}" class="px-6 py-3 bg-surface-container hover:bg-surface-container-high border border-outline text-on-surface font-bold rounded-xl transition-colors text-center w-full">
                                Classroom Hub
                            </a>
                            @if($course->tutor_id === auth()->id())
                                <div class="flex gap-2">
                                    <a href="{{ route('tutor.courses.edit', $course) }}" class="flex-1 px-4 py-2 bg-primary/10 text-primary font-bold rounded-xl hover:bg-primary/20 text-center text-sm">Edit</a>
                                    <form action="{{ route('tutor.courses.destroy', $course) }}" method="POST" class="flex-1" onsubmit="return confirm('Are you sure you want to delete this course?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="w-full px-4 py-2 bg-error-container text-on-error-container font-bold rounded-xl hover:bg-error/20 text-center text-sm">Delete</button>
                                    </form>
                                </div>
                                @if(in_array($course->status, ['draft', 'rejected']))
                                    <form action="{{ route('tutor.courses.submit', $course) }}" method="POST" class="mt-2" onsubmit="return confirm('Submit this course to the admin for review and publishing?')">
                                        @csrf
                                        <button type="submit" class="w-full px-4 py-2 bg-warning-container text-warning font-bold rounded-xl hover:bg-warning-container text-center text-sm flex justify-center items-center gap-2">
                                            <span class="material-symbols-outlined text-sm">send</span> Submit for Review
                                        </button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    </div>
                    
                    <!-- Syllabus Units Section -->
                    <div class="space-y-6">
                        <div class="flex items-center justify-between">
                            <h4 class="font-bold text-base text-on-surface flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary">view_list</span> Syllabus Outline
                            </h4>
                        </div>

                        <!-- Syllabus Units List -->
                        @if($course->units && $course->units->count() > 0)
                            <div class="space-y-4">
                                @foreach($course->units as $unit)
                                    <div class="p-5 border border-outline-variant/30 rounded-2xl bg-surface-container-low/50">
                                        <div class="font-bold text-base text-on-surface mb-1 flex items-center gap-2">
                                            <span class="w-6 h-6 rounded-full bg-primary/10 text-primary text-xs flex items-center justify-center font-bold">{{ $unit->order }}</span>
                                            {{ $unit->title }}
                                        </div>
                                        @if($unit->description)
                                            <p class="text-xs text-on-surface-variant mb-3">{{ $unit->description }}</p>
                                        @endif

                                        <!-- Unit Modules -->
                                        <div class="space-y-2 pl-4 border-l-2 border-primary/20 mt-3">
                                            @forelse($unit->modules as $mod)
                                                <div class="p-3 bg-white rounded-xl border border-outline-variant/20 flex items-center justify-between text-sm">
                                                    <span class="font-medium text-on-surface">{{ $mod->title }}</span>
                                                    <span class="text-xs px-2 py-0.5 rounded bg-surface-container text-on-surface-variant uppercase font-bold">{{ $mod->content_type ?? $mod->module_type }}</span>
                                                </div>
                                            @empty
                                                <p class="text-xs text-on-surface-variant italic">No lessons in this unit yet.</p>
                                            @endforelse
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                    </div>
                </div>
            @empty
                <div class="clay-card bg-white p-6 md:p-12 text-center">
                    <span class="material-symbols-outlined text-4xl text-on-surface-variant mb-2">menu_book</span>
                    <h3 class="font-bold text-lg text-on-surface mb-1">No assigned classes</h3>
                    <p class="text-sm text-on-surface-variant">You have not been assigned to any classes yet.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
