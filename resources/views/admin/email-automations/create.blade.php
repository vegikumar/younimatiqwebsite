@extends('layouts.dashboard')

@section('title', 'New Email Automation')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="mb-6">
        <a href="{{ route('admin.email-automations.index') }}" class="text-sm font-bold text-primary hover:underline">← All automations</a>
    </div>

    <div class="clay-card p-5 md:p-8 max-w-xl border-t-8 border-primary">
        <h1 class="font-headline-xl text-headline-xl text-on-surface mb-2">New email automation</h1>
        <p class="text-sm text-on-surface-variant mb-6">Give it a name. You’ll set schedule, audiences, and copy on the next screen.</p>

        @if($errors->any())
            <div class="mb-4 p-4 rounded-xl bg-error-container text-on-error-container">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.email-automations.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-bold mb-2">Campaign name</label>
                <input type="text" name="name" value="{{ old('name') }}" required maxlength="120"
                       class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary"
                       placeholder="e.g. Weekend camp promo">
            </div>
            <div>
                <label class="block text-sm font-bold mb-2">Email subject <span class="font-normal text-on-surface-variant">(optional for now)</span></label>
                <input type="text" name="subject" value="{{ old('subject', $defaults['subject'] ?? '') }}" maxlength="180"
                       class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary"
                       placeholder="Trending classes this week">
            </div>
            <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" class="btn-primary">Create &amp; configure</button>
                <a href="{{ route('admin.email-automations.index') }}" class="px-4 py-2 rounded-xl border border-outline-variant font-bold text-sm">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
