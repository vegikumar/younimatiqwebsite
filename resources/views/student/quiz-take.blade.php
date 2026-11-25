@extends('layouts.dashboard')

@section('title', $quiz->title)

@section('content')
<div class="p-5 md:p-8 max-w-3xl mx-auto">
    <div class="mb-8">
        <a href="{{ route('student.quizzes') }}" class="text-primary font-bold text-sm hover:underline flex items-center gap-1 mb-4">
            <span class="material-symbols-outlined text-sm">arrow_back</span> Back to Quizzes
        </a>
        <h1 class="font-headline-lg font-bold mb-2">{{ $quiz->title }}</h1>
        <p class="text-on-surface-variant">Course: {{ $quiz->course->title ?? 'N/A' }} · Pass score: {{ $quiz->passing_score ?? 70 }}%</p>
    </div>

    <form method="POST" action="{{ route('student.quizzes.submit', $quiz) }}" class="space-y-8">
        @csrf

        @foreach($quiz->questions ?? [] as $index => $question)
            <div class="bg-white p-6 rounded-2xl border border-surface-container-high shadow-sm">
                <h3 class="font-bold mb-4">{{ $index + 1 }}. {{ $question['question'] ?? $question['text'] ?? 'Question ' . ($index + 1) }}</h3>

                @if(isset($question['options']) && is_array($question['options']))
                    <div class="space-y-3">
                        @foreach($question['options'] as $optIndex => $option)
                            <label class="flex items-center gap-3 p-3 bg-surface-container-low rounded-xl cursor-pointer hover:bg-surface-container transition-colors">
                                <input type="radio" name="answers[{{ $index }}]" value="{{ is_array($option) ? ($option['value'] ?? $optIndex) : $option }}" required class="w-4 h-4 text-primary focus:ring-primary">
                                <span>{{ is_array($option) ? ($option['label'] ?? $option['value']) : $option }}</span>
                            </label>
                        @endforeach
                    </div>
                @else
                    <input type="text" name="answers[{{ $index }}]" required class="w-full px-4 py-3 bg-surface-container-low border border-surface-container-high rounded-xl focus:outline-none focus:border-primary" placeholder="Your answer">
                @endif
            </div>
        @endforeach

        @if(empty($quiz->questions))
            <div class="bg-surface-container-low p-5 md:p-8 rounded-2xl text-center text-on-surface-variant">
                <p>This quiz has no questions configured yet.</p>
            </div>
        @else
            <button type="submit" class="btn-primary btn-block btn-lg">Submit Quiz</button>
        @endif
    </form>
</div>
@endsection
