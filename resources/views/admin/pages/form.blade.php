@extends('layouts.dashboard')

@section('title', $page->exists ? 'Edit Page' : 'Create Page')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-3xl mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-10">
        <div class="flex items-center gap-4 mb-8 border-b border-outline-variant pb-6">
            <div class="w-12 h-12 bg-secondary/10 text-secondary rounded-xl flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">web</span>
            </div>
            <div>
                <h2 class="font-headline-md text-headline-md text-on-surface">{{ $page->exists ? 'Edit Page' : 'Create Page' }}</h2>
                <p class="font-body-md text-on-surface-variant">Build static content pages for the public site.</p>
            </div>
        </div>

        @if($errors->any())
            <div class="mb-6 p-4 bg-error-container text-on-error-container rounded-xl text-sm">
                <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}" class="space-y-6">
            @csrf
            @if($page->exists) @method('PUT') @endif

            <div>
                <label class="block font-bold mb-2">Title</label>
                <input type="text" name="title" value="{{ old('title', $page->title) }}" required class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
            </div>

            <div>
                <label class="block font-bold mb-2">Slug</label>
                <input type="text" name="slug" value="{{ old('slug', $page->slug) }}" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" placeholder="auto-generated-from-title">
                <p class="text-xs text-on-surface-variant mt-1">URL: /page/<span class="font-mono">{{ old('slug', $page->slug ?: 'your-slug') }}</span></p>
            </div>

            <div>
                <label class="block font-bold mb-2">Content</label>
                <textarea name="content" rows="14" required class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none font-mono text-sm">{{ old('content', $page->content) }}</textarea>
            </div>

            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="is_published" value="1" {{ old('is_published', $page->is_published) ? 'checked' : '' }} class="w-5 h-5 rounded text-primary focus:ring-primary">
                <span class="font-bold">Publish this page</span>
            </label>

            <div class="flex gap-4 pt-4 border-t border-outline-variant">
                <button type="submit" class="btn-primary">{{ $page->exists ? 'Update Page' : 'Create Page' }}</button>
                <a href="{{ route('admin.pages.index') }}" class="px-8 py-3 bg-surface-container text-on-surface font-bold rounded-full">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
