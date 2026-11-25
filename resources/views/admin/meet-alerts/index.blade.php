@extends('layouts.dashboard')
@section('title', 'Meet Contact Alerts')
@section('content')
<div class="p-5 md:p-8 max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-on-surface tracking-tight" style="font-family: Nunito, system-ui, sans-serif;">Meet contact alerts</h1>
            <p class="text-sm text-on-surface-variant mt-1">Instant alerts when tutors or students share emails, phone numbers, or messaging links in website class Meets (chat, speech, or captions).</p>
        </div>
        @if($unreviewedCount)
            <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-tertiary text-white text-xs font-bold">
                {{ $unreviewedCount }} unreviewed
            </span>
        @endif
    </div>

    @if(session('success'))
        <div class="rounded-xl bg-tertiary-fixed text-tertiary px-4 py-3 text-sm font-semibold">{{ session('success') }}</div>
    @endif

    <form method="GET" class="flex flex-wrap gap-2 items-center">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search room, name, text…" class="flex-1 min-w-[180px] rounded-xl border border-outline-variant/40 px-3 py-2 text-sm">
        <select name="source" class="rounded-xl border border-outline-variant/40 px-3 py-2 text-sm">
            <option value="">All sources</option>
            @foreach(['chat' => 'Chat', 'speech' => 'Speech', 'captions' => 'Captions'] as $val => $label)
                <option value="{{ $val }}" @selected(request('source') === $val)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="type" class="rounded-xl border border-outline-variant/40 px-3 py-2 text-sm">
            <option value="">All types</option>
            @foreach(['email' => 'Email', 'phone' => 'Phone', 'messaging_link' => 'Messaging link'] as $val => $label)
                <option value="{{ $val }}" @selected(request('type') === $val)>{{ $label }}</option>
            @endforeach
        </select>
        <label class="inline-flex items-center gap-2 text-sm text-on-surface px-2">
            <input type="checkbox" name="unreviewed" value="1" @checked(request()->boolean('unreviewed'))>
            Unreviewed only
        </label>
        <button class="btn-primary btn-sm">Filter</button>
    </form>

    <div class="clay-card bg-white overflow-hidden">
        <div class="divide-y divide-outline-variant/20">
            @forelse($alerts as $alert)
                <div id="alert-{{ $alert->id }}" class="p-5 space-y-2 {{ ($highlightId ?? null) === $alert->id ? 'bg-warning-container' : '' }} {{ $alert->reviewed_at ? 'opacity-80' : '' }}">
                    <div class="flex flex-col md:flex-row md:items-start justify-between gap-3">
                        <div class="space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-bold
                                    {{ $alert->primary_type === 'email' ? 'bg-secondary-container text-on-secondary-container' : ($alert->primary_type === 'phone' ? 'bg-error-container text-error' : 'bg-primary-fixed text-primary') }}">
                                    {{ $alert->typeLabel() }}
                                </span>
                                <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-bold bg-surface-container-low text-on-surface">
                                    {{ $alert->sourceLabel() }}
                                </span>
                                @unless($alert->reviewed_at)
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-bold bg-warning-container text-warning">New</span>
                                @endunless
                            </div>
                            <p class="font-bold text-on-surface">
                                {{ $alert->actor_name ?: 'Unknown' }}
                                <span class="font-normal text-on-surface-variant text-sm">({{ $alert->actor_role ?: 'unknown' }})</span>
                            </p>
                            <p class="text-xs text-on-surface-variant">
                                {{ $alert->occurred_at?->timezone(config('app.timezone'))->format('M j, Y g:i:s A') }}
                                · Room <code class="text-[11px]">{{ $alert->room_id }}</code>
                            </p>
                        </div>
                        @unless($alert->reviewed_at)
                            <form method="POST" action="{{ route('admin.meet-alerts.reviewed', $alert) }}">
                                @csrf
                                <button class="btn btn-outline-primary btn-sm">Mark reviewed</button>
                            </form>
                        @else
                            <p class="text-xs text-on-surface-variant">Reviewed {{ $alert->reviewed_at->diffForHumans() }}</p>
                        @endunless
                    </div>

                    <div class="rounded-xl bg-surface-container-low/60 px-3 py-2 text-sm">
                        <p class="text-[11px] font-bold uppercase tracking-wide text-on-surface-variant mb-1">Shared</p>
                        @forelse($alert->matches ?? [] as $match)
                            <p class="font-semibold text-on-surface">
                                <span class="text-on-surface-variant font-medium">{{ strtoupper($match['type'] ?? 'contact') }}:</span>
                                {{ $match['value'] ?? '—' }}
                            </p>
                        @empty
                            <p class="font-semibold text-on-surface">{{ $alert->typeLabel() }} detected</p>
                        @endforelse
                    </div>

                    <p class="text-sm text-on-surface"><span class="text-on-surface-variant text-xs font-bold uppercase">Context</span><br>{{ $alert->shared_text }}</p>
                </div>
            @empty
                <div class="p-8 text-center text-on-surface-variant text-sm">No contact alerts yet.</div>
            @endforelse
        </div>
    </div>

    <div>{{ $alerts->links() }}</div>
</div>
@if($highlightId)
<script>
document.addEventListener('DOMContentLoaded', function () {
  var el = document.getElementById('alert-{{ (int) $highlightId }}');
  if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
});
</script>
@endif
@endsection
