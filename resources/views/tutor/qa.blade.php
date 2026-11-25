@extends('layouts.dashboard')
@section('title', 'Student Q&A')
@section('content')
<div class="p-5 md:p-8 space-y-6">
    <h1 class="font-headline-lg text-2xl font-bold">Student Questions</h1>
    @foreach($questions as $q)
    <div class="bg-white rounded-2xl border p-6">
        <p class="text-xs text-on-surface-variant">{{ $q->course->title ?? '' }} · {{ $q->user->name ?? 'Student' }}</p>
        <h3 class="font-bold mt-1">{{ $q->title }}</h3>
        <p class="text-sm mt-2">{{ $q->body }}</p>
        @foreach($q->answers as $a)
        <div class="mt-3 pl-4 border-l-2 border-primary text-sm"><strong>Your answer:</strong> {{ $a->body }}</div>
        @endforeach
        <form method="POST" action="{{ route('tutor.qa.answer', $q) }}" class="mt-4 flex gap-2">
            @csrf
            <input type="text" name="body" required placeholder="Write your answer..." class="flex-1 px-4 py-2 border rounded-xl">
            <button type="submit" class="btn-primary btn-sm">Reply</button>
        </form>
    </div>
    @endforeach
    @if($questions->isEmpty())<p class="text-on-surface-variant">No questions yet.</p>@endif
</div>
@endsection
