@extends('layouts.dashboard')
@section('title', 'Quizzes')
@section('content')
<div class="p-5 md:p-8 max-w-5xl mx-auto space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-headline-lg text-2xl font-bold">Manage Quizzes</h1>
            <p class="text-on-surface-variant">Create and assign quizzes to your students.</p>
        </div>
        <a href="{{ route('tutor.quizzes.create') }}" class="btn-primary flex items-center gap-2">
            <span class="material-symbols-outlined">add</span> Create Quiz
        </a>
    </div>

    @if(session('success'))
        <div class="bg-success-container text-on-success-container p-4 rounded-xl font-bold">{{ session('success') }}</div>
    @endif

    <div class="grid gap-4">
        @forelse($quizzes as $q)
        <div class="bg-white rounded-2xl border p-6 flex flex-col md:flex-row justify-between md:items-center gap-4">
            <div>
                <h3 class="text-lg font-bold text-primary">{{ $q->title }}</h3>
                <p class="text-sm text-on-surface-variant">{{ count($q->questions ?? []) }} questions &bull; {{ $q->xp_reward }} XP Reward</p>
                
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach($q->assignments as $a)
                        @if($a->assignable_type === \App\Models\Course::class)
                            <span class="px-2 py-1 bg-secondary/10 text-secondary rounded-lg text-xs font-bold">Course: {{ $a->assignable->title ?? 'Unknown' }}</span>
                        @elseif($a->assignable_type === \App\Models\User::class)
                            <span class="px-2 py-1 bg-tertiary/10 text-tertiary rounded-lg text-xs font-bold">Student: {{ $a->assignable->name ?? 'Unknown' }}</span>
                        @endif
                    @endforeach
                    @if($q->assignments->isEmpty())
                        <span class="px-2 py-1 bg-surface-container-high text-on-surface-variant rounded-lg text-xs font-bold">Unassigned</span>
                    @endif
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <a href="{{ route('tutor.quizzes.edit', $q->id) }}" class="px-4 py-2 bg-surface-container-low text-on-surface hover:bg-surface-container rounded-xl font-bold text-sm">Edit</a>
            </div>
        </div>
        @empty
        <div class="text-center p-6 md:p-12 bg-surface-container-low rounded-3xl border border-dashed border-outline-variant">
            <span class="material-symbols-outlined text-4xl text-on-surface-variant mb-2">quiz</span>
            <h3 class="text-lg font-bold">No quizzes yet</h3>
            <p class="text-on-surface-variant mb-4">You haven't created any quizzes.</p>
            <a href="{{ route('tutor.quizzes.create') }}" class="btn-primary inline-flex">Create First Quiz</a>
        </div>
        @endforelse
    </div>
</div>
@endsection
