@extends('layouts.dashboard')
@section('title', 'My Quizzes')
@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12 space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-headline-lg text-2xl font-bold">My Quizzes</h1>
            <p class="text-on-surface-variant">Complete quizzes to earn XP and climb the leaderboard.</p>
        </div>
        <div class="text-right">
            <span class="text-sm font-bold text-on-surface-variant block">Your Total XP</span>
            <span class="text-2xl font-bold text-tertiary flex items-center justify-end gap-1">
                <span class="material-symbols-outlined text-tertiary">star</span> {{ auth()->user()->xp ?? 0 }}
            </span>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-success-container text-on-success-container p-4 rounded-xl font-bold shadow-sm">{{ session('success') }}</div>
    @endif

    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($quizzes as $quiz)
            @php
                $passedAttempts = $quiz->attempts->where('passed', true);
                $isCompleted = $passedAttempts->isNotEmpty();
                $bestScore = $quiz->attempts->max('score');
                $latestAttempt = $quiz->attempts->first();
            @endphp
            <div class="clay-card p-6 flex flex-col justify-between border-t-8 {{ $isCompleted ? 'border-success' : 'border-primary' }}">
                <div class="mb-6">
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="text-xl font-bold leading-tight">{{ $quiz->title }}</h3>
                        @if($quiz->xp_reward > 0 && !$isCompleted)
                            <span class="px-2 py-1 bg-tertiary/10 text-tertiary rounded-lg text-xs font-bold flex items-center gap-1 shrink-0">
                                <span class="material-symbols-outlined text-[14px]">star</span> +{{ $quiz->xp_reward }}
                            </span>
                        @endif
                    </div>
                    @if($quiz->description)
                        <p class="text-sm text-on-surface-variant line-clamp-2">{{ $quiz->description }}</p>
                    @endif
                    <div class="mt-4 flex gap-2 flex-wrap">
                        <span class="px-2 py-1 bg-surface-container-high text-xs rounded font-bold">{{ count($quiz->questions ?? []) }} Questions</span>
                        <span class="px-2 py-1 bg-surface-container-high text-xs rounded font-bold">{{ $quiz->passing_score }}% to unlock badge</span>
                    </div>
                </div>

                <div class="pt-4 border-t border-outline-variant/30 space-y-3">
                    @if($quiz->attempts->isNotEmpty())
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex flex-col gap-1">
                                <div class="flex items-center gap-2 font-bold {{ $isCompleted ? 'text-success' : 'text-warning' }}">
                                    <span class="material-symbols-outlined">{{ $isCompleted ? 'check_circle' : 'info' }}</span>
                                    {{ $isCompleted ? 'Passed' : 'Needs Retry' }} ({{ $bestScore }}%)
                                </div>
                                @if($isCompleted && $quiz->xp_reward > 0)
                                    <div class="text-xs font-bold text-tertiary flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[14px]">star</span> Earned {{ $quiz->xp_reward }} XP
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('student.quizzes.results', $quiz) }}" class="btn-secondary btn-sm !px-4 text-xs font-bold inline-flex items-center gap-1">
                                <span class="material-symbols-outlined text-[16px]">visibility</span>
                                View Answers
                            </a>
                            @unless($isCompleted)
                                <a href="{{ route('student.quizzes.take', $quiz) }}" class="btn-primary btn-sm !px-4 text-xs font-bold inline-flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[16px]">refresh</span>
                                    Retry Quiz
                                </a>
                            @endunless
                        </div>
                    @else
                        <div class="flex items-center justify-between gap-2">
                            <div class="text-sm text-on-surface-variant font-bold">Not Attempted</div>
                            <a href="{{ route('student.quizzes.take', $quiz) }}" class="btn-primary py-2 px-6 rounded-full font-bold shadow-md">Take Quiz</a>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full text-center p-6 md:p-12 bg-surface-container-low rounded-3xl border border-dashed border-outline-variant">
                <span class="material-symbols-outlined text-5xl text-on-surface-variant mb-4 opacity-50">task</span>
                <h3 class="text-xl font-bold mb-2">You're all caught up!</h3>
                <p class="text-on-surface-variant">No quizzes have been assigned to you yet.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
