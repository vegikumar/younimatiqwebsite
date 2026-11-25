@extends('layouts.dashboard')
@section('title', 'Class Recordings')
@section('content')
<div class="p-5 md:p-8 max-w-4xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-extrabold text-on-surface tracking-tight" style="font-family: Nunito, system-ui, sans-serif;">Class Recordings</h1>
        <p class="text-on-surface-variant text-sm mt-1">Assigned recordings stay available until they are deleted (60 days from the recording date). Request access if a class recording isn’t assigned yet.</p>
    </div>

    @if(session('success'))
        <div class="rounded-xl bg-tertiary-fixed text-tertiary px-4 py-3 text-sm font-semibold">{{ session('success') }}</div>
    @endif

    <section class="space-y-3">
        <h2 class="text-sm font-bold uppercase tracking-wide text-on-surface-variant">Assigned to you</h2>
        @forelse($recordings as $recording)
            <div class="clay-card p-5 bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h3 class="font-bold text-on-surface">{{ $recording->title }}</h3>
                    <p class="text-xs text-on-surface-variant mt-1">
                        {{ optional($recording->recorded_at)->format('M j, Y g:i A') ?? $recording->created_at?->format('M j, Y') }}
                        · Available until {{ optional($recording->expires_at)->format('M j, Y') ?? '—' }}
                    </p>
                </div>
                <a href="{{ route('student.recordings.stream', $recording) }}" target="_blank" class="btn-primary btn-sm inline-flex items-center gap-1 shrink-0">
                    <span class="material-symbols-outlined text-sm">play_circle</span> Watch
                </a>
            </div>
        @empty
            <div class="clay-card p-8 bg-white text-center text-on-surface-variant text-sm">No recordings assigned yet.</div>
        @endforelse
        <div>{{ $recordings->links() }}</div>
    </section>

    <section class="space-y-3">
        <h2 class="text-sm font-bold uppercase tracking-wide text-on-surface-variant">Request access</h2>
        @forelse($requestable as $recording)
            <div class="clay-card p-5 bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h3 class="font-bold text-on-surface">{{ $recording->title }}</h3>
                    <p class="text-xs text-on-surface-variant mt-1">
                        {{ $recording->course?->title }}
                        · {{ optional($recording->recorded_at)->format('M j, Y g:i A') ?? $recording->created_at?->format('M j, Y') }}
                        · Deletes {{ optional($recording->expires_at)->format('M j, Y') ?? '—' }}
                    </p>
                </div>
                @if(in_array($recording->id, $pendingRequestIds))
                    <span class="text-xs font-bold text-warning bg-warning-container px-3 py-1.5 rounded-full">Request pending</span>
                @else
                    <form method="POST" action="{{ route('student.recordings.request', $recording) }}">
                        @csrf
                        <button class="btn btn-outline-primary btn-sm">Request access</button>
                    </form>
                @endif
            </div>
        @empty
            <div class="clay-card p-6 bg-white text-center text-on-surface-variant text-sm">No other class recordings available to request.</div>
        @endforelse
    </section>
</div>
@endsection
