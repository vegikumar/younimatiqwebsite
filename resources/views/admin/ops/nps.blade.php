@extends('layouts.dashboard')

@section('title', 'Admin Operations - Instructor NPS & Churn Analytics')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden bg-white">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="font-headline-xl text-2xl font-bold text-on-surface mb-2">Instructor Performance & NPS Dashboard</h1>
                <p class="font-body-lg text-sm text-on-surface-variant">Spot instructors who need support before churn shows up in reviews. Monitor Net Promoter Scores and early health warnings.</p>
            </div>
            <div class="px-5 py-3 bg-primary-container text-on-primary-container rounded-2xl text-center">
                <p class="text-[10px] uppercase tracking-wider font-bold">Roster Avg NPS</p>
                <h3 class="text-2xl font-extrabold">{{ $avgNps }} / 10</h3>
            </div>
        </div>
    </div>

    <!-- At-Risk Instructor Alerts -->
    @if($atRiskTutors->count() > 0)
        <div class="mb-10 p-6 bg-error-container text-on-error-container rounded-2xl shadow-sm space-y-3 border-l-8 border-error">
            <h3 class="font-bold text-base flex items-center gap-2">
                <span class="material-symbols-outlined text-error">warning</span> At-Risk Instructors Flagged ({{ $atRiskTutors->count() }})
            </h3>
            <p class="text-xs opacity-90">These instructors have lower ratings or NPS scores and may need curriculum or operational support:</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                @foreach($atRiskTutors as $item)
                    <div class="p-3 bg-white/90 rounded-xl text-xs font-bold text-on-surface flex justify-between items-center">
                        <div>
                            <p class="text-sm font-bold text-on-surface">{{ $item['tutor']->name }}</p>
                            <p class="text-[10px] text-on-surface-variant font-normal">{{ $item['tutor']->email }}</p>
                        </div>
                        <div class="text-right">
                            <span class="px-2 py-0.5 rounded bg-error-container text-error font-bold">Rating: ★ {{ $item['rating'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Full Instructor Health Table -->
    <div class="clay-card p-6 bg-white border border-outline-variant/20">
        <h2 class="font-headline-sm text-lg font-bold mb-4 text-on-surface flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">health_metrics</span> Instructor Health Roster
        </h2>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-outline-variant/20 text-xs">
                        <th class="py-3 px-4 font-bold">Instructor</th>
                        <th class="py-3 px-4 font-bold">Rating</th>
                        <th class="py-3 px-4 font-bold">NPS Score</th>
                        <th class="py-3 px-4 font-bold">Punctuality Rate</th>
                        <th class="py-3 px-4 font-bold">Re-enrollment Rate</th>
                        <th class="py-3 px-4 font-bold">Health Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roster as $row)
                        @php
                            $isAtRisk = $row['rating'] < 4.5 || ($row['tutor']->nps_score ?? 9.5) < 8.0;
                        @endphp
                        <tr class="border-b border-outline-variant/10 text-xs hover:bg-surface-container-low">
                            <td class="py-3 px-4 font-bold">{{ $row['tutor']->name }}</td>
                            <td class="py-3 px-4 font-bold text-warning">★ {{ $row['rating'] }}</td>
                            <td class="py-3 px-4 font-bold">{{ $row['tutor']->nps_score ?? 9.5 }} / 10</td>
                            <td class="py-3 px-4 font-bold text-on-tertiary-container">{{ $row['punctuality_rate'] }}%</td>
                            <td class="py-3 px-4 font-bold text-on-tertiary-container">{{ $row['re_enrollment_rate'] }}%</td>
                            <td class="py-3 px-4">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $isAtRisk ? 'bg-error-container text-error' : 'bg-tertiary-fixed text-tertiary' }}">
                                    {{ $isAtRisk ? 'Needs Support' : 'Healthy' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-on-surface-variant text-xs">No instructor records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
