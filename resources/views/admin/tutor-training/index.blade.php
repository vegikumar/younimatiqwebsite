@extends('layouts.dashboard')
@section('title', 'Tutor Training')
@section('content')
<div class="p-5 md:p-8 space-y-6">
    <h1 class="text-2xl font-bold">Tutor Training Modules</h1>
    <p class="text-sm text-on-surface-variant">Create videos, assignments, and quizzes. Assign them to tutors from the Tutors kanban. Passing a full module grants linked skills.</p>

    @if(session('success'))<div class="p-3 rounded-xl bg-secondary-container font-bold">{{ session('success') }}</div>@endif

    <form method="POST" action="{{ route('admin.tutor-training.modules.store') }}" class="bg-white border rounded-2xl p-5 space-y-3">
        @csrf
        <h2 class="font-bold">New module</h2>
        <input name="title" required placeholder="Module title" class="w-full p-3 rounded-xl border bg-surface-container-low">
        <textarea name="description" placeholder="Description" class="w-full p-3 rounded-xl border bg-surface-container-low"></textarea>
        <div class="flex flex-wrap gap-2">
            @foreach($skills as $skill)
                <label class="text-sm flex gap-1 items-center"><input type="checkbox" name="skill_ids[]" value="{{ $skill->id }}"> {{ $skill->name }}</label>
            @endforeach
        </div>
        <button class="px-4 py-2 bg-primary text-white rounded-xl font-bold">Create module</button>
    </form>

    @foreach($modules as $module)
        <div class="bg-white border rounded-2xl p-5 space-y-4">
            <div class="flex justify-between gap-3">
                <div>
                    <h3 class="font-bold text-lg">{{ $module->title }}</h3>
                    <p class="text-sm text-on-surface-variant">{{ $module->description }}</p>
                    <div class="flex flex-wrap gap-1 mt-2">
                        @foreach($module->skillRewards as $s)
                            <span class="text-xs px-2 py-0.5 rounded-full bg-tertiary-fixed">{{ $s->name }}</span>
                        @endforeach
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.tutor-training.modules.destroy', $module) }}" onsubmit="return confirm('Delete module?')">
                    @csrf @method('DELETE')
                    <button class="text-error text-sm font-bold">Delete</button>
                </form>
            </div>

            <ul class="space-y-2">
                @foreach($module->items as $item)
                    <li class="p-3 rounded-xl bg-surface-container-low text-sm flex justify-between">
                        <span><strong class="uppercase text-xs">{{ $item->type }}</strong> — {{ $item->title }}</span>
                        @if($item->type==='quiz')<span class="text-xs">Pass ≥ {{ $item->pass_score }}%</span>@endif
                    </li>
                @endforeach
            </ul>

            <form method="POST" action="{{ route('admin.tutor-training.items.store', $module) }}" enctype="multipart/form-data" class="grid md:grid-cols-2 gap-3 border-t pt-4">
                @csrf
                <select name="type" required class="p-3 rounded-xl border">
                    <option value="document" selected>Document / PDF / PPT / files</option>
                    <option value="video">Video</option>
                    <option value="assignment">Assignment</option>
                    <option value="quiz">Quiz</option>
                </select>
                <input name="title" required placeholder="Skill title" class="p-3 rounded-xl border">
                <textarea name="instructions" placeholder="Instructions" class="md:col-span-2 p-3 rounded-xl border"></textarea>
                <input type="url" name="document_urls[]" placeholder="Document URL (optional)" class="p-3 rounded-xl border">
                <input type="file" name="document_files[]" multiple accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.png,.jpg,.jpeg,.zip,.mp4,.webm" class="p-3 rounded-xl border text-sm">
                <input type="url" name="video_url" placeholder="Video URL (optional, for Video type)" class="p-3 rounded-xl border">
                <input type="file" name="video_file" accept="video/*" class="p-3 rounded-xl border text-sm">
                <input type="number" name="pass_score" value="70" min="1" max="100" placeholder="Pass score %" class="p-3 rounded-xl border">
                <textarea name="quiz_json" placeholder="Quiz questions (admin only — use the React training page for the visual quiz builder)" class="md:col-span-2 p-3 rounded-xl border text-xs"></textarea>
                <button class="md:col-span-2 px-4 py-2 bg-secondary text-white rounded-xl font-bold">Add item</button>
            </form>
        </div>
    @endforeach
</div>
@endsection
