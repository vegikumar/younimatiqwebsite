@extends('layouts.dashboard')
@section('title', 'Post class Activity')
@section('content')
<div class="p-5 md:p-8 space-y-8">
    <h1 class="font-headline-lg text-2xl font-bold">Manage Post class Activity</h1>
    
    @if(session('success'))
        <div class="p-4 bg-tertiary-fixed text-tertiary rounded-xl border border-tertiary-fixed-dim font-medium mb-4">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="p-4 bg-error-container text-on-error-container rounded-xl border border-error/20 font-medium mb-4">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    @endif
    
    <form method="POST" action="{{ route('tutor.assignments.store') }}" enctype="multipart/form-data" class="bg-white rounded-2xl border p-6 space-y-4 max-w-4xl">
        @csrf
        <h3 class="font-bold text-lg mb-2">Create New Assignment</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Course (Optional)</label>
                <select name="course_id" id="course_select" class="w-full px-4 py-3 border rounded-xl focus:border-primary outline-none">
                    <option value="">Select a Course</option>
                    @foreach($courses as $c)
                        <option value="{{ $c->id }}">{{ $c->title }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Due Date</label>
                <input type="datetime-local" name="due_at" class="w-full px-4 py-3 border rounded-xl focus:border-primary outline-none">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Title</label>
            <input type="text" name="title" required placeholder="Assignment title" class="w-full px-4 py-3 border rounded-xl focus:border-primary outline-none">
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Description</label>
                <textarea name="description" placeholder="Description" class="w-full px-4 py-3 border rounded-xl focus:border-primary outline-none" rows="4"></textarea>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">XP Reward</label>
                    <input type="number" name="xp_reward" placeholder="e.g. 100" class="w-full px-4 py-3 border rounded-xl focus:border-primary outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">File Attachment</label>
                    <input type="file" name="assignment_file" class="w-full px-4 py-3 border rounded-xl focus:border-primary outline-none" accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png">
                    <p class="text-xs text-on-surface-variant mt-1">Optional worksheet/prompt for students (PDF, Word, text, or image · max 10MB).</p>
                </div>
            </div>
        </div>

        <div class="border-t border-outline-variant/30 pt-6">
            <h4 class="font-bold text-lg mb-4">Assignment Questions</h4>
            <x-assignment-question-builder />
        </div>

        <div class="border border-outline-variant/50 rounded-xl overflow-hidden mt-4">
            <div class="bg-surface-container-low p-3 border-b border-outline-variant/50 font-bold text-sm">
                Assign to Students (Select at least one)
            </div>
            <div class="p-3 border-b border-outline-variant/40">
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">search</span>
                    <input type="text" id="tutor-assign-student-search" placeholder="Search students by name..." class="w-full pl-9 pr-4 py-2 bg-white border border-outline-variant/50 rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all text-sm">
                </div>
            </div>
            <div class="max-h-48 overflow-y-auto p-3 space-y-2" id="student_list">
                @foreach($students as $student)
                    @php
                        $studentCourses = $enrollments->where('user_id', $student->id)->pluck('course_id')->toJson();
                    @endphp
                    <label class="student-label flex items-center gap-2 p-2 hover:bg-surface-container-lowest rounded-lg cursor-pointer transition-colors" data-courses="{{ $studentCourses }}">
                        <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="w-4 h-4 text-primary rounded border-outline focus:ring-primary">
                        <span class="text-sm font-medium">{{ $student->name }}</span>
                    </label>
                @endforeach
                @if($students->isEmpty())
                    <p class="text-sm text-on-surface-variant p-2">No students found.</p>
                @endif
                <p id="no_students_msg" class="text-sm text-on-surface-variant p-2 hidden">No students found in this course.</p>
            </div>
        </div>

        <button type="submit" class="btn-primary btn-sm mt-4">Create & Assign</button>
    </form>
    
    <div class="space-y-4">
        <h3 class="font-bold text-xl mt-8">Past Post class Activity</h3>
        @foreach($assignments as $a)
        <div class="bg-white rounded-2xl border p-6">
            <div class="flex justify-between items-start mb-2">
                <div>
                    <h3 class="font-bold text-lg flex items-center gap-2">
                        {{ $a->title }} 
                        @if($a->course)
                            <span class="px-2 py-0.5 bg-primary/10 text-primary text-xs rounded-full">{{ $a->course->title }}</span>
                        @endif
                    </h3>
                    <p class="text-sm text-on-surface-variant mt-1">
                        Assigned to: 
                        @foreach($a->students as $st)
                            <span class="inline-block bg-surface-container px-2 py-0.5 rounded-full text-xs font-medium mr-1">{{ $st->name }}</span>
                        @endforeach
                    </p>
                </div>
                <span class="text-sm text-on-surface-variant"><x-local-time :date="$a->created_at" format="date" /></span>
            </div>
            @if($a->file_path)
                <a href="{{ route('secure.asset', ['path' => $a->file_path]) }}" target="_blank" class="text-primary text-sm hover:underline flex items-center gap-1 mt-1"><span class="material-symbols-outlined text-[16px]">attachment</span> View Attached Prompt</a>
            @endif
            @if($a->hasQuestions())
                <p class="text-sm text-on-surface-variant mt-1">{{ count($a->questions) }} question(s)</p>
            @endif
            
            <div class="mt-4 border-t border-outline-variant/30 pt-4">
                <h4 class="font-bold text-sm mb-3">Submissions ({{ $a->submissions->count() }})</h4>
                @foreach($a->submissions as $s)
                <div class="mt-2 p-4 bg-surface-container-low rounded-xl">
                    <p class="text-sm flex justify-between gap-3">
                        <strong>{{ $s->user->name ?? 'Student' }}</strong>
                        <span class="text-xs text-on-surface-variant">Submitted: <x-local-time :date="$s->created_at" format="datetime-short" /></span>
                    </p>
                    <x-assignment-submission-answers :assignment="$a" :submission="$s" />
                    <form method="POST" action="{{ route('tutor.assignments.grade', $s) }}" class="mt-4 flex flex-wrap gap-2">
                        @csrf
                        <input type="number" name="grade" min="0" max="100" placeholder="Grade" value="{{ $s->grade }}" class="w-24 px-3 py-2 border rounded-lg text-sm">
                        <input type="text" name="feedback" placeholder="Feedback" value="{{ $s->feedback }}" class="flex-1 min-w-[200px] px-3 py-2 border rounded-lg text-sm">
                        <button type="submit" class="btn-enroll btn-sm px-4">{{ $s->status === 'graded' ? 'Update Grade' : 'Grade' }}</button>
                    </form>
        <script>
            (function(){
                const search = document.getElementById('tutor-assign-student-search');
                const labels = Array.from(document.querySelectorAll('#student_list .student-label'));
                if(!search) return;
                search.addEventListener('input', function(){
                    const q = this.value.toLowerCase().trim();
                    labels.forEach(l=>{
                        if(q === ''){ l.classList.remove('hidden'); return; }
                        const hay = (l.getAttribute('data-student-search') || l.textContent.toLowerCase());
                        if(hay.includes(q)) l.classList.remove('hidden'); else l.classList.add('hidden');
                    });
                });
            })();
        </script>
                </div>
                @endforeach
                @if($a->submissions->isEmpty())
                    <p class="text-sm text-on-surface-variant italic">No submissions yet.</p>
                @endif
            </div>
        </div>
        @endforeach
        @if($assignments->isEmpty())
            <p class="text-on-surface-variant">No assignments created yet.</p>
        @endif
    </div>
</div>

<x-submission-preview-modal />

<script>
document.addEventListener('DOMContentLoaded', function() {
    const courseSelect = document.getElementById('course_select');
    const studentLabels = document.querySelectorAll('.student-label');
    const noStudentsMsg = document.getElementById('no_students_msg');
});
</script>
@endsection
