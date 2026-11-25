@extends('layouts.dashboard')
@section('title', 'Class Recordings')
@section('content')
<div class="p-5 md:p-8 max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-on-surface tracking-tight" style="font-family: Nunito, system-ui, sans-serif;">Class recordings & timeline</h1>
            <p class="text-sm text-on-surface-variant mt-1">Recordings are kept for {{ \App\Models\ClassRecording::RETENTION_DAYS }} days from creation. Students only see recordings you assign after they request access.</p>
        </div>
        <div class="flex gap-2 text-sm font-bold">
            <a href="{{ route('admin.recordings.index', ['tab' => 'timeline']) }}" class="px-3 py-1.5 rounded-full {{ ($tab ?? '') === 'timeline' ? 'bg-primary text-white' : 'bg-surface-container-low text-on-surface' }}">Timeline</a>
            <a href="{{ route('admin.recordings.index', ['tab' => 'recordings']) }}" class="px-3 py-1.5 rounded-full {{ ($tab ?? '') === 'recordings' ? 'bg-primary text-white' : 'bg-surface-container-low text-on-surface' }}">All recordings</a>
            <a href="{{ route('admin.recordings.index', ['tab' => 'requests']) }}" class="px-3 py-1.5 rounded-full {{ ($tab ?? '') === 'requests' ? 'bg-primary text-white' : 'bg-surface-container-low text-on-surface' }}">
                Requests
                @if($pendingRequests->count())
                    <span class="ml-1 inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1 rounded-full bg-tertiary text-white text-[10px]">{{ $pendingRequests->count() }}</span>
                @endif
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl bg-tertiary-fixed text-tertiary px-4 py-3 text-sm font-semibold">{{ session('success') }}</div>
    @endif

    @if(($tab ?? 'timeline') === 'requests')
        <div class="clay-card bg-white overflow-hidden">
            <div class="px-5 py-4 border-b border-outline-variant/30 font-bold text-on-surface">Pending student requests</div>
            <div class="divide-y divide-outline-variant/20">
                @forelse($pendingRequests as $req)
                    <div class="p-5 flex flex-col lg:flex-row lg:items-center gap-4 justify-between">
                        <div>
                            <p class="font-bold text-on-surface">{{ $req->student?->name }} <span class="font-normal text-on-surface-variant text-sm">({{ $req->student?->email }})</span></p>
                            <p class="text-sm text-on-surface mt-1">{{ $req->recording?->title }}</p>
                            <p class="text-xs text-on-surface-variant mt-1">
                                Recorded {{ optional($req->recording?->recorded_at)->format('M j, Y g:i A') ?? '—' }}
                                · Deletes {{ optional($req->recording?->expires_at)->format('M j, Y') ?? '—' }}
                            </p>
                            @if($req->note)
                                <p class="text-xs mt-2 text-on-surface-variant italic">“{{ $req->note }}”</p>
                            @endif
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('admin.recordings.assign') }}">
                                @csrf
                                <input type="hidden" name="class_recording_id" value="{{ $req->class_recording_id }}">
                                <input type="hidden" name="recording_request_id" value="{{ $req->id }}">
                                <input type="hidden" name="student_ids[]" value="{{ $req->student_id }}">
                                <button class="btn-primary btn-sm">Assign & approve</button>
                            </form>
                            <form method="POST" action="{{ route('admin.recordings.requests.reject', $req) }}">
                                @csrf
                                <button class="btn btn-outline-primary btn-sm">Reject</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-on-surface-variant text-sm">No pending requests.</div>
                @endforelse
            </div>
        </div>
    @elseif(($tab ?? '') === 'recordings')
        <form method="GET" class="flex gap-2">
            <input type="hidden" name="tab" value="recordings">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search title or room…" class="flex-1 rounded-xl border border-outline-variant/40 px-3 py-2 text-sm">
            <button class="btn-primary btn-sm">Search</button>
        </form>

        <div class="space-y-4">
            @forelse($recordings as $recording)
                <div class="clay-card bg-white p-5 space-y-3">
                    <div class="flex flex-col md:flex-row md:items-start justify-between gap-3">
                        <div>
                            <h2 class="font-bold text-on-surface text-lg">{{ $recording->title }}</h2>
                            <p class="text-xs text-on-surface-variant mt-1">
                                Course: {{ $recording->course?->title ?? '—' }}
                                · Tutor: {{ $recording->booking?->tutor?->name ?? $recording->liveSession?->tutor?->name ?? '—' }}
                                · Class date: {{ optional($recording->booking?->scheduled_at ?? $recording->liveSession?->start_time ?? $recording->recorded_at)->format('M j, Y g:i A') ?? '—' }}
                            </p>
                            <p class="text-xs text-on-surface-variant mt-1">
                                Recorded {{ optional($recording->recorded_at)->format('M j, Y g:i A') ?? optional($recording->created_at)->format('M j, Y') }}
                                · Deletes {{ optional($recording->expires_at)->format('M j, Y') ?? '—' }}
                                @if($recording->deleted_from_storage_at)
                                    · <span class="text-error font-bold">Removed {{ $recording->deleted_from_storage_at->format('M j, Y') }}</span>
                                @elseif(!$recording->isAvailable())
                                    · <span class="text-error font-bold">Expired</span>
                                @else
                                    · <span class="text-on-tertiary-container font-bold">Available</span>
                                @endif
                            </p>
                            <p class="text-xs text-on-surface-variant mt-1">
                                Assigned to:
                                @forelse($recording->assignments as $a)
                                    {{ $a->student?->name }}{{ !$loop->last ? ', ' : '' }}
                                @empty
                                    none
                                @endforelse
                            </p>
                        </div>
                    </div>

                    @if($recording->isAvailable())
                        <form method="POST" action="{{ route('admin.recordings.assign') }}" class="flex flex-col sm:flex-row gap-2 items-stretch sm:items-end border-t border-outline-variant/20 pt-3">
                            @csrf
                            <input type="hidden" name="class_recording_id" value="{{ $recording->id }}">
                            <div class="flex-1">
                                <label class="text-[11px] font-bold uppercase tracking-wide text-on-surface-variant">Assign students</label>
                                <select name="student_ids[]" multiple class="mt-1 w-full rounded-xl border border-outline-variant/40 px-3 py-2 text-sm min-h-[88px]">
                                    @foreach($students as $student)
                                        <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->email }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <button class="btn-primary btn-sm shrink-0">Assign</button>
                        </form>
                    @endif
                </div>
            @empty
                <div class="clay-card p-10 bg-white text-center text-on-surface-variant">No recordings synced yet.</div>
            @endforelse
        </div>
        <div>{{ $recordings->links() }}</div>
    @else
        <form method="GET" class="flex flex-wrap gap-2 items-end">
            <input type="hidden" name="tab" value="timeline">
            <div>
                <label class="text-[11px] font-bold uppercase text-on-surface-variant">From</label>
                <input type="date" name="from" value="{{ request('from', now()->subDays(14)->toDateString()) }}" class="block mt-1 rounded-xl border border-outline-variant/40 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase text-on-surface-variant">To</label>
                <input type="date" name="to" value="{{ request('to', now()->addDays(7)->toDateString()) }}" class="block mt-1 rounded-xl border border-outline-variant/40 px-3 py-2 text-sm">
            </div>
            <button class="btn-primary btn-sm">Filter</button>
        </form>

        <div class="clay-card bg-white overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-surface-container-low text-left text-[11px] uppercase tracking-wide text-on-surface-variant">
                    <tr>
                        <th class="px-4 py-3 font-bold">Class date</th>
                        <th class="px-4 py-3 font-bold">Type</th>
                        <th class="px-4 py-3 font-bold">Class / course</th>
                        <th class="px-4 py-3 font-bold">Tutor</th>
                        <th class="px-4 py-3 font-bold">Student</th>
                        <th class="px-4 py-3 font-bold">Recording</th>
                        <th class="px-4 py-3 font-bold">Deletes on</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    @forelse($timeline as $row)
                        <tr class="align-top">
                            <td class="px-4 py-3 whitespace-nowrap text-on-surface font-semibold">{{ optional($row['when'])->format('M j, Y g:i A') ?? '—' }}</td>
                            <td class="px-4 py-3 text-on-surface-variant">{{ $row['type'] }}</td>
                            <td class="px-4 py-3">
                                <div class="font-bold text-on-surface">{{ $row['title'] }}</div>
                                <div class="text-xs text-on-surface-variant mt-0.5">Status: {{ $row['status'] ?? '—' }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $row['tutor'] ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $row['student'] ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @if($row['recording'])
                                    <div class="font-semibold text-on-surface">{{ $row['recording']->title }}</div>
                                    <div class="text-xs text-on-surface-variant">Created {{ optional($row['recorded_at'])->format('M j, Y') ?? '—' }}</div>
                                    @if($row['deleted_at'])
                                        <div class="text-xs text-error font-bold">Deleted {{ $row['deleted_at']->format('M j, Y') }}</div>
                                    @endif
                                @else
                                    <span class="text-on-surface-variant">No recording yet</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($row['deletes_at'])
                                    <span class="font-bold {{ ($row['deletes_at']->isPast() || $row['deleted_at']) ? 'text-error' : 'text-on-surface' }}">{{ $row['deletes_at']->format('M j, Y') }}</span>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center text-on-surface-variant">No classes in this range.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
