@extends('layouts.dashboard')

@section('title', 'Edit Course')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">Edit Course: {{ $course->title }}</h1>
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

    <form action="{{ route('tutor.courses.update', $course) }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl p-6 shadow-sm border border-outline-variant/30 max-w-3xl space-y-4">
        @csrf
        @method('PUT')
        <div>
            <label class="block text-sm font-bold mb-1">Title</label>
            <input type="text" name="title" required class="w-full border rounded-lg px-4 py-2" value="{{ old('title', $course->title) }}">
        </div>
        <div>
            <label class="block text-sm font-bold mb-1">Short Description</label>
            <input type="text" name="short_description" class="w-full border rounded-lg px-4 py-2" value="{{ old('short_description', $course->short_description) }}">
        </div>
        <div>
            <label class="block text-sm font-bold mb-1">Description</label>
            <textarea name="description" required rows="4" class="w-full border rounded-lg px-4 py-2">{{ old('description', $course->description) }}</textarea>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-bold mb-1">Course Type</label>
                <select name="type" required class="w-full border rounded-lg px-4 py-2">
                    <option value="1-on-1" {{ $course->type == '1-on-1' ? 'selected' : '' }}>1-on-1</option>
                    <option value="camp" {{ $course->type == 'camp' ? 'selected' : '' }}>Group Camp</option>
                    <option value="recording" {{ $course->type == 'recording' ? 'selected' : '' }}>Pre-recorded</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold mb-1">Price ($)</label>
                <input type="number" step="0.01" name="price" class="w-full border rounded-lg px-4 py-2" value="{{ old('price', $course->price) }}">
            </div>
        </div>
        <div>
            <label class="block text-sm font-bold mb-1">Categories</label>
            <select name="category_ids[]" multiple class="w-full border rounded-lg px-4 py-2 h-32">
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ $course->categories->contains($category->id) ? 'selected' : '' }}>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-bold mb-1">Thumbnail Image</label>
            @if($course->thumbnail)
                <div class="mb-2">
                    <img src="{{ route('secure.asset', ['path' => $course->thumbnail]) }}" alt="Thumbnail" class="w-32 h-auto rounded-lg">
                </div>
            @endif
            <input type="file" name="thumbnail" accept="image/*" class="w-full border rounded-lg px-4 py-2">
        </div>

        <div class="pt-4 text-right">
            <button type="submit" class="btn-primary">Update Course</button>
        </div>
    </form>
</div>
@endsection
