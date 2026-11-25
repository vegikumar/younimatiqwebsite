@extends('layouts.dashboard')

@section('title', 'Create Course')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">Create New Course</h1>
        <a href="{{ route('tutor.manage-courses') }}" class="btn-secondary btn-sm">Back</a>
    </div>

    @if($errors->any())
        <div class="p-4 bg-error-container text-on-error-container rounded-xl">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('tutor.courses.store') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl p-6 shadow-sm border border-outline-variant/30 max-w-3xl space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-bold mb-1">Title</label>
            <input type="text" name="title" required class="w-full border rounded-lg px-4 py-2" value="{{ old('title') }}">
        </div>
        <div>
            <label class="block text-sm font-bold mb-1">Short Description</label>
            <input type="text" name="short_description" class="w-full border rounded-lg px-4 py-2" value="{{ old('short_description') }}">
        </div>
        <div>
            <label class="block text-sm font-bold mb-1">Description</label>
            <textarea name="description" required rows="4" class="w-full border rounded-lg px-4 py-2">{{ old('description') }}</textarea>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-bold mb-1">Course Type</label>
                <select name="type" required class="w-full border rounded-lg px-4 py-2">
                    <option value="1-on-1">1-on-1</option>
                    <option value="camp">Group Camp</option>
                    <option value="recording">Pre-recorded</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold mb-1">Price ($)</label>
                <input type="number" step="0.01" name="price" class="w-full border rounded-lg px-4 py-2" value="{{ old('price') }}">
            </div>
        </div>
        <div>
            <label class="block text-sm font-bold mb-1">Categories</label>
            <select name="category_ids[]" multiple class="w-full border rounded-lg px-4 py-2 h-32">
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-bold mb-1">Thumbnail Image</label>
            <input type="file" name="thumbnail" accept="image/*" class="w-full border rounded-lg px-4 py-2">
        </div>

        <div class="pt-4 text-right">
            <button type="submit" class="btn-primary">Create Course</button>
        </div>
    </form>
</div>
@endsection
