@extends('layouts.dashboard')
@section('title', $quiz->title.' — Results')
@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-3xl mx-auto py-12 space-y-8">
    <div class="text-center space-y-3">
        <a href="{{ route('student.quizzes') }}" class="text-primary font-bold text-sm hover:underline inline-flex items-center gap-1">
            <span class="material-symbols-outlined text-sm">arrow_back</span> Back to Quizzes
        </a>
        <h1 class="font-headline-lg text-3xl font-bold">{{ $quiz->title }}</h1>
        <p class="text-on-surface-variant">Answer review</p>

        @if(session('success'))
            <div class="bg-tertiary-fixed text-tertiary border border-tertiary-fixed-dim p-4 rounded-xl font-bold shadow-sm">{{ session('success') }}</div>
        @endif

        <div class="inline-flex flex-col items-center gap-1 px-8 py-5 rounded-2xl {{ $attempt->passed ? 'bg-tertiary-fixed border border-tertiary-fixed-dim' : 'bg-warning-container border border-warning-container' }}">
            <span class="text-4xl font-extrabold {{ $attempt->passed ? 'text-on-tertiary-container' : 'text-warning' }}">{{ $attempt->score }}%</span>
            <span class="text-sm font-bold {{ $attempt->passed ? 'text-on-tertiary-container' : 'text-warning' }}">
                {{ $attempt->passed ? 'Passed' : 'Needs Retry' }} · Pass mark {{ $quiz->passing_score ?? 70 }}%
            </span>
        </div>
    </div>

    <div class="space-y-6">
        @foreach($review as $item)
            <div class="clay-card p-5 md:p-6 bg-white space-y-4 border-l-4 {{ $item['is_correct'] ? 'border-tertiary' : 'border-error' }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Question {{ $item['number'] }}</span>
                        <h3 class="text-lg font-bold leading-relaxed mt-1">{{ $item['question'] }}</h3>
                    </div>
                    <span class="shrink-0 px-3 py-1 rounded-full text-xs font-bold {{ $item['is_correct'] ? 'bg-tertiary-fixed text-tertiary' : 'bg-error-container text-error' }}">
                        {{ $item['is_correct'] ? 'Correct' : 'Incorrect' }}
                    </span>
                </div>

                @if(!empty($item['image_url']))
                    <img src="{{ $item['image_url'] }}" alt="Question image" class="max-h-48 rounded-xl border object-contain">
                @endif

                <div class="grid sm:grid-cols-2 gap-3 text-sm">
                    <div class="rounded-xl bg-surface-container-low p-3">
                        <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1">Your answer</p>
                        <p class="font-medium {{ $item['is_correct'] ? 'text-tertiary' : 'text-error' }}">{{ $item['student_answer'] }}</p>
                    </div>
                    <div class="rounded-xl bg-tertiary-fixed p-3 border border-tertiary-fixed-dim">
                        <p class="text-xs font-bold uppercase tracking-wider text-on-tertiary-container mb-1">Correct answer</p>
                        <p class="font-medium text-tertiary">{{ $item['correct_answer'] }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="flex flex-wrap items-center justify-center gap-3 pt-4">
        @unless($attempt->passed)
            <a href="{{ route('student.quizzes.take', $quiz) }}" class="btn-primary py-3 px-8 rounded-full font-bold shadow-md">
                Retry Quiz
            </a>
        @endunless
        <a href="{{ route('student.quizzes') }}" class="btn-secondary py-3 px-8 rounded-full font-bold">
            All Quizzes
        </a>
    </div>
</div>
@endsection
