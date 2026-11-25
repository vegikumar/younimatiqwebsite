@extends('layouts.dashboard')

@section('title', $blog->exists ? 'Edit Blog Post' : 'Create Blog Post')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-3xl mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-10">
        <div class="flex items-center gap-4 mb-8 border-b border-outline-variant pb-6">
            <div class="w-12 h-12 bg-primary/10 text-primary rounded-xl flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">article</span>
            </div>
            <div>
                <h2 class="font-headline-md text-headline-md text-on-surface">{{ $blog->exists ? 'Edit Blog Post' : 'Create Blog Post' }}</h2>
                <p class="font-body-md text-on-surface-variant">Write and publish content for the public blog.</p>
            </div>
        </div>

        @if($errors->any())
            <div class="mb-6 p-4 bg-error-container text-on-error-container rounded-xl text-sm">
                <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ $blog->exists ? route('admin.blogs.update', $blog) : route('admin.blogs.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @if($blog->exists) @method('PUT') @endif

            <div>
                <label class="block font-bold mb-2">Title</label>
                <input type="text" name="title" value="{{ old('title', $blog->title) }}" required class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
            </div>

            <div>
                <label class="block font-bold mb-2">Slug (optional)</label>
                <input type="text" name="slug" value="{{ old('slug', $blog->slug) }}" placeholder="auto-generated from title if blank" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
            </div>

            <div>
                <label class="block font-bold mb-2">Excerpt</label>
                <textarea name="excerpt" rows="2" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">{{ old('excerpt', $blog->excerpt) }}</textarea>
            </div>

            <div>
                <label class="block font-bold mb-2">Content</label>
                <textarea id="blog-content" name="content" rows="16" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none rich-text">{{ old('content', $blog->content) }}</textarea>
                <p class="text-sm text-on-surface-variant mt-2">Use the editor toolbar to format your post.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block font-bold mb-2">Featured Image Upload</label>
                    @if($blog->featured_image)
                        <div class="mb-2">
                            <img src="{{ $blog->featured_image }}" alt="Current Image" class="w-24 h-24 object-cover rounded-xl border border-outline-variant">
                        </div>
                    @endif
                    <input type="file" name="featured_image" accept="image/*" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                </div>
                <div>
                    <label class="block font-bold mb-2">Author</label>
                    <input type="text" name="author" value="{{ old('author', $blog->author ?? 'Younimatiq') }}" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block font-bold mb-2">Meta Title</label>
                    <input type="text" name="meta_title" value="{{ old('meta_title', $blog->seo_metadata['meta_title'] ?? '') }}" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                </div>
                <div>
                    <label class="block font-bold mb-2">Meta Description</label>
                    <textarea name="meta_description" rows="2" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">{{ old('meta_description', $blog->seo_metadata['meta_description'] ?? '') }}</textarea>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block font-bold mb-2">Status</label>
                    <select name="status" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                        <option value="draft" {{ old('status', $blog->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="published" {{ old('status', $blog->status) === 'published' ? 'selected' : '' }}>Published</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold mb-2">Publish Date</label>
                    <input type="date" name="published_at" value="{{ old('published_at', $blog->published_at?->format('Y-m-d')) }}" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                </div>
            </div>

            <div class="flex gap-4 pt-4 border-t border-outline-variant">
                <button type="submit" class="btn-primary">{{ $blog->exists ? 'Update Post' : 'Create Post' }}</button>
                <a href="{{ route('admin.blogs.index') }}" class="px-8 py-3 bg-surface-container text-on-surface font-bold rounded-full">Cancel</a>
            </div>
        </form>
    </div>
</div>

<style>
    .ck-editor__editable_inline {
        min-height: 320px;
        background-color: var(--color-surface-container-low) !important;
        border-color: var(--color-outline-variant) !important;
        border-radius: 0 0 12px 12px !important;
        color: var(--color-on-surface) !important;
    }
    .ck.ck-toolbar {
        background: var(--color-surface) !important;
        border-color: var(--color-outline-variant) !important;
        border-radius: 12px 12px 0 0 !important;
    }
</style>
<script src="https://cdn.ckeditor.com/ckeditor5/40.0.0/classic/ckeditor.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const el = document.getElementById('blog-content');
        if (!el) return;
        ClassicEditor.create(el).catch(error => { console.error(error); });
    });
</script>
@endsection
