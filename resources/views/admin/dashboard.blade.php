@extends('layouts.dashboard')

@section('title', 'Admin Dashboard')

@section('content')
@php
    $maxRevenue = max(1, max($trend['revenue'] ?: [1]));
    $maxEnroll  = max(1, max($trend['enrollments'] ?: [1]));
    $mixTotal   = array_sum($categoryMix) ?: 1;
    $mixColors  = ['#7130D8', '#087FF5', '#38B91F', '#FF9800', '#F52B76', '#7C3AED'];
@endphp

<div class="mx-auto max-w-[1400px] space-y-5">

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="font-headline-lg text-on-surface">Admin Basecamp</h1>
            <p class="text-sm text-on-surface-variant mt-0.5">Platform overview and mission control.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.reports') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-outline-variant bg-white px-3.5 py-2 text-xs font-bold text-on-surface hover:border-primary/40 hover:text-primary transition-colors">
                <span class="material-symbols-outlined text-base">bar_chart</span> Reports
            </a>
            <a href="{{ route('admin.settings.index') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-primary px-3.5 py-2 text-xs font-bold text-white shadow-sm hover:bg-primary-container transition-colors">
                <span class="material-symbols-outlined text-base">settings</span> API Settings
            </a>
        </div>
    </div>

    {{-- KPI cards --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @php
            $cards = [
                ['label' => 'Total Revenue', 'value' => '$'.number_format($metrics['total_revenue'], 0), 'sub' => '$'.number_format($metrics['month_revenue'], 0).' this month', 'icon' => 'payments', 'tone' => 'primary'],
                ['label' => 'Enrollments', 'value' => number_format($metrics['total_enrollments']), 'sub' => '+'.$metrics['month_enrollments'].' this month', 'icon' => 'school', 'tone' => 'secondary'],
                ['label' => 'Students', 'value' => number_format($metrics['active_students']), 'sub' => $metrics['active_tutors'].' tutors', 'icon' => 'groups', 'tone' => 'tertiary'],
                ['label' => 'Upcoming Sessions', 'value' => number_format($metrics['upcoming_sessions']), 'sub' => $metrics['published_courses'].' live courses', 'icon' => 'event_upcoming', 'tone' => 'amber'],
            ];
            $tones = [
                'primary' => ['bg' => 'bg-primary-fixed', 'fg' => 'text-primary'],
                'secondary' => ['bg' => 'bg-secondary-fixed', 'fg' => 'text-secondary'],
                'tertiary' => ['bg' => 'bg-tertiary-fixed', 'fg' => 'text-tertiary'],
                'amber' => ['bg' => 'bg-warning-container', 'fg' => 'text-warning'],
            ];
        @endphp
        @foreach($cards as $c)
            @php $t = $tones[$c['tone']]; @endphp
            <div class="clay-card p-4">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold uppercase tracking-wide text-on-surface-variant">{{ $c['label'] }}</p>
                        <h3 class="mt-1 font-headline-lg text-on-surface truncate">{{ $c['value'] }}</h3>
                        <p class="mt-0.5 text-xs text-on-surface-variant truncate">{{ $c['sub'] }}</p>
                    </div>
                    <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl {{ $t['bg'] }} {{ $t['fg'] }}">
                        <span class="material-symbols-outlined text-xl">{{ $c['icon'] }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
        {{-- Revenue trend --}}
        <div class="clay-card p-5 xl:col-span-2">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="font-headline-md text-on-surface">Revenue</h2>
                    <p class="text-xs text-on-surface-variant">Last 14 days · completed payments</p>
                </div>
                <span class="rounded-full bg-primary-fixed px-3 py-1 text-xs font-bold text-primary">
                    ${{ number_format(array_sum($trend['revenue']), 0) }}
                </span>
            </div>
            <div class="flex h-40 items-end gap-1.5" role="img" aria-label="Revenue over the last 14 days">
                @foreach($trend['revenue'] as $i => $val)
                    @php $h = max(3, (int) round(($val / $maxRevenue) * 100)); @endphp
                    <div class="group relative flex-1">
                        <div class="w-full rounded-t-md bg-gradient-to-t from-primary/70 to-primary-container transition-all duration-300 group-hover:opacity-80"
                             style="height: {{ $h }}%; min-height: 3px;" title="{{ $trend['labels'][$i] }} · ${{ number_format($val, 2) }}"></div>
                    </div>
                @endforeach
            </div>
            <div class="mt-2 flex justify-between text-[10px] font-medium text-on-surface-variant">
                <span>{{ $trend['labels'][0] }}</span>
                <span>{{ $trend['labels'][count($trend['labels']) - 1] }}</span>
            </div>
        </div>

        {{-- Category mix --}}
        <div class="clay-card p-5">
            <h2 class="font-headline-md text-on-surface">Enrollment Mix</h2>
            <p class="mb-4 text-xs text-on-surface-variant">By category</p>
            @forelse($categoryMix as $name => $count)
                @php $pct = round(($count / $mixTotal) * 100); @endphp
                <div class="mb-3">
                    <div class="mb-1 flex items-center justify-between text-xs">
                        <span class="font-semibold text-on-surface truncate pr-2">{{ $name }}</span>
                        <span class="text-on-surface-variant">{{ $pct }}%</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-surface-container-high">
                        <div class="h-full rounded-full transition-all duration-500" style="width: {{ $pct }}%; background: {{ $mixColors[$loop->index % count($mixColors)] }};"></div>
                    </div>
                </div>
            @empty
                <p class="py-8 text-center text-sm text-on-surface-variant">No category data yet.</p>
            @endforelse
        </div>
    </div>

    {{-- Upcoming sessions needing attention --}}
    @if($upcoming_bookings->count() > 0)
    <div class="clay-card p-5">
        <div class="mb-3 flex items-center justify-between border-b border-outline-variant pb-3">
            <h2 class="font-headline-md text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">event_upcoming</span> Upcoming Sessions
            </h2>
            <span class="text-xs text-on-surface-variant">Next 7 days</span>
        </div>
        <div class="grid grid-cols-1 gap-2 md:grid-cols-2 xl:grid-cols-3">
            @foreach($upcoming_bookings as $b)
                <div class="flex items-center gap-3 rounded-xl border border-outline-variant/60 bg-surface-container-low p-3">
                    <div class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-primary-fixed text-primary">
                        <span class="material-symbols-outlined text-lg">schedule</span>
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-on-surface">{{ $b->student->name ?? 'Student' }}</p>
                        <p class="truncate text-xs text-on-surface-variant">{{ $b->course->title ?? 'Class' }} · {{ optional($b->scheduled_at)->diffForHumans() }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Recent activity --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="clay-card p-5">
            <div class="mb-4 flex items-center gap-2 border-b border-outline-variant pb-3">
                <span class="material-symbols-outlined text-primary">rocket_launch</span>
                <h2 class="font-headline-md text-on-surface">Recent Enrollments</h2>
            </div>
            @forelse($recent_enrollments as $booking)
                <div class="mb-2 flex items-center justify-between rounded-xl bg-surface-container-low p-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-primary text-sm font-bold text-white">
                            {{ strtoupper(substr($booking->student->name ?? 'S', 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-on-surface">{{ $booking->student->name ?? 'Unknown Student' }}</p>
                            <p class="truncate text-xs text-on-surface-variant">{{ $booking->course->title ?? 'Deleted Course' }}</p>
                        </div>
                    </div>
                    <div class="shrink-0 text-right">
                        <span class="inline-flex rounded-full bg-secondary-fixed px-2.5 py-0.5 text-[11px] font-bold text-secondary">{{ ucfirst($booking->status) }}</span>
                        <p class="mt-0.5 text-[11px] text-on-surface-variant">{{ $booking->created_at->diffForHumans() }}</p>
                    </div>
                </div>
            @empty
                <p class="py-8 text-center text-sm text-on-surface-variant">No enrollments yet.</p>
            @endforelse
        </div>

        <div class="clay-card p-5">
            <div class="mb-4 flex items-center gap-2 border-b border-outline-variant pb-3">
                <span class="material-symbols-outlined text-tertiary">person_add</span>
                <h2 class="font-headline-md text-on-surface">Recent Users</h2>
            </div>
            @forelse($recent_users as $user)
                <div class="mb-2 flex items-center justify-between rounded-xl bg-surface-container-low p-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="grid h-9 w-9 shrink-0 place-items-center rounded-full text-sm font-bold text-white {{ $user->role === 'tutor' ? 'bg-tertiary' : 'bg-outline-variant' }}">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-on-surface">{{ $user->name }}</p>
                            <p class="truncate text-xs text-on-surface-variant">{{ $user->email }}</p>
                        </div>
                    </div>
                    <div class="shrink-0 text-right">
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $user->role === 'tutor' ? 'bg-tertiary-fixed text-tertiary' : 'bg-surface-dim text-on-surface' }}">{{ ucfirst($user->role) }}</span>
                        <p class="mt-0.5 text-[11px] text-on-surface-variant">{{ $user->created_at->diffForHumans() }}</p>
                    </div>
                </div>
            @empty
                <p class="py-8 text-center text-sm text-on-surface-variant">No users yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
