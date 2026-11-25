@extends('layouts.dashboard')

@section('title', 'Student Dashboard')

@section('content')
<div class="p-6 md:p-8 space-y-8 max-w-[1600px] mx-auto">
    @if(!empty($trialEndsAt) && empty($hasPurchasedCourse))
    @php
        $trialSecondsLeft = max(0, (int) now()->diffInSeconds($trialEndsAt, false));
        $trialDaysLeft = intdiv($trialSecondsLeft, 86400);
        $trialRemainingLabel = $trialDaysLeft >= 1
            ? $trialDaysLeft . ' ' . \Illuminate\Support\Str::plural('day', $trialDaysLeft)
            : ($trialSecondsLeft > 0 ? 'less than a day' : 'no time');
        $trialEndsLabel = $trialEndsAt->format('d M Y');
    @endphp
    <div id="trial-timer-banner" class="rounded-[1.5rem] border border-warning-container bg-gradient-to-r from-warning to-warning p-5 md:p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4"
         data-ends-at="{{ $trialEndsAt->toIso8601String() }}">
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-warning/80 mb-1">Trial dashboard access</p>
            <h2 class="text-lg md:text-xl font-bold text-warning">Your free dashboard closes on {{ $trialEndsLabel }}</h2>
            <p class="text-sm text-warning/80 mt-1"><strong>{{ $trialRemainingLabel }}</strong> of access remaining. Enroll in a course anytime to keep full access.</p>
        </div>
        <div class="flex items-center gap-3 bg-white/80 rounded-2xl px-5 py-3 border border-warning-container shadow-sm min-w-[220px] justify-center">
            <span class="material-symbols-outlined text-warning text-3xl">timer</span>
            <div class="text-center md:text-left">
                <p class="text-[10px] font-bold uppercase tracking-wider text-warning/70">Time remaining</p>
                <p id="trial-countdown" class="font-mono text-xl font-bold text-warning tabular-nums">--d --h --m --s</p>
            </div>
        </div>
    </div>
    <script>
        (function () {
            const banner = document.getElementById('trial-timer-banner');
            const label = document.getElementById('trial-countdown');
            if (!banner || !label) return;
            const endsAt = new Date(banner.dataset.endsAt).getTime();
            function tick() {
                const diff = endsAt - Date.now();
                if (diff <= 0) {
                    label.textContent = 'Expired';
                    window.location.href = @json(route('student.trial_expired'));
                    return;
                }
                const s = Math.floor(diff / 1000);
                const d = Math.floor(s / 86400);
                const h = Math.floor((s % 86400) / 3600);
                const m = Math.floor((s % 3600) / 60);
                const sec = s % 60;
                label.textContent = d + 'd ' + String(h).padStart(2, '0') + 'h ' + String(m).padStart(2, '0') + 'm ' + String(sec).padStart(2, '0') + 's';
            }
            tick();
            setInterval(tick, 1000);
        })();
    </script>
    @endif

    <!-- Welcome Header & Leaderboard Score Summary -->
    <div class="grid grid-cols-1 lg:grid-cols-1 md:grid-cols-3 gap-6">
        <div class="lg:col-span-2 flex justify-between items-center p-5 md:p-8 rounded-[2rem] clay-card text-primary relative overflow-hidden" style="background: var(--brand-gradient);">
                @php $pendingCount = isset($assignments) ? $assignments->filter(fn($a)=>$a->submissions->isEmpty())->count() : 0; @endphp
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-primary/10 text-primary rounded-full text-xs font-bold uppercase tracking-wider mb-3">
                    Level {{ $stats['level'] }} Explorer
                </span>
                <h1 class="font-headline-lg text-headline-lg font-bold mb-2">Welcome back, {{ auth()->user()->name ?? 'Explorer' }}! 🚀</h1>
                <p class="font-body-lg text-body-lg opacity-90 font-bold">Ready for your next learning adventure today?</p>
                @if($pendingCount > 0)
                <a href="{{ route('student.assignments') }}" class="inline-flex items-center gap-2 mt-3 px-4 py-2 bg-white/90 text-primary rounded-xl font-bold text-sm hover:bg-white transition-colors shadow-sm">
                    <span class="material-symbols-outlined text-lg">assignment</span>
                    {{ $pendingCount }} Pending Post class Activity
                    <span class="material-symbols-outlined text-lg">arrow_forward</span>
                </a>
                @endif
            </div>
            <div class="hidden md:block">
                <span class="material-symbols-outlined text-8xl opacity-80">workspace_premium</span>
            </div>
        </div>

        <!-- Leaderboard XP Card -->
        <div class="bg-gradient-to-br from-tertiary-container via-warning to-warning p-5 md:p-8 rounded-[2rem] clay-card border border-tertiary/20 flex flex-col justify-between">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-on-tertiary-container/80">Leaderboard Score</span>
                    <h2 class="text-4xl font-headline-xl font-bold text-on-tertiary-container mt-1">{{ number_format($stats['xp']) }} <span class="text-lg">XP</span></h2>
                </div>
                <div class="w-14 h-14 bg-white/80 rounded-2xl flex items-center justify-center shadow-sm">
                    <span class="text-3xl">🏆</span>
                </div>
            </div>
            <div class="flex items-center justify-between pt-4 border-t border-on-tertiary-container/10">
                <span class="text-sm font-bold text-on-tertiary-container">Global Rank: #{{ $stats['rank'] }}</span>
                <span class="text-xs font-bold bg-white/90 text-on-tertiary-container px-3 py-1 rounded-full shadow-xs">{{ $stats['tier'] }}</span>
            </div>
        </div>
    </div>

    <!-- Quick Stat Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-surface-container-low p-6 rounded-2xl border border-surface-container-high flex flex-col items-center justify-center text-center">
            <div class="w-16 h-16 bg-primary-fixed rounded-full flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-primary text-3xl">school</span>
            </div>
            <h3 class="font-headline-md text-headline-md font-bold">{{ $stats['enrolled'] }}</h3>
            <p class="text-on-surface-variant text-sm font-medium">Enrolled Courses</p>
        </div>

        <div class="bg-surface-container-low p-6 rounded-2xl border border-surface-container-high flex flex-col items-center justify-center text-center">
            <div class="w-16 h-16 bg-secondary-fixed rounded-full flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-secondary text-3xl">play_circle</span>
            </div>
            <h3 class="font-headline-md text-headline-md font-bold">{{ $stats['in_progress'] }}</h3>
            <p class="text-on-surface-variant text-sm font-medium">In Progress</p>
        </div>

        <div class="bg-surface-container-low p-6 rounded-2xl border border-surface-container-high flex flex-col items-center justify-center text-center">
            <div class="w-16 h-16 bg-tertiary-fixed rounded-full flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-tertiary text-3xl">task_alt</span>
            </div>
            <h3 class="font-headline-md text-headline-md font-bold">{{ $stats['completed'] }}</h3>
            <p class="text-on-surface-variant text-sm font-medium">Completed</p>
        </div>

        <div class="bg-surface-container-low p-6 rounded-2xl border border-surface-container-high flex flex-col items-center justify-center text-center">
            <div class="w-16 h-16 bg-primary-fixed rounded-full flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-primary text-3xl">trending_up</span>
            </div>
            <h3 class="font-headline-md text-headline-md font-bold">{{ round($stats['avg_progress']) }}%</h3>
            <p class="text-on-surface-variant text-sm font-medium">Avg Progress</p>
        </div>

        <a href="{{ route('student.assignments') }}" class="bg-surface-container-low p-6 rounded-2xl border border-surface-container-high flex flex-col items-center justify-center text-center hover:border-primary/40 hover:bg-white transition-colors">
            <div class="w-16 h-16 bg-tertiary-fixed rounded-full flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-tertiary text-3xl">assignment</span>
            </div>
            <h3 class="font-headline-md text-headline-md font-bold">Post class Activity</h3>
            <p class="text-on-surface-variant text-sm font-medium">View & Submit</p>
        </a>
    </div>

    <!-- Action Required -->
    <div class="bg-gradient-to-r from-primary/5 to-secondary/5 p-5 md:p-6 rounded-[2rem] border border-primary/20">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center">
                    <span class="material-symbols-outlined text-primary text-2xl">pending_actions</span>
                </div>
                <div>
                    <h2 class="font-headline-md font-bold text-on-surface">Action Required</h2>
                    <p class="text-sm text-on-surface-variant">Complete pending Post class Activities and upcoming classes</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('student.assignments') }}" class="px-4 py-2 bg-white rounded-xl text-sm font-bold text-primary border border-primary/20 hover:bg-primary/5 transition-colors inline-flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg">assignment</span> Post class Activity
                    @php $pendingCount = isset($assignments) ? $assignments->filter(fn($a)=>$a->submissions->isEmpty())->count() : 0; @endphp
                    @if($pendingCount > 0)
                    <span class="px-2 py-0.5 bg-primary text-white rounded-full text-xs">{{ $pendingCount }}</span>
                    @endif
                </a>
                <a href="{{ route('student.courses') }}" class="px-4 py-2 bg-white rounded-xl text-sm font-bold text-secondary border border-secondary/20 hover:bg-secondary/5 transition-colors inline-flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg">school</span> My Courses
                </a>
                <a href="{{ route('student.bookings') }}" class="px-4 py-2 bg-white rounded-xl text-sm font-bold text-tertiary border border-tertiary/20 hover:bg-tertiary/5 transition-colors inline-flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg">event</span> Bookings
                </a>
            </div>
        </div>
    </div>

    <!-- Upcoming Sessions & Class Joining Note -->
    
    <div class="bg-white p-5 md:p-8 rounded-[2rem] border border-surface-container-high shadow-sm">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="font-headline-md text-headline-md font-bold">Upcoming Live Sessions</h2>
                <p class="text-sm text-on-surface-variant">Class joining link & instructions</p>
            </div>
            <a href="{{ route('student.bookings') }}" class="text-primary font-bold text-sm hover:underline">View All Bookings</a>
        </div>
        <div class="space-y-4">
            @if($upcomingSessions->isEmpty() && $demoSessions->isEmpty())
                <p class="text-on-surface-variant text-center py-6 bg-surface-container-low rounded-2xl">No upcoming live sessions, free consultations, or free trial classes scheduled.</p>
            @else
                @foreach($upcomingSessions as $session)
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 bg-surface-container-low hover:bg-surface-container rounded-2xl transition-colors border border-outline-variant/30">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-primary/10 text-primary rounded-2xl flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-2xl">videocam</span>
                            </div>
                            <div>
                                <h4 class="font-bold text-on-surface text-base">{{ $session->course->title ?? $session->camp_name ?? 'Live Interactive Session' }}</h4>
                                <p class="text-xs text-on-surface-variant mt-0.5">
                                    Scheduled: 
                                    @if($session->scheduled_at)
                                        <x-local-time :date="$session->scheduled_at" format="datetime" />
                                    @else
                                        Upcoming
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            @php $effectiveDate = $session->scheduled_at ?? $session->created_at; @endphp
                            @if(isset($session->status) && $session->status === 'completed')
                                <span class="btn-secondary btn-sm inline-flex items-center gap-2 opacity-70 cursor-not-allowed">
                                    <span class="material-symbols-outlined text-sm">event_busy</span> Session Ended
                                </span>
                            @elseif($effectiveDate && \Carbon\Carbon::parse($effectiveDate)->addMinutes(60)->isPast())
                                <span class="btn-secondary btn-sm inline-flex items-center gap-2 opacity-70 cursor-not-allowed">
                                    <span class="material-symbols-outlined text-sm">event_busy</span> Session Ended
                                </span>
                            @elseif($session->zoom_join_url && $effectiveDate && \Carbon\Carbon::parse($effectiveDate)->subHours(4)->isPast())
                                <x-join-class-action
                                    :url="$session->zoom_join_url"
                                    :provider="$session->meeting_provider ?? null"
                                    :room-id="$session->meeting_room_id ?? null"
                                    :passcode="$session->zoom_passcode ?? null"
                                    button-class="btn-primary btn-sm inline-flex items-center gap-2 !bg-success hover:!bg-success border-none mt-1"
                                    :icon="true"
                                />
                            @elseif($session->zoom_join_url && $effectiveDate)
                                <span class="btn-secondary btn-sm inline-flex items-center gap-2 opacity-70 cursor-not-allowed" title="Link will be available 4 hours before class">
                                    <span class="material-symbols-outlined text-sm">schedule</span> Link Available Soon
                                </span>
                            @elseif($session->zoom_join_url)
                                <x-join-class-action
                                    :url="$session->zoom_join_url"
                                    :provider="$session->meeting_provider ?? null"
                                    :room-id="$session->meeting_room_id ?? null"
                                    :passcode="$session->zoom_passcode ?? null"
                                    button-class="btn-primary btn-sm inline-flex items-center gap-2 !bg-success hover:!bg-success border-none mt-1"
                                    :icon="true"
                                />
                            @else
                                <span class="btn-secondary btn-sm inline-flex items-center gap-2 opacity-70 cursor-not-allowed">
                                    <span class="material-symbols-outlined text-sm">schedule</span> Link Pending
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
                
                @foreach($demoSessions as $session)
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 bg-surface-container-low hover:bg-surface-container rounded-2xl transition-colors border border-outline-variant/30">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-secondary/10 text-secondary rounded-2xl flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-2xl">star</span>
                            </div>
                            <div>
                                <h4 class="font-bold text-on-surface text-base">{{ str_starts_with($session->camp_name ?? '', 'Consultation') ? 'Free Consultation' : 'Free Trial' }}: {{ str_replace(['Free Trial - ', 'Consultation - '], '', $session->camp_name) }}</h4>
                                <p class="text-xs text-on-surface-variant mt-0.5">
                                    @if($session->scheduled_at)
                                        Scheduled: <x-local-time :date="$session->scheduled_at" format="datetime" />
                                    @else
                                        <span class="text-warning font-medium">⏳ Awaiting schedule — our team will contact you shortly</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            @if(!$session->scheduled_at)
                                <span class="btn-secondary btn-sm inline-flex items-center gap-2 opacity-80">
                                    <span class="material-symbols-outlined text-sm">pending</span> Scheduling Soon
                                </span>
                            @elseif($session->scheduled_at && \Carbon\Carbon::parse($session->scheduled_at)->addMinutes(60)->isPast())
                                <span class="btn-secondary btn-sm inline-flex items-center gap-2 opacity-70 cursor-not-allowed">
                                    <span class="material-symbols-outlined text-sm">event_busy</span> Session Ended
                                </span>
                            @elseif($session->zoom_join_url && \Carbon\Carbon::parse($session->scheduled_at)->subHours(4)->isPast())
                                <x-join-class-action
                                    :url="$session->zoom_join_url"
                                    :provider="$session->meeting_provider ?? null"
                                    :room-id="$session->meeting_room_id ?? null"
                                    :passcode="$session->zoom_passcode ?? null"
                                    button-class="btn-primary btn-sm inline-flex items-center gap-2 !bg-success hover:!bg-success border-none mt-1"
                                    :icon="true"
                                />
                            @elseif($session->zoom_join_url)
                                <span class="btn-secondary btn-sm inline-flex items-center gap-2 opacity-70 cursor-not-allowed" title="Link will be available 4 hours before class">
                                    <span class="material-symbols-outlined text-sm">schedule</span> Link Available Soon
                                </span>
                            @else
                                <span class="btn-secondary btn-sm inline-flex items-center gap-2 opacity-70 cursor-not-allowed">
                                    <span class="material-symbols-outlined text-sm">schedule</span> Link Pending
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <!-- Leaderboard Score Table & Worksheets Practice -->
    <div class="grid grid-cols-1 lg:grid-cols-1 md:grid-cols-2 gap-5 md:p-8">
        <!-- Leaderboard Scores -->
        <div class="bg-white p-5 md:p-8 rounded-[2rem] border border-surface-container-high shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="font-headline-md text-headline-md font-bold flex items-center gap-2">
                            <span>🏆</span> Global Leaderboard
                        </h2>
                        <p class="text-sm text-on-surface-variant">Top student performers this week</p>
                    </div>
                    <span class="text-xs font-bold text-primary bg-primary/10 px-3 py-1 rounded-full">Updated Live</span>
                </div>
                <div class="space-y-3">
                    @foreach($leaderboard as $student)
                        <div class="flex items-center justify-between p-3.5 rounded-2xl transition-colors {{ isset($student['is_current']) ? 'bg-warning-container border-2 border-warning-container' : 'bg-surface-container-low' }}">
                            <div class="flex items-center gap-3">
                                <span class="font-bold text-sm text-on-surface-variant w-6 text-center">#{{ $student['rank'] }}</span>
                                <div class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center font-bold text-sm shrink-0">
                                    {{ $student['avatar'] }}
                                </div>
                                <div>
                                    <h4 class="font-bold text-sm text-on-surface flex items-center gap-2">
                                        {{ $student['name'] }}
                                        @if(isset($student['is_current']))
                                            <span class="text-[10px] bg-primary text-white px-2 py-0.5 rounded-full font-bold">You</span>
                                        @endif
                                    </h4>
                                    <p class="text-xs text-on-surface-variant">{{ $student['badge'] }}</p>
                                </div>
                            </div>
                            <span class="font-bold text-primary text-base">{{ number_format($student['xp']) }} XP</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Worksheets & Practice PDFs -->
        <div class="bg-white p-5 md:p-8 rounded-[2rem] border border-surface-container-high shadow-sm">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="font-headline-md text-headline-md font-bold flex items-center gap-2">
                        <span class="material-symbols-outlined text-secondary text-3xl">picture_as_pdf</span> PDFs &amp; Worksheets Practice
                    </h2>
                    <p class="text-sm text-on-surface-variant">View practice sheets &amp; study guides</p>
                </div>
            </div>
            <div class="space-y-4">
                @foreach($worksheets as $sheet)
                    <div class="flex items-center justify-between p-4 bg-surface-container-low hover:bg-surface-container rounded-2xl transition-colors border border-outline-variant/20">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-primary-container text-on-primary-container flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-xl">description</span>
                            </div>
                            <div>
                                <h4 class="font-bold text-sm text-on-surface">{{ $sheet->title }}</h4>
                                <span class="text-xs text-on-surface-variant">{{ $sheet->course->title ?? 'General Practice' }}</span>
                            </div>
                        </div>
                        <a href="{{ $sheet->content_url ?? '#' }}" target="_blank" rel="noopener" class="w-9 h-9 rounded-full bg-white border border-outline-variant flex items-center justify-center text-primary hover:bg-primary hover:text-white transition-colors" title="View PDF">
                            <span class="material-symbols-outlined text-lg">visibility</span>
                        </a>
                    </div>
                @endforeach
                
                @foreach($assignments as $assignment)
                    <div class="flex items-center justify-between p-4 bg-surface-container-low hover:bg-surface-container rounded-2xl transition-colors border border-outline-variant/20">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-tertiary-container text-on-tertiary-container flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-xl">assignment</span>
                            </div>
                            <div>
                                <h4 class="font-bold text-sm text-on-surface">{{ $assignment->title }} (Assignment)</h4>
                                <span class="text-xs text-on-surface-variant">{{ $assignment->course->title ?? 'General Practice' }}</span>
                            </div>
                        </div>
                        <a href="{{ $assignment->file_path ? route('secure.asset', ['path' => $assignment->file_path]) : '#' }}" target="_blank" rel="noopener" class="w-9 h-9 rounded-full bg-white border border-outline-variant flex items-center justify-center text-tertiary hover:bg-tertiary hover:text-white transition-colors" title="View Assignment">
                            <span class="material-symbols-outlined text-lg">visibility</span>
                        </a>
                    </div>
                @endforeach

                @if($worksheets->isEmpty() && $assignments->isEmpty())
                    <p class="text-sm text-on-surface-variant py-4 text-center">Enroll in courses to get practice sheets.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Class Rewards & Badges -->
    @if($rewardStats['count'] > 0)
    <div class="bg-white p-5 md:p-8 rounded-[2rem] border border-surface-container-high shadow-sm">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="font-headline-md text-headline-md font-bold flex items-center gap-2">
                    <span class="material-symbols-outlined text-warning text-3xl">celebration</span> Class Rewards &amp; Badges
                </h2>
                <p class="text-sm text-on-surface-variant">Compliments and badges your tutor gave you in class</p>
            </div>
            <div class="text-right">
                <p class="text-2xl font-bold text-tertiary">+{{ number_format($rewardStats['total']) }} XP</p>
                <p class="text-xs text-on-surface-variant">{{ $rewardStats['badges'] }} badge{{ $rewardStats['badges'] === 1 ? '' : 's' }} earned</p>
            </div>
        </div>

        @if($badges->count())
            <div class="flex flex-wrap gap-3 mb-6">
                @foreach($badges as $badge)
                    <div class="flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-warning-container border border-warning-container" title="{{ $badge->description }}">
                        <span class="material-symbols-outlined text-warning">{{ $badge->badge_icon }}</span>
                        <span class="font-bold text-sm text-warning">{{ $badge->title }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="space-y-3">
            @foreach($rewards as $reward)
                <div class="p-4 bg-surface-container-low rounded-2xl border border-outline-variant/20 flex items-start justify-between gap-4">
                    <div class="text-sm">
                        <p class="font-bold text-on-surface flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-lg">person</span>
                            {{ $reward->tutor->name ?? 'Your tutor' }}
                            @if($reward->course)<span class="text-xs font-normal text-on-surface-variant">· {{ $reward->course->title }}</span>@endif
                        </p>
                        @if($reward->compliments)
                            <div class="flex flex-wrap gap-1.5 mt-2">
                                @foreach($reward->compliments as $c)
                                    <span class="text-[11px] bg-primary/10 text-primary font-bold px-2.5 py-1 rounded-full">{{ $c['label'] ?? 'Compliment' }} @if(!empty($c['xp'])) (+{{ $c['xp'] }} XP) @endif</span>
                                @endforeach
                            </div>
                        @endif
                        @if($reward->level_up_tip)
                            <p class="text-xs text-on-surface-variant mt-2 flex items-start gap-1"><span class="material-symbols-outlined text-sm text-tertiary">lightbulb</span> {{ $reward->level_up_tip }}</p>
                        @endif
                        <p class="text-[11px] text-on-surface-variant mt-1.5"><x-local-time :date="$reward->awarded_at" format="datetime-short" /></p>
                    </div>
                    <div class="text-right whitespace-nowrap">
                        <span class="font-bold text-tertiary">+{{ $reward->xp_amount }} XP</span>
                        @if($reward->badge)
                            <p class="text-xs font-bold text-warning flex items-center gap-1 justify-end mt-1">
                                <span class="material-symbols-outlined text-sm">{{ $reward->badge->badge_icon }}</span> {{ $reward->badge->title }}
                            </p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Quizzes & Interactive Practice -->
    <div class="bg-white p-5 md:p-8 rounded-[2rem] border border-surface-container-high shadow-sm">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="font-headline-md text-headline-md font-bold flex items-center gap-2">
                    <span class="material-symbols-outlined text-tertiary text-3xl">quiz</span> Practice Quizzes
                </h2>
                <p class="text-sm text-on-surface-variant">Test your knowledge and earn extra XP points</p>
            </div>
            <a href="{{ route('student.quizzes') }}" class="text-primary font-bold text-sm hover:underline">View All Quizzes</a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @forelse($quizzes as $quiz)
                @php
                    $quizPassed = $quiz->attempts->contains(fn ($a) => $a->passed);
                    $quizBest = $quiz->attempts->max('score');
                    $quizAttempted = $quiz->attempts->isNotEmpty();
                @endphp
                <div class="p-5 bg-surface-container-low rounded-2xl border border-surface-container-high flex flex-col justify-between gap-3">
                    <div>
                        <span class="px-3 py-1 bg-tertiary-container text-on-tertiary-container rounded-full text-xs font-bold inline-block mb-3">Quiz</span>
                        <h4 class="font-bold text-base text-on-surface mb-2">{{ $quiz->title }}</h4>
                        <p class="text-xs text-on-surface-variant mb-2">{{ $quiz->course->title ?? 'General Practice' }}</p>
                        @if($quizAttempted)
                            <p class="text-xs font-bold {{ $quizPassed ? 'text-on-tertiary-container' : 'text-warning' }} mb-2">
                                {{ $quizPassed ? 'Passed' : 'Needs Retry' }} · {{ $quizBest }}%
                            </p>
                        @endif
                    </div>
                    <div class="flex flex-col gap-2">
                        @if($quizAttempted)
                            <a href="{{ route('student.quizzes.results', $quiz) }}" class="btn-secondary btn-sm w-full text-center">View Answers</a>
                            @unless($quizPassed)
                                <a href="{{ route('student.quizzes.take', $quiz) }}" class="btn-primary btn-sm w-full text-center">Retry Quiz →</a>
                            @endunless
                        @else
                            <a href="{{ route('student.quizzes.take', $quiz) }}" class="btn-primary btn-sm w-full text-center">Take Quiz →</a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-4 text-center py-6 bg-surface-container-low rounded-2xl">
                    <span class="material-symbols-outlined text-4xl text-on-surface-variant mb-2">fact_check</span>
                    <p class="text-on-surface-variant font-medium text-sm">Practice quizzes ready for your course modules.</p>
                    <a href="{{ route('student.quizzes') }}" class="btn-primary btn-sm mt-3 inline-block">Explore Quizzes</a>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Tips and Tricks Videos -->
    <div class="bg-white p-5 md:p-8 rounded-[2rem] border border-surface-container-high shadow-sm">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="font-headline-md text-headline-md font-bold flex items-center gap-2">
                    <span class="material-symbols-outlined text-error text-3xl">smart_display</span> Tips &amp; Tricks Videos
                </h2>
                <p class="text-sm text-on-surface-variant">Short micro-lessons &amp; expert learning hacks</p>
            </div>
        </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($tipsFolders as $folder)
                <div class="bg-surface-container-low rounded-2xl p-5 border border-outline-variant/30 group hover:shadow-md transition-all">
                    <div class="flex items-center gap-3 mb-4 pb-4 border-b border-outline-variant/30">
                        <div class="w-10 h-10 bg-warning-container text-warning rounded-xl flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined">folder</span>
                        </div>
                        <h4 class="font-bold text-on-surface">{{ $folder->name }}</h4>
                    </div>
                    <div class="space-y-3 max-h-48 overflow-y-auto pr-2 custom-scrollbar">
                        @forelse($folder->tips as $tip)
                            <a href="javascript:void(0)" onclick="openSecureViewer('{{ addslashes($tip->title) }}', '{{ route('tips.stream', $tip->id) }}', '{{ $tip->type }}')" class="flex items-start gap-3 p-2 hover:bg-white rounded-xl transition-colors group/item">
                                <span class="material-symbols-outlined {{ $tip->type === 'video' ? 'text-error' : 'text-primary' }} text-xl shrink-0 mt-0.5">
                                    {{ $tip->type === 'video' ? 'play_circle' : 'description' }}
                                </span>
                                <div>
                                    <p class="font-medium text-sm text-on-surface group-hover/item:text-primary transition-colors line-clamp-2">{{ $tip->title }}</p>
                                    <span class="text-[10px] text-on-surface-variant uppercase font-bold tracking-wider">{{ $tip->type }}</span>
                                </div>
                            </a>
                        @empty
                            <p class="text-xs text-on-surface-variant italic">Empty folder.</p>
                        @endforelse
                    </div>
                </div>
            @empty
                @if(count($looseTips) === 0)
                    <div class="col-span-3 text-center py-8 bg-surface-container-low rounded-2xl border border-outline-variant/30">
                        <span class="material-symbols-outlined text-4xl text-on-surface-variant mb-2 opacity-50">inventory_2</span>
                        <p class="text-on-surface-variant font-medium text-sm">No tips & tricks have been assigned to you yet.</p>
                    </div>
                @endif
            @endforelse

            @if(count($looseTips) > 0)
                <div class="bg-surface-container-low rounded-2xl p-5 border border-outline-variant/30 group hover:shadow-md transition-all md:col-span-2 lg:col-span-3">
                    <h4 class="font-bold text-on-surface mb-4 pb-4 border-b border-outline-variant/30">Other Tips & Hacks</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                        @foreach($looseTips as $tip)
                            <a href="javascript:void(0)" onclick="openSecureViewer('{{ addslashes($tip->title) }}', '{{ route('tips.stream', $tip->id) }}', '{{ $tip->type }}')" class="flex items-start gap-3 p-3 bg-white border border-outline-variant/30 hover:border-primary/30 rounded-xl transition-all group/item">
                                <span class="material-symbols-outlined {{ $tip->type === 'video' ? 'text-error' : 'text-primary' }} text-2xl shrink-0">
                                    {{ $tip->type === 'video' ? 'smart_display' : 'description' }}
                                </span>
                                <div>
                                    <p class="font-medium text-sm text-on-surface group-hover/item:text-primary transition-colors line-clamp-2">{{ $tip->title }}</p>
                                    <span class="text-[10px] text-on-surface-variant uppercase font-bold tracking-wider">{{ $tip->type }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

    </div>

    <!-- My Enrolled Courses List -->
    <div class="bg-white p-5 md:p-8 rounded-[2rem] border border-surface-container-high shadow-sm">
        <div class="flex justify-between items-center mb-6">
            <h2 class="font-headline-md text-headline-md font-bold">My Courses</h2>
            <a href="{{ route('student.courses') }}" class="text-primary font-bold text-sm hover:underline">View All</a>
        </div>
        <div class="space-y-4">
            @foreach($enrollments->take(5) as $enrollment)
                @php 
                    $is1to1 = $enrollment->course->format === '1to1';
                    $booking = $is1to1 ? $bookings->get($enrollment->course->id) : null;
                @endphp
                <div class="flex items-center gap-4 p-4 hover:bg-surface-container-low rounded-xl transition-colors border border-transparent hover:border-surface-container-high">
                    <div class="w-12 h-12 {{ $is1to1 ? 'bg-tertiary-fixed text-tertiary' : 'bg-primary-fixed text-primary' }} rounded-xl flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined">{{ $is1to1 ? 'person_play' : 'menu_book' }}</span>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <h4 class="font-bold text-on-surface">{{ $enrollment->course->title ?? 'Course' }}</h4>
                            @if($is1to1)
                                <span class="text-[9px] bg-tertiary/10 text-tertiary px-1.5 py-0.5 rounded uppercase font-bold tracking-wider">1:1</span>
                            @endif
                        </div>
                        
                        @if($is1to1)
                            @if($booking)
                                @if(!$booking->tutor_id)
                                    <div class="text-xs text-warning font-bold mt-1.5 bg-warning-container px-2 py-0.5 rounded inline-flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">schedule</span> Not yet allotted tutor</div>
                                @elseif(!$booking->scheduled_at)
                                    <div class="text-xs text-on-secondary-container font-bold mt-1.5 bg-secondary-container px-2 py-0.5 rounded inline-flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">calendar_month</span> Not yet scheduled</div>
                                @else
                                    <div class="text-xs text-on-tertiary-container font-bold mt-1.5 bg-tertiary-fixed px-2 py-0.5 rounded inline-flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">event</span> Next: <x-local-time :date="$booking->scheduled_at" format="datetime-short" /></div>
                                @endif
                            @else
                                <div class="text-xs text-on-surface font-bold mt-1.5 bg-surface-container-low px-2 py-0.5 rounded inline-flex items-center gap-1">Processing booking...</div>
                            @endif
                        @else
                            <div class="flex items-center gap-2 mt-1.5">
                                <div class="flex-1 max-w-xs bg-surface-container-high rounded-full h-1.5">
                                    <div class="bg-primary h-1.5 rounded-full" style="width: {{ $enrollment->progress ?? 0 }}%"></div>
                                </div>
                                <span class="text-xs font-medium text-on-surface-variant">{{ round($enrollment->progress ?? 0) }}%</span>
                            </div>
                        @endif
                    </div>
                    @if($is1to1)
                        <div class="flex flex-wrap items-center gap-2 shrink-0 justify-end">
                            <x-enrollment-certificate-button :enrollment="$enrollment" :course="$enrollment->course" />
                        @if($booking && $booking->zoom_join_url && $booking->scheduled_at && \Carbon\Carbon::parse($booking->scheduled_at)->subHours(4)->isPast())
                            <x-join-class-action
                                :url="$booking->zoom_join_url"
                                :provider="$booking->meeting_provider"
                                :room-id="$booking->meeting_room_id"
                                :passcode="$booking->zoom_passcode"
                                button-class="btn-primary btn-sm !bg-success hover:!bg-success border-none shrink-0 inline-flex items-center gap-1 mt-1"
                                :icon="true"
                            />
                        @elseif($booking && $booking->zoom_join_url && $booking->scheduled_at && \Carbon\Carbon::parse($booking->scheduled_at)->isFuture())
                            <span class="btn-secondary btn-sm shrink-0 cursor-not-allowed opacity-70" title="Link will be available 4 hours before class">Link Soon</span>
                        @else
                            @php
                                $existingBookingsCount = \App\Models\Booking::where('student_id', auth()->id())->where('course_id', $enrollment->course->id)->count();
                                $totalClasses = $enrollment->course->number_of_classes ?? 8;
                            @endphp
                            
                            @if($existingBookingsCount < $totalClasses)
                                <a href="{{ route('student.courses.schedule-wizard', $enrollment->course) }}" class="btn-secondary btn-sm shrink-0">Schedule</a>
                            @else
                                <a href="{{ route('student.bookings') }}" class="btn-secondary btn-sm shrink-0">View Bookings</a>
                            @endif
                        @endif
                        </div>
                    @else
                        <div class="flex flex-wrap items-center gap-2 shrink-0 justify-end">
                            <x-enrollment-certificate-button :enrollment="$enrollment" :course="$enrollment->course" />
                            <a href="{{ route('student.courses.player', $enrollment->course) }}" class="btn-primary btn-sm shrink-0">Continue</a>
                        </div>
                    @endif
                </div>
            @endforeach
            
            @foreach($demoSessions as $session)
                <div class="flex items-center gap-4 p-4 hover:bg-surface-container-low rounded-xl transition-colors">
                    <div class="w-12 h-12 bg-secondary-fixed rounded-xl flex items-center justify-center">
                        <span class="material-symbols-outlined text-secondary">star</span>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-bold">{{ str_starts_with($session->camp_name ?? '', 'Consultation') ? 'Free Consultation' : 'Free Trial' }}: {{ str_replace(['Free Trial - ', 'Consultation - '], '', $session->camp_name) }}</h4>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs text-secondary font-bold">Trial Class</span>
                        </div>
                    </div>
                    @if($session->zoom_join_url && $session->scheduled_at && \Carbon\Carbon::parse($session->scheduled_at)->subHours(4)->isPast())
                        <x-join-class-action
                            :url="$session->zoom_join_url"
                            :provider="$session->meeting_provider ?? null"
                            :room-id="$session->meeting_room_id ?? null"
                            :passcode="$session->zoom_passcode ?? null"
                            button-class="btn-primary btn-sm !bg-success hover:!bg-success border-none mt-1"
                        />
                    @elseif($session->zoom_join_url && $session->scheduled_at)
                        <span class="btn-secondary btn-sm opacity-70 cursor-not-allowed" title="Link will be available 4 hours before class">Link Soon</span>
                    @else
                        <span class="btn-secondary btn-sm opacity-70 cursor-not-allowed">Link Pending</span>
                    @endif
                </div>
            @endforeach

            @if($enrollments->isEmpty() && $demoSessions->isEmpty())
                <p class="text-on-surface-variant text-center py-6">No courses enrolled yet. <a href="{{ route('courses.index') }}" class="text-primary font-bold">Browse courses</a></p>
            @endif
        </div>
    </div>
</div>
<x-secure-viewer />
@endsection
