@extends('layouts.dashboard')

@section('title', 'Post class Activity')

@section('content')
<div class="p-5 md:p-8 space-y-8">
    <div>
        <h1 class="font-headline-lg text-headline-lg font-bold mb-2">Post class Activity 📝</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant">Keep track of your pending and completed tasks.</p>
    </div>

    @if(session('success'))
        <div class="p-4 bg-tertiary-fixed text-tertiary rounded-xl border border-tertiary-fixed-dim font-medium">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="p-4 bg-error-container text-on-error-container rounded-xl border border-error/20 font-medium">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    @endif

    @php
        $pending = $assignments->filter(fn($a) => $a->submissions->isEmpty());
        $completed = $assignments->filter(fn($a) => $a->submissions->isNotEmpty());
    @endphp

    <div class="grid grid-cols-1 gap-6">
        @forelse($assignments as $assignment)
            @php $submitted = $assignment->submissions->first(); @endphp
            <div class="bg-white p-6 rounded-2xl border border-surface-container-high shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-center gap-6">
                        @if($assignment->due_at)
                            <div class="w-16 h-16 {{ $submitted ? 'bg-secondary-container text-on-secondary-container' : 'bg-error-container text-on-error-container' }} rounded-2xl flex flex-col items-center justify-center shrink-0">
                                <span class="font-bold text-lg leading-none">{{ $assignment->due_at->format('d') }}</span>
                                <span class="text-xs uppercase">{{ $assignment->due_at->format('M') }}</span>
                            </div>
                        @endif
                        <div>
                            <h3 class="font-headline-md font-bold mb-1">{{ $assignment->title }}</h3>
                            <p class="text-sm text-on-surface-variant">Course: {{ $assignment->course->title ?? 'N/A' }}</p>
                            @if($assignment->description)
                                <p class="text-sm text-on-surface-variant mt-2">{{ $assignment->description }}</p>
                            @endif
                            @if($assignment->file_path)
                                <a href="{{ route('secure.asset', ['path' => $assignment->file_path]) }}" target="_blank" rel="noopener" class="text-primary text-sm hover:underline flex items-center gap-1 mt-2 font-bold"><span class="material-symbols-outlined text-[16px]">visibility</span> View Attached File</a>
                            @endif
                            @if($assignment->hasQuestions())
                                <p class="text-sm text-primary font-bold mt-2">{{ count($assignment->questions) }} questions</p>
                            @endif
                            @if($submitted)
                                <span class="inline-block mt-2 px-3 py-1 bg-secondary-container text-on-secondary-container rounded-full text-xs font-bold">Submitted {{ $submitted->created_at->format('M d') }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                @if($assignment->hasQuestions())
                    @if(!$submitted || $submitted->status !== 'graded')
                        <div class="mt-6 pt-6 border-t border-surface-container-high">
                            <a href="{{ route('student.assignments.take', $assignment) }}" class="btn-primary btn-sm inline-flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">{{ $submitted ? 'edit' : 'play_arrow' }}</span>
                                {{ $submitted ? 'Update Answers' : 'Start Assignment' }}
                            </a>
                        </div>
                    @endif
                    @if($submitted)
                        <div class="mt-4 p-4 bg-surface-container-low rounded-xl text-sm">
                            <p class="font-bold mb-1">Your submission:</p>
                            <x-assignment-submission-answers :assignment="$assignment" :submission="$submitted" />
                            @if($submitted->grade !== null)
                                <p class="font-bold mt-3 text-primary">Grade: {{ $submitted->grade }}%</p>
                            @endif
                            @if($submitted->feedback)
                                <p class="text-on-surface-variant mt-1"><strong>Feedback:</strong> {{ $submitted->feedback }}</p>
                            @endif
                        </div>
                    @endif
                @elseif(!$submitted)
                    <form method="POST" action="{{ route('student.assignments.submit', $assignment) }}" enctype="multipart/form-data" class="mt-6 pt-6 border-t border-surface-container-high">
                        @csrf
                        <label class="block text-sm font-bold mb-2">Your Submission (Text)</label>
                        <textarea name="content" rows="4" class="w-full bg-surface-container-low border border-surface-container-high rounded-xl px-4 py-3 focus:outline-none focus:border-primary mb-4" placeholder="Write your answer here..."></textarea>
                        
                        <label class="block text-sm font-bold mb-2">Or Upload a File</label>
                        <input type="file" name="submission_file" class="w-full bg-surface-container-low border border-surface-container-high rounded-xl px-4 py-2 focus:outline-none focus:border-primary mb-4" accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png">
                        
                        <button type="submit" class="btn-primary btn-sm">Submit Assignment</button>
                    </form>
                @else
                    <div class="mt-4 p-4 bg-surface-container-low rounded-xl text-sm">
                        <p class="font-bold mb-1">Your submission:</p>
                        @if($submitted->content)
                            <p class="text-on-surface-variant mb-2">{{ $submitted->content }}</p>
                        @endif
                        @if($submitted->file_path)
                            <a href="{{ route('secure.asset', ['path' => $submitted->file_path]) }}" target="_blank" class="text-primary text-sm hover:underline flex items-center gap-1 font-bold"><span class="material-symbols-outlined text-[16px]">file_open</span> View My Uploaded File</a>
                        @endif
                        @if($submitted->grade !== null)
                            <p class="font-bold mt-3 text-primary">Grade: {{ $submitted->grade }}%</p>
                        @endif
                        @if($submitted->feedback)
                            <p class="text-on-surface-variant mt-1"><strong>Feedback:</strong> {{ $submitted->feedback }}</p>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <div class="bg-surface-container-low border-2 border-dashed border-surface-container-high rounded-2xl p-6 md:p-12 text-center text-on-surface-variant">
                <span class="material-symbols-outlined text-5xl mb-4">assignment</span>
                <p class="font-bold">No assignments yet.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
