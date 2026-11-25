@extends('layouts.dashboard')

@section('title', 'Live Sessions')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-tertiary relative overflow-hidden">
        <h1 class="font-headline-xl text-headline-xl text-on-surface mb-2">Schedule Live Sessions</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant">Create class meetings for your enrolled students.</p>
        <div class="absolute right-0 top-0 -mr-20 -mt-20 w-64 h-64 bg-tertiary-fixed rounded-full blur-3xl opacity-20 pointer-events-none"></div>
    </div>

    

    @if($errors->any())
        <div class="mb-6 p-4 bg-error-container text-on-error-container rounded-xl text-sm">
            <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-1 md:grid-cols-3 gap-5 md:p-8">
        <div class="clay-card p-5 md:p-8">
            <h2 class="font-headline-md font-bold mb-6 flex items-center gap-2">
                <span class="material-symbols-outlined text-tertiary">event</span> New Session
            </h2>
            <form method="POST" action="{{ route('tutor.sessions.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-bold mb-2">Course</label>
                    <select name="course_id" required class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                        <option value="">Select course</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>{{ $course->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Start Time</label>
                    <input type="datetime-local" name="start_time" required value="{{ old('start_time') }}" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold mb-2">Duration (min)</label>
                        <input type="number" name="duration_minutes" value="{{ old('duration_minutes', 60) }}" min="15" max="180" required class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Max Students</label>
                        <input type="number" name="max_students" value="{{ old('max_students', 10) }}" min="1" max="50" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                    </div>
                </div>
                <button type="submit" class="btn-primary btn-block">Schedule Session</button>
            </form>
        </div>

        <div class="lg:col-span-2 clay-card p-5 md:p-8">
            <h2 class="font-headline-md font-bold mb-6">Your Sessions</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b-2 border-outline-variant">
                            <th class="py-3 px-4 font-bold">Course</th>
                            <th class="py-3 px-4 font-bold">Start Time</th>
                            <th class="py-3 px-4 font-bold">Duration</th>
                            <th class="py-3 px-4 font-bold">Status</th>
                            <th class="py-3 px-4 font-bold">Join</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sessions as $session)
                            <tr class="border-b border-outline-variant hover:bg-surface-container-low">
                                <td class="py-3 px-4 font-medium">{{ $session->course->title ?? 'N/A' }}</td>
                                <td class="py-3 px-4 text-sm"><x-local-time :date="$session->start_time" format="datetime" /></td>
                                <td class="py-3 px-4 text-sm">{{ $session->duration_minutes }} min</td>
                                <td class="py-3 px-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold {{ $session->status === 'scheduled' ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container text-on-surface-variant' }}">
                                        {{ ucfirst($session->status) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    @if($session->start_url)
                                        <a href="{{ $session->start_url }}" target="_blank" class="text-primary font-bold text-sm hover:underline">Start (Host)</a>
                                    @else
                                        <span class="text-on-surface-variant text-sm">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-8 text-center text-on-surface-variant">No sessions scheduled yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
