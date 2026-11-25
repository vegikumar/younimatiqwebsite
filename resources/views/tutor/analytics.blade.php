@extends('layouts.dashboard')

@section('title', 'Analytics & Insights')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="font-headline-xl text-headline-xl text-on-surface mb-2">Analytics & Business Insights</h1>
                <p class="font-body-lg text-body-lg text-on-surface-variant">Track monthly and yearly earnings roll-up, total learners taught, hours worked, punctuality, class scores, and re-enrollment stickiness rate.</p>
            </div>
            <a href="{{ route('tutor.payouts') }}" class="btn-primary py-2.5 px-5 text-xs font-bold flex items-center gap-1">
                <span class="material-symbols-outlined text-sm">receipt_long</span> Itemized Payouts Report
            </a>
        </div>
    </div>

    <!-- Top Metrics Banner -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
        <div class="clay-card p-6 bg-white border-l-4 border-primary">
            <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1">Monthly Earnings</p>
            <h3 class="text-3xl font-extrabold text-primary">${{ number_format($metrics['monthly_earnings'], 2) }}</h3>
            <p class="text-[10px] text-on-surface-variant mt-1">Yearly: ${{ number_format($metrics['yearly_earnings'], 2) }}</p>
        </div>

        <div class="clay-card p-6 bg-white border-l-4 border-secondary">
            <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1">Learners Taught</p>
            <h3 class="text-3xl font-extrabold text-on-surface">{{ number_format($metrics['learners_taught']) }}</h3>
            <p class="text-[10px] text-on-surface-variant mt-1">Unique enrolled students</p>
        </div>

        <div class="clay-card p-6 bg-white border-l-4 border-accent">
            <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1">Hours Worked</p>
            <h3 class="text-3xl font-extrabold text-on-surface">{{ number_format($metrics['hours_worked']) }} hrs</h3>
            <p class="text-[10px] text-on-surface-variant mt-1">Committed time slots</p>
        </div>

        <div class="clay-card p-6 bg-white border-l-4 border-tertiary">
            <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1">Re-enrollment Stickiness</p>
            <h3 class="text-3xl font-extrabold text-on-tertiary-container">{{ $metrics['re_enrollment_rate'] }}%</h3>
            <p class="text-[10px] text-on-surface-variant mt-1">Leading indicator of brand stickiness</p>
        </div>
    </div>

    <!-- Performance Details Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-1 md:grid-cols-3 gap-5 md:p-8">
        <div class="clay-card p-6 bg-white space-y-4">
            <h3 class="font-bold text-base text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">grade</span> Class Score & Parent Reviews
            </h3>
            <div class="flex items-center gap-4 p-4 bg-surface-container-low rounded-2xl">
                <div class="text-4xl font-extrabold text-warning">{{ $metrics['class_score'] }}</div>
                <div>
                    <div class="flex text-warning text-sm">★★★★★</div>
                    <p class="text-xs font-bold text-on-surface-variant">{{ $metrics['review_count'] }} parent reviews</p>
                </div>
            </div>
        </div>

        <div class="clay-card p-6 bg-white space-y-4">
            <h3 class="font-bold text-base text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">timer</span> Punctuality & Time Commitment
            </h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center text-xs p-3 bg-surface-container-low rounded-xl">
                    <span class="font-bold">Punctuality Score</span>
                    <span class="font-extrabold text-on-tertiary-container">{{ $metrics['punctuality_rate'] }}%</span>
                </div>
                <div class="flex justify-between items-center text-xs p-3 bg-surface-container-low rounded-xl">
                    <span class="font-bold">Committed Time Slots</span>
                    <span class="font-bold text-on-surface">{{ $metrics['committed_slots'] }} slots</span>
                </div>
            </div>
        </div>

        <div class="clay-card p-6 bg-white space-y-4">
            <h3 class="font-bold text-base text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">do_not_disturb_on</span> Cancellations & Withdrawals
            </h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center text-xs p-3 bg-surface-container-low rounded-xl">
                    <span class="font-bold">Class Cancellations</span>
                    <span class="font-bold text-error">{{ $metrics['cancellations'] }}</span>
                </div>
                <div class="flex justify-between items-center text-xs p-3 bg-surface-container-low rounded-xl">
                    <span class="font-bold">Student Withdrawals</span>
                    <span class="font-bold text-error">{{ $metrics['withdrawals'] }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
