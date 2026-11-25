@extends('layouts.dashboard')

@section('title', 'Q & A')

@section('content')
<div class="p-5 md:p-8 space-y-8">
    <div>
        <h1 class="font-headline-lg text-headline-lg font-bold mb-2">Q & A 💬</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant">Questions you've asked and answers from tutors.</p>
    </div>

    

    <div class="bg-white p-6 rounded-2xl border border-surface-container-high shadow-sm">
        <h2 class="font-headline-md font-bold mb-4">Ask a Question</h2>
        <form method="POST" action="{{ route('student.qa.store') }}" class="space-y-4 max-w-2xl">
            @csrf
            <div>
                <label class="block text-sm font-bold mb-2">Course</label>
                <select name="course_id" required class="w-full bg-surface-container-low border border-surface-container-high rounded-xl px-4 py-3 focus:outline-none focus:border-primary">
                    <option value="">Select a course</option>
                    @foreach(\App\Models\Enrollment::with('course')->where('user_id', auth()->id())->get() as $enrollment)
                        @if($enrollment->course)
                            <option value="{{ $enrollment->course_id }}">{{ $enrollment->course->title }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold mb-2">Question Title</label>
                <input type="text" name="title" required maxlength="255" class="w-full bg-surface-container-low border border-surface-container-high rounded-xl px-4 py-3 focus:outline-none focus:border-primary" placeholder="Brief summary of your question">
            </div>
            <div>
                <label class="block text-sm font-bold mb-2">Details</label>
                <textarea name="body" rows="4" required maxlength="2000" class="w-full bg-surface-container-low border border-surface-container-high rounded-xl px-4 py-3 focus:outline-none focus:border-primary" placeholder="Describe your question in detail..."></textarea>
            </div>
            <button type="submit" class="btn-primary flex items-center gap-2">
                <span class="material-symbols-outlined">send</span> Post Question
            </button>
        </form>
    </div>

    <div class="space-y-6">
        @forelse($questions as $question)
        <div class="bg-white p-6 rounded-2xl border border-surface-container-high shadow-sm">
            <div class="flex gap-4">
                <div class="w-12 h-12 bg-primary-fixed rounded-full flex items-center justify-center flex-shrink-0 text-primary font-bold">Q</div>
                <div class="w-full">
                    <h3 class="font-bold text-lg mb-2">{{ $question->title }}</h3>
                    <p class="text-sm text-on-surface-variant mb-2">Course: {{ $question->course->title ?? 'N/A' }} · Asked on <x-local-time :date="$question->created_at" format="date" /></p>
                    <p class="text-on-surface mb-4">{{ $question->body }}</p>

                    @foreach($question->answers as $answer)
                    <div class="bg-surface-container-low p-4 rounded-xl border-l-4 border-secondary mt-4">
                        <div class="flex gap-3 mb-2 items-center">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($answer->user->name ?? 'Tutor') }}&background=4CAF50&color=fff" class="w-6 h-6 rounded-full" alt="">
                            <span class="font-bold text-sm">{{ $answer->user->name ?? 'Tutor' }}</span>
                            <span class="text-xs text-on-surface-variant"><x-local-time :date="$answer->created_at" format="date" /></span>
                        </div>
                        <p class="text-on-surface">{{ $answer->body }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @empty
        <div class="bg-surface-container-low border-2 border-dashed border-surface-container-high rounded-2xl flex flex-col items-center justify-center p-5 md:p-8 text-center text-on-surface-variant">
            <span class="material-symbols-outlined text-4xl mb-2">forum</span>
            <p class="font-bold mb-2">No questions asked yet.</p>
        </div>
        @endforelse
    </div>
</div>
@endsection
