@extends('layouts.dashboard')

@section('title', 'Email Automations')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
            <div>
                <h1 class="font-headline-xl text-headline-xl text-on-surface mb-2">Email Automations</h1>
                <p class="font-body-lg text-body-lg text-on-surface-variant">
                    Create multiple campaigns — each with its own schedule, audiences, include/exclude lists, and copy.
                </p>
            </div>
            <a href="{{ route('admin.email-automations.create') }}" class="btn-primary inline-flex items-center gap-2 shrink-0">
                <span class="material-symbols-outlined text-base">add</span>
                New automation
            </a>
        </div>
        <div class="absolute right-0 top-0 -mr-20 -mt-20 w-64 h-64 bg-primary-fixed rounded-full blur-3xl opacity-20 pointer-events-none"></div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-8">
        <div class="clay-card p-4"><div class="text-xs text-on-surface-variant">Sent 7d</div><div class="text-xl font-bold">{{ number_format($stats['sent_7d']) }}</div></div>
        <div class="clay-card p-4"><div class="text-xs text-on-surface-variant">Failed 7d</div><div class="text-xl font-bold">{{ number_format($stats['failed_7d']) }}</div></div>
        <div class="clay-card p-4"><div class="text-xs text-on-surface-variant">Sent 30d</div><div class="text-xl font-bold">{{ number_format($stats['sent_30d']) }}</div></div>
        <div class="clay-card p-4"><div class="text-xs text-on-surface-variant">Queued now</div><div class="text-xl font-bold">{{ number_format($stats['queued']) }}</div></div>
        <div class="clay-card p-4"><div class="text-xs text-on-surface-variant">Unsubscribed</div><div class="text-xl font-bold">{{ number_format($stats['suppressions']) }}</div></div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-secondary-container text-on-secondary-container font-medium">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 rounded-xl bg-error-container text-on-error-container font-medium">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-10">
        <div class="xl:col-span-2 space-y-4">
            @forelse($automations as $automation)
                @php
                    $count = $audienceCounts[$automation->id] ?? 0;
                    $next = $nextSendAts[$automation->id] ?? null;
                    $days = collect($automation->days_of_week ?? [])->map(fn ($d) => $dayLabels[(int) $d] ?? $d)->implode(', ');
                @endphp
                <div class="clay-card p-5 md:p-6 flex flex-col gap-4">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                        <div>
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <h2 class="font-headline-md font-bold text-on-surface">{{ $automation->name }}</h2>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $automation->is_enabled ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container text-on-surface-variant' }}">
                                    {{ $automation->is_enabled ? 'ON' : 'OFF' }}
                                </span>
                            </div>
                            <p class="text-sm text-on-surface-variant">{{ $automation->subject }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2 shrink-0">
                            <a href="{{ route('admin.email-automations.edit', $automation) }}" class="px-4 py-2 rounded-xl border border-outline-variant font-bold text-sm hover:border-primary">Edit</a>
                            <form method="POST" action="{{ route('admin.email-automations.destroy', $automation) }}" onsubmit="return confirm('Delete “{{ $automation->name }}”? This cannot be undone.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-4 py-2 rounded-xl border border-error/40 text-error font-bold text-sm hover:bg-error-container">Delete</button>
                            </form>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 text-xs">
                        <span class="px-2.5 py-1 rounded-full bg-surface-container-low">Audience: {{ number_format($count) }}</span>
                        <span class="px-2.5 py-1 rounded-full bg-surface-container-low">{{ $days ?: 'No days set' }} · {{ $automation->send_time }} {{ $automation->timezone }}</span>
                        @if($next)
                            <span class="px-2.5 py-1 rounded-full bg-primary/10 text-primary">Next: {{ $next->timezone($automation->timezone ?: 'Asia/Kolkata')->format('D, M j · g:i A T') }}</span>
                        @endif
                        @if($automation->last_sent_at)
                            <span class="px-2.5 py-1 rounded-full bg-surface-container-low">Last: <x-local-time :date="$automation->last_sent_at" format="datetime-short" /> ({{ $automation->last_sent_count }})</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="clay-card p-8 text-center">
                    <p class="text-on-surface-variant mb-4">No email automations yet.</p>
                    <a href="{{ route('admin.email-automations.create') }}" class="btn-primary">Create your first automation</a>
                </div>
            @endforelse
        </div>

        <div class="space-y-6">
            <div class="clay-card p-5 md:p-8">
                <h2 class="font-headline-md font-bold mb-4">How to use</h2>
                <ol class="space-y-2 text-sm text-on-surface-variant list-decimal pl-5">
                    <li><strong class="text-on-surface">New automation</strong> — create as many campaigns as you need.</li>
                    <li><strong class="text-on-surface">Edit</strong> — set copy, schedule, audiences, include/exclude.</li>
                    <li><strong class="text-on-surface">Test → enable</strong> — send a test, then turn auto-send on.</li>
                    <li><strong class="text-on-surface">Delete</strong> — remove campaigns you no longer need (keep at least one).</li>
                    <li><strong class="text-on-surface">Course triggers</strong> — pick which automation’s audience to use on a course edit page.</li>
                </ol>
            </div>

            <div class="clay-card p-5 md:p-8">
                <h2 class="font-headline-md font-bold mb-4">Suppression / unsubscribe list</h2>
                <form method="POST" action="{{ route('admin.email-automations.suppressions.store') }}" class="flex flex-col sm:flex-row gap-2 mb-4">
                    @csrf
                    <input type="email" name="email" required placeholder="email@example.com" class="flex-1 p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary">
                    <button type="submit" class="px-4 py-3 rounded-xl border border-outline-variant font-bold text-sm">Block</button>
                </form>
                <div class="space-y-2 max-h-56 overflow-y-auto text-sm">
                    @forelse($suppressions as $row)
                        <div class="flex items-center justify-between gap-2 p-2 rounded-lg bg-surface-container-low">
                            <div>
                                <div class="font-medium">{{ $row->email }}</div>
                                <div class="text-xs text-on-surface-variant">{{ $row->reason }} · {{ optional($row->suppressed_at)->diffForHumans() }}</div>
                            </div>
                            <form method="POST" action="{{ route('admin.email-automations.suppressions.destroy', $row) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-bold text-error">Remove</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-on-surface-variant">No suppressions yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="clay-card p-5 md:p-8">
                <h2 class="font-headline-md font-bold mb-4">Recent runs</h2>
                <div class="space-y-2 max-h-72 overflow-y-auto text-sm">
                    @forelse($recentRuns as $run)
                        <div class="p-3 rounded-xl bg-surface-container-low">
                            <div class="flex justify-between gap-2">
                                <span class="font-bold">{{ $run->automation?->name ?? 'Campaign' }} · {{ ucfirst($run->trigger) }}</span>
                                <span class="text-xs text-on-surface-variant"><x-local-time :date="$run->created_at" format="datetime-short" /></span>
                            </div>
                            <div class="text-xs text-on-surface-variant mt-1">
                                {{ ucfirst($run->status) }} · Recipients {{ $run->recipient_count }} · Queued {{ $run->sent_count }}
                            </div>
                        </div>
                    @empty
                        <p class="text-on-surface-variant">No sends yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="clay-card p-5 md:p-8">
                <h2 class="font-headline-md font-bold mb-4">Recent delivery logs</h2>
                <div class="overflow-x-auto max-h-72 overflow-y-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-outline-variant">
                                <th class="py-2 pr-2">To</th>
                                <th class="py-2 pr-2">Status</th>
                                <th class="py-2">When</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentLogs as $log)
                                <tr class="border-b border-outline-variant/40">
                                    <td class="py-2 pr-2">{{ $log->to_email }}</td>
                                    <td class="py-2 pr-2">{{ $log->status }}</td>
                                    <td class="py-2"><x-local-time :date="$log->created_at" format="datetime-short" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="py-4 text-on-surface-variant">No campaign emails logged yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
