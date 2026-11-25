@extends('layouts.dashboard')

@section('title', 'My Reviews')

@section('content')
<div class="p-5 md:p-8 space-y-8">
    <div>
        <h1 class="font-headline-lg text-headline-lg font-bold mb-2">My Reviews ⭐</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant">Feedback you've given on courses and tutors.</p>
    </div>

    <!-- Session success is now handled by global toast -->

    @if($unreviewedCourses->isNotEmpty())
    <div class="bg-white p-6 rounded-2xl border border-surface-container-high shadow-sm">
        <h2 class="font-headline-md font-bold mb-4">Write a Review</h2>
        <form method="POST" action="{{ route('student.reviews.store') }}" class="space-y-4 max-w-xl">
            @csrf
            <div>
                <label class="block text-sm font-bold mb-2">Course</label>
                <select name="course_id" required class="w-full bg-surface-container-low border border-surface-container-high rounded-xl px-4 py-3 focus:outline-none focus:border-primary">
                    <option value="">Select a course</option>
                    @foreach($unreviewedCourses as $course)
                        <option value="{{ $course->id }}">{{ $course->title }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold mb-2">Rating</label>
                <select name="rating" required class="w-full bg-surface-container-low border border-surface-container-high rounded-xl px-4 py-3 focus:outline-none focus:border-primary">
                    @for($i = 5; $i >= 1; $i--)
                        <option value="{{ $i }}">{{ $i }} Star{{ $i > 1 ? 's' : '' }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold mb-2">Comment</label>
                <textarea name="comment" rows="3" class="w-full bg-surface-container-low border border-surface-container-high rounded-xl px-4 py-3 focus:outline-none focus:border-primary" placeholder="Share your experience..."></textarea>
            </div>
            <button type="submit" class="btn-primary btn-sm">Submit Review</button>
        </form>
    </div>
    @endif

    <div class="space-y-6">
        @forelse($reviews as $review)
        <div class="bg-white p-6 rounded-2xl border border-surface-container-high shadow-sm" x-data="{ editing: false }">
            <div x-show="!editing">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="font-headline-md font-bold mb-1">{{ $review->course->title ?? 'Deleted Course' }}</h3>
                        <p class="text-sm text-on-surface-variant">Reviewed on <x-local-time :date="$review->created_at" format="date" /></p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="flex gap-1 text-tertiary">
                            @for($i = 0; $i < 5; $i++)
                                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' {{ $i < $review->rating ? '1' : '0' }};">star</span>
                            @endfor
                        </div>
                        <button @click="editing = true" class="text-on-surface-variant hover:text-primary transition-colors" title="Edit Review">
                            <span class="material-symbols-outlined text-sm">edit</span>
                        </button>
                    </div>
                </div>
                @if($review->comment)
                    <p class="text-on-surface leading-relaxed">{{ $review->comment }}</p>
                @endif
            </div>

            <div x-show="editing" style="display: none;">
                <h3 class="font-headline-md font-bold mb-4">Edit Review</h3>
                <form method="POST" action="{{ route('student.reviews.store') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="course_id" value="{{ $review->course_id }}">
                    <div>
                        <label class="block text-sm font-bold mb-2">Rating</label>
                        <select name="rating" required class="w-full bg-surface-container-low border border-surface-container-high rounded-xl px-4 py-3 focus:outline-none focus:border-primary">
                            @for($i = 5; $i >= 1; $i--)
                                <option value="{{ $i }}" {{ $review->rating == $i ? 'selected' : '' }}>{{ $i }} Star{{ $i > 1 ? 's' : '' }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Comment</label>
                        <textarea name="comment" rows="3" class="w-full bg-surface-container-low border border-surface-container-high rounded-xl px-4 py-3 focus:outline-none focus:border-primary" placeholder="Share your experience...">{{ $review->comment }}</textarea>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="btn-primary btn-sm">Save Changes</button>
                        <button type="button" @click="editing = false" class="btn-outline btn-sm">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
        @empty
        <div class="bg-surface-container-low border-2 border-dashed border-surface-container-high rounded-2xl flex flex-col items-center justify-center p-5 md:p-8 text-center text-on-surface-variant">
            <span class="material-symbols-outlined text-4xl mb-2">star_rate</span>
            <p class="font-bold mb-2">No reviews yet.</p>
        </div>
        @endforelse
    </div>
</div>
@endsection
