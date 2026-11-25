@extends('layouts.dashboard')
@section('title', 'Skills')
@section('content')
<div class="p-5 md:p-8 space-y-6">
    <h1 class="text-2xl font-bold">Tutor Skills Catalog</h1>
    @if(session('success'))<div class="p-3 rounded-xl bg-secondary-container font-bold">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="p-3 rounded-xl bg-error-container font-bold">{{ session('error') }}</div>@endif

    <form method="POST" action="{{ route('admin.skills.store') }}" class="bg-white border rounded-2xl p-5 flex flex-wrap gap-3">
        @csrf
        <input name="name" required placeholder="New skill name" class="flex-1 p-3 rounded-xl border">
        <input name="description" placeholder="Description" class="flex-1 p-3 rounded-xl border">
        <button class="px-4 py-2 bg-primary text-white rounded-xl font-bold">Add skill</button>
    </form>

    <div class="bg-white border rounded-2xl overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-surface-container-low"><tr><th class="p-3">Skill</th><th class="p-3">Tutors</th><th class="p-3">System</th><th class="p-3"></th></tr></thead>
            <tbody>
                @foreach($skills as $skill)
                <tr class="border-t">
                    <td class="p-3">
                        <form method="POST" action="{{ route('admin.skills.update', $skill) }}" class="flex gap-2">
                            @csrf @method('PUT')
                            <input name="name" value="{{ $skill->name }}" class="p-2 rounded-lg border flex-1">
                            <button class="text-sm font-bold text-primary">Save</button>
                        </form>
                    </td>
                    <td class="p-3">{{ $skill->tutors_count }}</td>
                    <td class="p-3">{{ $skill->is_system ? 'Yes' : 'No' }}</td>
                    <td class="p-3">
                        @unless($skill->is_system)
                        <form method="POST" action="{{ route('admin.skills.destroy', $skill) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')
                            <button class="text-error text-sm font-bold">Delete</button>
                        </form>
                        @endunless
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="bg-white border rounded-2xl p-5 space-y-3">
        <h2 class="font-bold">Map required skills on a course</h2>
        <form method="POST" action="" id="courseSkillForm" class="space-y-3" onsubmit="this.action='/admin/skills/courses/'+document.getElementById('courseSelect').value+'/sync'">
            @csrf
            <select id="courseSelect" required class="w-full p-3 rounded-xl border">
                <option value="">Select course…</option>
                @foreach($courses as $c)
                    <option value="{{ $c->id }}">{{ $c->title }}</option>
                @endforeach
            </select>
            <div class="flex flex-wrap gap-2">
                @foreach($skills as $skill)
                    <label class="text-sm flex gap-1"><input type="checkbox" name="skill_ids[]" value="{{ $skill->id }}"> {{ $skill->name }}</label>
                @endforeach
            </div>
            <button class="px-4 py-2 bg-secondary text-white rounded-xl font-bold">Save course requirements</button>
        </form>
    </div>
</div>
@endsection
