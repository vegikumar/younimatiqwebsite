@extends('layouts.dashboard')

@section('title', 'Live Session Co-Pilot - ' . $course->title)

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden bg-white">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <span class="px-3 py-1 bg-primary-container text-on-primary-container rounded-full text-xs font-bold uppercase tracking-wider mb-2 inline-block">
                    Live Session Co-Pilot
                </span>
                <h1 class="font-headline-xl text-3xl font-extrabold text-on-surface mb-1">{{ $course->title }}</h1>
                <p class="font-body-lg text-sm text-on-surface-variant">Pre-class prep drawer: child avatars, parent learning style notes, and last session sticking points.</p>
            </div>
            <a href="{{ route('tutor.classroom.show', $course) }}" class="btn-outline-primary py-2 px-4 text-xs font-bold flex items-center gap-1">
                <span class="material-symbols-outlined text-sm">arrow_back</span> Classroom Hub
            </a>
        </div>
    </div>

    

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($coPilotData as $child)
            <div class="clay-card bg-white p-6 rounded-3xl border border-outline-variant/20 space-y-4 shadow-sm">
                <div class="flex items-center gap-4 border-b border-outline-variant/20 pb-4">
                    <div class="w-12 h-12 rounded-full bg-primary text-white font-bold flex items-center justify-center text-base shrink-0">
                        {{ substr($child['name'], 0, 1) }}
                    </div>
                    <div>
                        <h3 class="font-bold text-base text-on-surface">{{ $child['name'] }}</h3>
                        <span class="px-2 py-0.5 rounded bg-surface-container text-on-surface-variant text-[10px] font-bold">Enrolled Learner</span>
                    </div>
                </div>

                <div class="space-y-3 text-xs">
                    <div class="p-3 bg-warning-container rounded-xl text-warning border border-warning-container">
                        <strong class="block mb-1 font-bold text-warning flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs text-warning">psychology</span> Parent Notes & Learning Style:
                        </strong>
                        {{ $child['parent_notes'] }}
                    </div>

                    <div class="p-3 bg-surface-container-low rounded-xl text-on-surface">
                        <strong class="block mb-1 font-bold flex items-center gap-1 text-primary">
                            <span class="material-symbols-outlined text-xs">warning</span> Last Session Sticking Point:
                        </strong>
                        {{ $child['last_sticking_point'] }}
                    </div>
                </div>

                <form method="POST" action="{{ route('tutor.copilot.update', $course) }}" class="space-y-2 pt-2 border-t border-outline-variant/20">
                    @csrf
                    <input type="hidden" name="student_id" value="{{ $child['student_id'] }}">
                    <input type="text" name="last_sticking_point" placeholder="Update sticking point..." class="w-full px-3 py-1.5 bg-surface-container-low border border-outline-variant/30 rounded-lg text-xs outline-none">
                    <button type="submit" class="btn-primary w-full py-1.5 text-xs font-bold">Save Prep Note</button>
                </form>
            </div>
        @empty
            <div class="col-span-3 py-12 text-center text-xs text-on-surface-variant">
                No enrolled students found in section for Co-Pilot prep.
            </div>
        @endforelse
    </div>
</div>
@endsection
