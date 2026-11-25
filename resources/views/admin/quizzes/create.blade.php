@extends('layouts.dashboard')
@section('title', 'Create Quiz')
@section('content')
<div class="p-5 md:p-8 max-w-4xl mx-auto space-y-8">
    <div class="flex items-center justify-between">
        <h1 class="font-headline-lg text-2xl font-bold">Create New Quiz</h1>
        <a href="{{ route('admin.quizzes.index') }}" class="text-on-surface-variant hover:text-primary">&larr; Back to Quizzes</a>
    </div>

    @if(session('error'))
        <div class="bg-error-container text-on-error-container p-4 rounded-xl font-bold">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.quizzes.store') }}" enctype="multipart/form-data" class="space-y-8">
        @csrf

        <!-- Assignment Selection -->
        <div class="clay-card p-6 border-l-4 border-secondary space-y-4">
            <h2 class="text-xl font-bold">Assign To (Optional)</h2>
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-on-surface-variant mb-1">Assign to Course</label>
                    <select name="course_id" id="course_id_select" class="w-full px-4 py-3 border rounded-xl bg-surface-container-low">
                        <option value="">-- No Course (Manual Assignment) --</option>
                        @foreach($courses as $c)
                            <option value="{{ $c->id }}">{{ $c->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-on-surface-variant mb-1">Assign to Specific Students</label>
                    <div class="mb-2">
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">search</span>
                            <input type="text" id="quiz-assign-student-search" placeholder="Search students by name or email..." class="w-full pl-9 pr-4 py-2 bg-white border border-outline-variant/50 rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all text-sm">
                        </div>
                    </div>
                    <div class="max-h-40 overflow-y-auto border rounded-xl p-2 bg-surface-container-low space-y-1" id="student_list_container">
                        @foreach($students as $s)
                            @php
                                $enrolledCourses = $s->enrollments ? $s->enrollments->pluck('course_id')->toArray() : [];
                            @endphp
                            <label class="quiz-student-label student-item flex items-center gap-2 p-2 hover:bg-white rounded-lg cursor-pointer" data-course-ids="{{ json_encode($enrolledCourses) }}" data-student-search="{{ strtolower($s->name.' '.$s->email) }}">
                                <input type="checkbox" name="student_ids[]" value="{{ $s->id }}" class="rounded text-secondary focus:ring-secondary">
                                <span>{{ $s->name }} <span class="text-xs text-on-surface-variant">({{ $s->email }})</span></span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>



        <x-quiz-builder />
    </form>
</div>
@endsection
