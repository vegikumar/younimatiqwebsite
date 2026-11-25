@extends('layouts.dashboard')

@section('title', 'Tutor Dashboard')

@section('content')
@php
    $punctuality = $metrics['punctuality_rate'] ?? null;
    $hasUpcoming = $bookings->isNotEmpty() || $campBookings->isNotEmpty() || $sessions->isNotEmpty();
@endphp

<div class="mx-auto max-w-[1400px] space-y-5">

    {{-- Welcome --}}
    <div class="clay-card relative overflow-hidden p-5">
        <div class="pointer-events-none absolute -right-8 -top-8 h-32 w-32 rounded-full bg-primary/10"></div>
        <div class="relative z-10 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="font-headline-lg text-on-surface">Welcome back, {{ Auth::user()->name ?? 'Tutor' }}!</h1>
                <p class="mt-0.5 text-sm text-on-surface-variant">Track learners, host classes, and grow your teaching practice.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('tutor.training.index') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-secondary px-3.5 py-2 text-xs font-bold text-white shadow-sm">
                    <span class="material-symbols-outlined text-base">workspace_premium</span> Training
                </a>
                <a href="{{ route('tutor.classes') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-primary px-3.5 py-2 text-xs font-bold text-white shadow-sm hover:bg-primary-container transition-colors">
                    <span class="material-symbols-outlined text-base">videocam</span> My Classes
                </a>
            </div>
        </div>
        @php $dashSkills = Auth::user()->tutorSkills()->with('skill')->get(); @endphp
        @if($dashSkills->isNotEmpty())
            <div class="relative z-10 mt-3 flex flex-wrap gap-2">
                @foreach($dashSkills as $ts)
                    <span class="rounded-full px-2.5 py-1 text-[11px] {{ $ts->verified ? 'bg-tertiary-fixed font-bold' : 'bg-surface-container-high' }}">{{ $ts->skill->name }}{{ $ts->verified ? '' : ' (pending)' }}</span>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Metrics --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @php
            $cards = [
                ['icon' => 'group', 'val' => number_format($metrics['learners_taught'] ?? 0), 'label' => 'Learners Taught', 'tone' => 'secondary'],
                ['icon' => 'schedule', 'val' => number_format($metrics['hours_worked'] ?? 0).' hrs', 'label' => 'Hours Worked', 'tone' => 'tertiary'],
                ['icon' => 'star', 'val' => number_format($metrics['class_score'] ?? 5.0, 1), 'label' => 'Class Score ('.($metrics['review_count'] ?? 0).' reviews)', 'tone' => 'amber'],
                ['icon' => 'trending_up', 'val' => ($metrics['re_enrollment_rate'] ?? 0).'%', 'label' => 'Re-enrollment Rate', 'tone' => 'primary'],
            ];
            $tones = [
                'secondary' => ['bg' => 'bg-secondary-fixed', 'fg' => 'text-secondary'],
                'tertiary' => ['bg' => 'bg-tertiary-fixed', 'fg' => 'text-tertiary'],
                'amber' => ['bg' => 'bg-warning-container', 'fg' => 'text-warning'],
                'primary' => ['bg' => 'bg-primary-fixed', 'fg' => 'text-primary'],
            ];
        @endphp
        @foreach($cards as $c)
            @php $t = $tones[$c['tone']]; @endphp
            <div class="clay-card p-4">
                <div class="mb-2 grid h-9 w-9 place-items-center rounded-xl {{ $t['bg'] }} {{ $t['fg'] }}">
                    <span class="material-symbols-outlined text-lg">{{ $c['icon'] }}</span>
                </div>
                <p class="font-headline-lg text-on-surface truncate">{{ $c['val'] }}</p>
                <p class="text-xs text-on-surface-variant">{{ $c['label'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Earnings + reliability strip --}}
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="clay-card flex items-center gap-3 p-4">
            <span class="material-symbols-outlined text-2xl text-primary">payments</span>
            <div>
                <p class="font-bold text-on-surface">${{ number_format($metrics['monthly_earnings'] ?? 0, 2) }}</p>
                <p class="text-xs text-on-surface-variant">Earned this month</p>
            </div>
        </div>
        <div class="clay-card flex items-center gap-3 p-4">
            <span class="material-symbols-outlined text-2xl text-tertiary">account_balance_wallet</span>
            <div>
                <p class="font-bold text-on-surface">${{ number_format($metrics['yearly_earnings'] ?? 0, 2) }}</p>
                <p class="text-xs text-on-surface-variant">Earned this year</p>
            </div>
        </div>
        <div class="clay-card flex items-center gap-3 p-4">
            <span class="material-symbols-outlined text-2xl text-secondary">verified</span>
            <div>
                <p class="font-bold text-on-surface">{{ $punctuality !== null ? $punctuality.'%' : 'New' }}</p>
                <p class="text-xs text-on-surface-variant">Punctuality @if(($metrics['past_classes'] ?? 0) > 0) ({{ $metrics['past_classes'] }} classes) @endif</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        {{-- Upcoming classes --}}
        <div class="clay-card p-5 lg:col-span-2">
            <div class="mb-4 flex items-center justify-between border-b border-outline-variant pb-3">
                <h3 class="font-headline-md text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">videocam</span> Upcoming Classes
                </h3>
                <a href="{{ route('tutor.classes') }}" class="text-xs font-bold text-primary hover:underline">View all</a>
            </div>

            @if($hasUpcoming)
                <div class="space-y-3">
                    @foreach($bookings as $booking)
                        <div class="rounded-xl border border-outline-variant/60 bg-surface-container-low p-3.5">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-primary">1:1 Class</span>
                                    <h4 class="truncate font-semibold text-on-surface">{{ $booking->course->title ?? 'Live Session' }}</h4>
                                    <p class="text-xs text-on-surface-variant">
                                        {{ $booking->student->name ?? 'Student' }}
                                        @if($booking->scheduled_at) · <x-local-time :date="$booking->scheduled_at" format="datetime" /> @else · Schedule TBD @endif
                                    </p>
                                </div>
                                @if($booking->zoom_start_url)
                                    <a href="{{ $booking->zoom_start_url }}" target="_blank" class="btn-enroll shrink-0 py-2 px-5 text-xs">
                                        <span class="material-symbols-outlined text-sm">play_arrow</span> Start
                                    </a>
                                @else
                                    <span class="shrink-0 text-xs italic text-on-surface-variant">Link pending</span>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    @foreach($campBookings as $campBooking)
                        <div class="rounded-xl border border-warning-container/70 bg-warning-container/50 p-3.5">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-warning">Demo / Trial</span>
                                    <h4 class="truncate font-semibold text-on-surface">{{ $campBooking->camp_name ?? 'Free Trial / Consultation' }}</h4>
                                    <p class="text-xs text-on-surface-variant">
                                        {{ $campBooking->child_name ?? 'Student' }} · {{ $campBooking->parent_name }}
                                        @if($campBooking->scheduled_at) · <x-local-time :date="$campBooking->scheduled_at" format="datetime" /> @else · Schedule TBD @endif
                                    </p>
                                </div>
                                @if($campBooking->zoom_start_url)
                                    <a href="{{ $campBooking->zoom_start_url }}" target="_blank" class="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-warning px-5 py-2 text-xs font-extrabold text-white hover:bg-warning">
                                        <span class="material-symbols-outlined text-sm">play_arrow</span> Start
                                    </a>
                                @else
                                    <span class="shrink-0 text-xs italic text-on-surface-variant">Link pending</span>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    @foreach($sessions as $session)
                        <div class="rounded-xl border border-outline-variant/60 bg-surface-container-low p-3.5">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <h4 class="truncate font-semibold text-on-surface">{{ $session->course->title ?? 'Live Session' }}</h4>
                                    <p class="text-xs text-on-surface-variant">
                                        <x-local-time :date="$session->start_time" format="datetime" /> · {{ $session->duration_minutes }} mins
                                    </p>
                                </div>
                                <a href="{{ $session->start_url ?? '#' }}" target="_blank" class="btn-enroll shrink-0 py-2 px-5 text-xs">
                                    <span class="material-symbols-outlined text-sm">play_arrow</span> Start
                                </a>
                            </div>
                            @if($session->course && $session->course->units->count() > 0)
                                <div class="mt-3 rounded-lg border border-outline-variant/50 bg-white p-3">
                                    <h5 class="mb-1 flex items-center gap-1 text-[11px] font-bold uppercase tracking-wider text-primary">
                                        <span class="material-symbols-outlined text-sm">auto_stories</span> Today's Lesson Plan
                                    </h5>
                                    <p class="text-xs font-bold text-on-surface">{{ $session->course->units->first()->title }}</p>
                                    <p class="text-[11px] text-on-surface-variant">{{ $session->course->units->first()->description ?? 'Follow the syllabus builder materials.' }}</p>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-12 text-center">
                    <span class="material-symbols-outlined text-4xl text-on-surface-variant">event_busy</span>
                    <h4 class="mt-2 font-semibold text-on-surface">No upcoming classes</h4>
                    <p class="mt-1 text-xs text-on-surface-variant">Classes assigned by admin will appear here with your host link.</p>
                </div>
            @endif
        </div>

        {{-- Side column --}}
        <div class="space-y-4">
            <div class="clay-card border-2 border-warning/40 bg-gradient-to-br from-warning/10 to-primary/10 p-5">
                <div class="mb-2 flex items-center gap-2 text-sm font-bold text-warning">
                    <span class="material-symbols-outlined text-warning">rocket_launch</span> Upsell Opportunity
                </div>
                <p class="text-xs leading-relaxed text-on-surface-variant">
                    Classes reaching session 6 of 8 are prime for re-enrollment. Share your next-level course link with parents to earn upsell commissions.
                </p>
                <a href="{{ route('tutor.manage-courses') }}" class="btn-enroll mt-3 w-full py-2 text-xs">
                    <span class="material-symbols-outlined text-sm">share</span> Share next-level link
                </a>
            </div>

            <div class="clay-card p-5">
                <h3 class="mb-3 border-b border-outline-variant pb-3 font-headline-md text-on-surface">Quick Actions</h3>
                <div class="space-y-2">
                    <a href="{{ route('tutor.manage-courses') }}" class="flex items-center gap-3 rounded-xl bg-surface-container-low p-2.5 text-xs font-bold text-on-surface hover:bg-surface-container-high transition-colors">
                        <span class="material-symbols-outlined text-primary">menu_book</span> Manage classes &amp; syllabus
                    </a>
                    <a href="{{ route('tutor.availability') }}" class="flex items-center gap-3 rounded-xl bg-surface-container-low p-2.5 text-xs font-bold text-on-surface hover:bg-surface-container-high transition-colors">
                        <span class="material-symbols-outlined text-primary">schedule</span> Update availability
                    </a>
                    <a href="{{ route('tutor.messages.index') }}" class="flex items-center gap-3 rounded-xl bg-surface-container-low p-2.5 text-xs font-bold text-on-surface hover:bg-surface-container-high transition-colors">
                        <span class="material-symbols-outlined text-primary">chat</span> Message students
                    </a>
                    <a href="{{ route('tutor.announcements.index') }}" class="flex items-center gap-3 rounded-xl bg-surface-container-low p-2.5 text-xs font-bold text-on-surface hover:bg-surface-container-high transition-colors">
                        <span class="material-symbols-outlined text-primary">campaign</span> Post announcement
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
