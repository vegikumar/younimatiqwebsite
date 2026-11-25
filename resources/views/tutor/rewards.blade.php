@extends('layouts.dashboard')

@section('title', 'Class Rewards')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <span class="px-3 py-1 bg-primary-container text-on-primary-container rounded-full text-xs font-bold uppercase tracking-wider mb-2 inline-block">
                    Class Rewards
                </span>
                <h1 class="font-headline-xl text-headline-xl text-on-surface mb-2">Celebrate Your Learners</h1>
                <p class="font-body-lg text-body-lg text-on-surface-variant">Award XP compliments, level-up tips and badges during trial or course classes. Students see them instantly on their dashboard.</p>
            </div>
            @if($students->count())
                <button type="button" class="btn-enroll py-3 px-5 text-sm font-bold flex items-center gap-1"
                    @click="$dispatch('open-reward', { id: {{ $students->first()->id }}, name: @js($students->first()->name) })">
                    <span class="material-symbols-outlined text-sm">celebration</span> Celebrate a Student
                </button>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
        <div class="clay-card p-6 bg-white border-l-4 border-tertiary">
            <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">XP Awarded</p>
            <p class="text-3xl font-bold text-tertiary mt-1">{{ number_format($stats['xp_awarded']) }}</p>
        </div>
        <div class="clay-card p-6 bg-white border-l-4 border-primary">
            <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Badges Awarded</p>
            <p class="text-3xl font-bold text-primary mt-1">{{ number_format($stats['badges_awarded']) }}</p>
        </div>
        <div class="clay-card p-6 bg-white border-l-4 border-secondary">
            <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Students Celebrated</p>
            <p class="text-3xl font-bold text-secondary mt-1">{{ number_format($stats['students_celebrated']) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Reward history -->
        <div class="lg:col-span-2 clay-card p-6 bg-white">
            <h2 class="font-headline-sm text-lg font-bold mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">history</span> Recent Rewards
            </h2>

            <div class="space-y-3">
                @forelse($rewards as $reward)
                    <div class="p-4 border border-outline-variant/20 rounded-2xl bg-surface-container-low/30 flex items-start justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-full bg-primary text-white font-bold flex items-center justify-center text-sm shrink-0">
                                {{ substr($reward->student->name ?? 'S', 0, 1) }}
                            </div>
                            <div class="text-sm">
                                <p class="font-bold text-on-surface">
                                    {{ $reward->student->name ?? 'Student' }}
                                    @if($reward->is_trial) <span class="text-[10px] font-bold uppercase bg-secondary/20 text-secondary px-2 py-0.5 rounded-full ml-1">Trial</span> @endif
                                </p>
                                <p class="text-xs text-on-surface-variant">{{ $reward->course->title ?? 'General' }} · <x-local-time :date="$reward->awarded_at" format="datetime-short" /></p>

                                @if($reward->badge)
                                    <p class="text-xs font-bold text-primary mt-1 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-sm">{{ $reward->badge->badge_icon }}</span> {{ $reward->badge->title }}
                                    </p>
                                @endif

                                @if($reward->compliments)
                                    <div class="flex flex-wrap gap-1 mt-1.5">
                                        @foreach($reward->compliments as $c)
                                            <span class="text-[10px] bg-primary/10 text-primary font-bold px-2 py-0.5 rounded-full">{{ $c['label'] ?? 'Compliment' }} @if(!empty($c['xp'])) (+{{ $c['xp'] }} XP) @endif</span>
                                        @endforeach
                                    </div>
                                @endif

                                @if($reward->level_up_tip)
                                    <p class="text-xs text-on-surface-variant mt-1.5 flex items-start gap-1"><span class="material-symbols-outlined text-sm text-tertiary">lightbulb</span> {{ $reward->level_up_tip }}</p>
                                @endif
                            </div>
                        </div>
                        <span class="font-bold text-tertiary whitespace-nowrap">+{{ $reward->xp_amount }} XP</span>
                    </div>
                @empty
                    <p class="py-12 text-center text-on-surface-variant">No rewards awarded yet. Celebrate a student to get started!</p>
                @endforelse
            </div>

            <div class="mt-6">{{ $rewards->links() }}</div>
        </div>

        <!-- Badge library + quick award -->
        <div class="space-y-6">
            <div class="clay-card p-6 bg-white">
                <h2 class="font-headline-sm text-lg font-bold mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-warning">military_tech</span> Badge Library
                </h2>
                <div class="flex flex-wrap gap-2 max-h-64 overflow-y-auto">
                    @foreach($badgeCatalog as $badge)
                        <span class="inline-flex items-center gap-1 text-xs font-bold px-3 py-1.5 rounded-full border {{ $badge->is_system ? 'bg-primary/10 text-primary border-primary/20' : 'bg-surface-container-low text-on-surface border-outline-variant/30' }}">
                            <span class="material-symbols-outlined text-sm">{{ $badge->badge_icon }}</span> {{ $badge->title }}
                        </span>
                    @endforeach
                </div>

                <form method="POST" action="{{ route('tutor.rewards.badges.store') }}" class="mt-5 pt-5 border-t border-outline-variant/20 space-y-2">
                    @csrf
                    <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Create custom badge</p>
                    <input type="text" name="title" required maxlength="80" placeholder="Badge name (e.g. Comic Creator)" class="w-full px-3 py-2 rounded-xl border border-outline-variant/30 text-xs outline-none focus:border-primary">
                    <input type="text" name="description" maxlength="240" placeholder="Short description (optional)" class="w-full px-3 py-2 rounded-xl border border-outline-variant/30 text-xs outline-none focus:border-primary">
                    <input type="text" name="badge_icon" value="military_tech" placeholder="Icon name" class="w-full px-3 py-2 rounded-xl border border-outline-variant/30 text-xs outline-none focus:border-primary">
                    <button type="submit" class="btn-enroll w-full py-2 text-xs font-bold">Create Badge</button>
                </form>
            </div>

            <div class="clay-card p-6 bg-white">
                <h2 class="font-headline-sm text-lg font-bold mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">group</span> My Students
                </h2>
                <div class="space-y-2 max-h-72 overflow-y-auto">
                    @forelse($students as $student)
                        <div class="flex items-center justify-between text-xs p-2 bg-surface-container-low rounded-lg">
                            <span class="font-bold text-on-surface">{{ $student->name }}</span>
                            <button type="button" class="text-primary font-bold hover:underline flex items-center gap-0.5"
                                @click="$dispatch('open-reward', { id: {{ $student->id }}, name: @js($student->name) })">
                                <span class="material-symbols-outlined text-xs">celebration</span> Reward
                            </button>
                        </div>
                    @empty
                        <p class="text-xs text-on-surface-variant">No assigned students yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @include('tutor.partials.reward-modal', ['badgeCatalog' => $badgeCatalog])
</div>
@endsection
