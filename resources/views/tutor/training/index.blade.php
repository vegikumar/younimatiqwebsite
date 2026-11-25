@extends('layouts.dashboard')
@section('title', 'My Training')
@section('content')
<div class="p-5 md:p-8 space-y-6">
    <h1 class="text-2xl font-bold">Training</h1>
    <p class="text-sm text-on-surface-variant">Complete assigned videos, assignments, and quizzes to unlock verified skills.</p>

    @if(session('success'))<div class="p-3 rounded-xl bg-secondary-container font-bold">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="p-3 rounded-xl bg-error-container font-bold">{{ session('error') }}</div>@endif

    <div class="bg-white border rounded-2xl p-5">
        <h2 class="font-bold mb-3">My skills</h2>
        <div class="flex flex-wrap gap-2">
            @forelse($skills as $ts)
                <span class="px-3 py-1 rounded-full text-sm {{ $ts->verified ? 'bg-tertiary-fixed font-bold' : 'bg-surface-container-high' }}">
                    {{ $ts->skill->name }} {{ $ts->verified ? '✓' : '(unverified)' }}
                </span>
            @empty
                <span class="text-sm text-on-surface-variant">No skills yet.</span>
            @endforelse
        </div>
    </div>

    @forelse($assignments as $assignment)
        @php $module = $assignment->module; @endphp
        <div class="bg-white border rounded-2xl p-5 space-y-4">
            <h2 class="font-bold text-lg">{{ $module->title }}</h2>
            <p class="text-sm text-on-surface-variant">{{ $module->description }}</p>
            @foreach($module->items as $item)
                @php $p = $progress->get($item->id); @endphp
                <div class="border rounded-xl p-4 space-y-3">
                    <div class="flex justify-between gap-2">
                        <div>
                            <span class="text-xs font-bold uppercase">{{ $item->type }}</span>
                            <h3 class="font-bold">{{ $item->title }}</h3>
                            <p class="text-sm text-on-surface-variant">{{ $item->instructions }}</p>
                        </div>
                        <span class="text-xs font-bold {{ ($p?->status==='passed') ? 'text-green-700' : 'text-on-surface-variant' }}">{{ $p?->status ?? 'pending' }}</span>
                    </div>

                    @if($item->type === 'video')
                        @if($item->video_url || $item->video_path)
                            <video controls class="w-full max-h-72 rounded-xl bg-black"
                                   src="{{ $item->video_url ?: asset('storage/'.$item->video_path) }}"></video>
                        @endif
                        @if(($p?->status ?? '') !== 'passed')
                            <form method="POST" action="{{ route('tutor.training.video', $item) }}">@csrf
                                <button class="px-4 py-2 bg-primary text-white rounded-xl font-bold text-sm">Mark video complete</button>
                            </form>
                        @endif
                    @elseif($item->type === 'assignment')
                        @if(($p?->status ?? '') !== 'passed')
                            <form method="POST" action="{{ route('tutor.training.assignment', $item) }}" enctype="multipart/form-data" class="space-y-2">
                                @csrf
                                <textarea name="submission_text" rows="3" class="w-full p-3 rounded-xl border" placeholder="Your response"></textarea>
                                <input type="file" name="submission_file" class="text-sm">
                                <button class="px-4 py-2 bg-secondary text-white rounded-xl font-bold text-sm">Submit assignment</button>
                            </form>
                        @endif
                    @elseif($item->type === 'quiz')
                        @if(($p?->status ?? '') !== 'passed')
                            <form method="POST" action="{{ route('tutor.training.quiz', $item) }}" class="space-y-3">
                                @csrf
                                @foreach(($item->quiz_questions ?? []) as $i => $q)
                                    <div>
                                        <p class="font-bold text-sm mb-1">{{ $q['q'] ?? 'Question' }}</p>
                                        @foreach(($q['options'] ?? []) as $oi => $opt)
                                            <label class="flex gap-2 text-sm"><input type="radio" name="answers[{{ $i }}]" value="{{ $oi }}" required> {{ $opt }}</label>
                                        @endforeach
                                    </div>
                                @endforeach
                                <button class="px-4 py-2 bg-tertiary text-white rounded-xl font-bold text-sm">Submit quiz</button>
                            </form>
                        @else
                            <p class="text-sm">Score: {{ $p->score }}%</p>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>
    @empty
        <div class="bg-white border rounded-2xl p-8 text-center text-on-surface-variant">No training assigned yet. An admin will assign modules after approval.</div>
    @endforelse
</div>
@endsection
