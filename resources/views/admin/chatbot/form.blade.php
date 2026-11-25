@extends('layouts.dashboard')

@section('title', isset($training) ? 'Edit QA Pair' : 'Add QA Pair')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-3xl mx-auto py-12">
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.chatbot.index') }}" class="p-2 bg-surface-container-high hover:bg-outline-variant text-on-surface rounded-full transition-colors flex items-center justify-center">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <h1 class="font-headline-xl text-2xl text-on-surface">{{ isset($training) ? 'Edit QA Pair' : 'Add QA Pair' }}</h1>
    </div>

    @if ($errors->any())
        <div class="mb-6 p-4 bg-error-container text-error rounded-xl font-medium">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="clay-card p-5 md:p-8">
        <form method="POST" action="{{ isset($training) ? route('admin.chatbot.update', $training->id) : route('admin.chatbot.store') }}" class="space-y-6">
            @csrf
            @if(isset($training))
                @method('PUT')
            @endif

            <div class="space-y-2">
                <label class="block font-bold text-on-surface">Question</label>
                <input type="text" name="question" value="{{ old('question', $training->question ?? '') }}" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all" placeholder="e.g. How do I reset my password?">
            </div>

            <div class="space-y-2">
                <label class="block font-bold text-on-surface">Tags / Keywords</label>
                <input type="text" name="tags" value="{{ old('tags', $training->tags ?? '') }}" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all" placeholder="e.g. password, reset, login">
                <p class="text-xs text-on-surface-variant">Separate multiple tags with commas.</p>
            </div>

            <div class="space-y-2">
                <label class="block font-bold text-on-surface">Bot Answer</label>
                <textarea name="answer" rows="5" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all" placeholder="The exact response the bot will give.">{{ old('answer', $training->answer ?? '') }}</textarea>
            </div>

            <div class="pt-4 border-t border-outline-variant text-right">
                <button type="submit" class="btn-primary">
                    {{ isset($training) ? 'Save Changes' : 'Add QA Pair' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
