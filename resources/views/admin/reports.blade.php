@extends('layouts.dashboard')

@section('title', 'Reports & Analysis')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden">
        <h1 class="font-headline-xl text-headline-xl text-on-surface mb-2">Reports & Analysis</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant">Revenue and enrollment insights for the selected period.</p>
        <div class="absolute right-0 top-0 -mr-20 -mt-20 w-64 h-64 bg-primary-fixed rounded-full blur-3xl opacity-20 pointer-events-none"></div>
    </div>

    <div class="clay-card p-6 mb-8">
        <form method="GET" action="{{ route('admin.reports') }}" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-bold mb-2">Time Period</label>
                <select name="period" class="p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                    @foreach(['7' => 'Last 7 days', '30' => 'Last 30 days', '90' => 'Last 90 days', '365' => 'Last year'] as $value => $label)
                        <option value="{{ $value }}" {{ $period == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-primary btn-sm">Update Report</button>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
        <div class="clay-card p-6 text-center">
            <span class="material-symbols-outlined text-primary text-4xl mb-2">payments</span>
            <p class="text-3xl font-bold text-primary">${{ number_format($revenue, 2) }}</p>
            <p class="text-on-surface-variant text-sm">Revenue</p>
        </div>
        <div class="clay-card p-6 text-center">
            <span class="material-symbols-outlined text-secondary text-4xl mb-2">school</span>
            <p class="text-3xl font-bold">{{ $enrollments }}</p>
            <p class="text-on-surface-variant text-sm">New Enrollments</p>
        </div>
        <div class="clay-card p-6 text-center">
            <span class="material-symbols-outlined text-tertiary text-4xl mb-2">school</span>
            <p class="text-3xl font-bold">{{ $campLeads }}</p>
            <p class="text-on-surface-variant text-sm">Consultation & Trial Bookings</p>
        </div>
        <div class="clay-card p-6 text-center">
            <span class="material-symbols-outlined text-primary text-4xl mb-2">person_add</span>
            <p class="text-3xl font-bold">{{ $newStudents }}</p>
            <p class="text-on-surface-variant text-sm">New Students</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-1 md:grid-cols-2 gap-5 md:p-8">
        <div class="clay-card p-5 md:p-8">
            <h2 class="font-headline-md font-bold mb-6 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">account_balance</span> Revenue by Gateway
            </h2>
            <div class="space-y-4">
                @forelse($revenueByGateway as $row)
                    <div class="flex items-center justify-between p-4 bg-surface-container-low rounded-xl">
                        <span class="font-bold capitalize">{{ $row->gateway ?? 'Unknown' }}</span>
                        <span class="text-primary font-bold">${{ number_format($row->total, 2) }}</span>
                    </div>
                @empty
                    <p class="text-on-surface-variant text-center py-6">No payment data available.</p>
                @endforelse
            </div>
        </div>

        <div class="clay-card p-5 md:p-8">
            <h2 class="font-headline-md font-bold mb-6 flex items-center gap-2">
                <span class="material-symbols-outlined text-secondary">trending_up</span> Top Courses by Enrollment
            </h2>
            <div class="space-y-3">
                @forelse($topCourses as $index => $course)
                    <div class="flex items-center gap-4 p-4 bg-surface-container-low rounded-xl">
                        <span class="w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center font-bold text-sm">{{ $index + 1 }}</span>
                        <div class="flex-1">
                            <p class="font-bold">{{ $course->title }}</p>
                            <p class="text-sm text-on-surface-variant">{{ $course->enrollments_count }} enrollments</p>
                        </div>
                    </div>
                @empty
                    <p class="text-on-surface-variant text-center py-6">No enrollment data yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
