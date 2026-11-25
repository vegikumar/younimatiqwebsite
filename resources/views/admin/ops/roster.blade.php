@extends('layouts.dashboard')

@section('title', 'Admin Operations - Teacher Roster & Leaderboard')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden bg-white">
        <h1 class="font-headline-xl text-2xl font-bold text-on-surface mb-2">Admin Operations: Teacher Roster & Performance Leaderboard</h1>
        <p class="font-body-lg text-sm text-on-surface-variant">View instructor average ratings, section counts, total enrollments, punctuality compliance, and performance ranking.</p>
    </div>

    <!-- Teacher Leaderboard -->
    <div class="mb-10 clay-card p-6 bg-white border border-outline-variant/20">
        <h2 class="font-headline-sm text-lg font-bold mb-4 text-on-surface flex items-center gap-2">
            <span class="material-symbols-outlined text-warning">emoji_events</span> Teacher Performance Leaderboard
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($leaderboard->take(3) as $index => $item)
                <div class="p-5 rounded-2xl border border-warning-container bg-warning-container/50 relative overflow-hidden">
                    <div class="absolute top-2 right-2 w-8 h-8 rounded-full bg-warning-container text-white font-extrabold flex items-center justify-center text-xs">
                        #{{ $index + 1 }}
                    </div>
                    <h3 class="font-bold text-base text-on-surface mb-1">{{ $item['tutor']->name }}</h3>
                    <p class="text-xs text-on-surface-variant mb-3">{{ $item['tutor']->email }}</p>
                    <div class="flex justify-between text-xs font-bold border-t border-warning-container/60 pt-2">
                        <span>Rating: <strong class="text-warning">★ {{ $item['rating'] }}</strong></span>
                        <span>Renewals: <strong class="text-on-tertiary-container">{{ $item['re_enrollment_rate'] }}%</strong></span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Operations Roster Table -->
    <div class="clay-card p-6 bg-white border border-outline-variant/20">
        <h2 class="font-headline-sm text-lg font-bold mb-4 text-on-surface flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">badge</span> Full Instructor Operations Roster
        </h2>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-outline-variant/20 text-xs">
                        <th class="py-3 px-4 font-bold">Instructor</th>
                        <th class="py-3 px-4 font-bold">Avg Rating</th>
                        <th class="py-3 px-4 font-bold">Active Sections</th>
                        <th class="py-3 px-4 font-bold">Learners Taught</th>
                        <th class="py-3 px-4 font-bold">Punctuality Rate</th>
                        <th class="py-3 px-4 font-bold">Re-enrollment Rate</th>
                        <th class="py-3 px-4 font-bold">Monthly Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rosterData as $row)
                        <tr class="border-b border-outline-variant/10 text-xs hover:bg-surface-container-low">
                            <td class="py-3 px-4 font-bold">
                                {{ $row['tutor']->name }}
                                <p class="text-[10px] text-on-surface-variant font-normal">{{ $row['tutor']->email }}</p>
                            </td>
                            <td class="py-3 px-4 font-bold text-warning">★ {{ $row['rating'] }}</td>
                            <td class="py-3 px-4">{{ $row['sections_count'] }} sections</td>
                            <td class="py-3 px-4 font-bold">{{ $row['learners_taught'] }} learners</td>
                            <td class="py-3 px-4 font-bold text-on-tertiary-container">{{ $row['punctuality_rate'] }}%</td>
                            <td class="py-3 px-4 font-bold text-on-tertiary-container">{{ $row['re_enrollment_rate'] }}%</td>
                            <td class="py-3 px-4 font-extrabold text-primary">${{ number_format($row['monthly_earnings'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-on-surface-variant text-xs">No instructor records found in roster.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
