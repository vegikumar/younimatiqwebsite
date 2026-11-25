@extends('layouts.dashboard')

@section('title', 'Edit Email Template')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-3xl mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-10">
        <div class="flex items-center gap-4 mb-8 border-b border-outline-variant pb-6">
            <div class="w-12 h-12 bg-primary/10 text-primary rounded-xl flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">mail</span>
            </div>
            <div>
                <h2 class="font-headline-md text-headline-md text-on-surface">Edit: {{ $template->name }}</h2>
                <p class="font-body-md text-on-surface-variant">Slug: <code class="text-sm bg-surface-container-low px-2 py-1 rounded">{{ $template->slug }}</code></p>
            </div>
        </div>

        

        @if($errors->any())
            <div class="mb-6 p-4 bg-error-container text-on-error-container rounded-xl text-sm">
                <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.email.update', $template) }}" class="space-y-6">
            @csrf @method('PUT')

            <div>
                <label class="block font-bold mb-2">Template Name</label>
                <input type="text" name="name" value="{{ old('name', $template->name) }}" required class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
            </div>

            <div>
                <label class="block font-bold mb-2">Email Subject</label>
                <input type="text" name="subject" value="{{ old('subject', $template->subject) }}" required class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
            </div>

            <div>
                <label class="block font-bold mb-2">Email Body</label>
                <textarea name="body" rows="14" required class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none font-mono text-sm">{{ old('body', $template->body) }}</textarea>
                <p class="text-xs text-on-surface-variant mt-2">Use placeholders like @{{student_name}}, @{{course_title}}, @{{amount}}, @{{reset_url}}</p>
            </div>

            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $template->is_active) ? 'checked' : '' }} class="w-5 h-5 rounded text-primary focus:ring-primary">
                <span class="font-bold">Template is active</span>
            </label>

            <div class="flex gap-4 pt-4 border-t border-outline-variant">
                <button type="submit" class="btn-primary">Save Template</button>
                <a href="{{ route('admin.email.index') }}" class="px-8 py-3 bg-surface-container text-on-surface font-bold rounded-full">Back</a>
            </div>
        </form>

        <div class="mt-8 pt-8 border-t border-outline-variant">
            <h3 class="font-bold mb-4">Send Test Email</h3>
            <form method="POST" action="{{ route('admin.email.test', $template) }}" class="flex gap-4">
                @csrf
                <input type="email" name="email" required placeholder="test@example.com" class="flex-1 p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                <button type="submit" class="btn-enroll btn-sm">Send Test</button>
            </form>
        </div>
    </div>
</div>
@endsection
