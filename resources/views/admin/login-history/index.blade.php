@extends('layouts.dashboard')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="mb-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="font-headline-xl text-headline-xl text-on-surface">Login History</h1>
            <p class="font-body-md text-body-md text-on-surface-variant mt-2">Track user login activities, timings, and locations.</p>
        </div>
        
        <div class="flex gap-2">
            <a href="{{ route('admin.login-history.index') }}" class="clay-button inline-flex items-center px-4 py-2 {{ !request('role') ? 'bg-primary text-white' : 'bg-white text-primary' }} rounded-xl text-sm font-bold transition-colors">
                All
            </a>
            <a href="{{ route('admin.login-history.index', ['role' => 'student']) }}" class="clay-button inline-flex items-center px-4 py-2 {{ request('role') === 'student' ? 'bg-primary text-white' : 'bg-white text-primary' }} rounded-xl text-sm font-bold transition-colors">
                Students
            </a>
            <a href="{{ route('admin.login-history.index', ['role' => 'tutor']) }}" class="clay-button inline-flex items-center px-4 py-2 {{ request('role') === 'tutor' ? 'bg-primary text-white' : 'bg-white text-primary' }} rounded-xl text-sm font-bold transition-colors">
                Tutors
            </a>
            <a href="{{ route('admin.login-history.index', ['role' => 'admin']) }}" class="clay-button inline-flex items-center px-4 py-2 {{ request('role') === 'admin' ? 'bg-primary text-white' : 'bg-white text-primary' }} rounded-xl text-sm font-bold transition-colors">
                Admins
            </a>
        </div>
    </div>

    <div class="clay-card p-6 md:p-8">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-outline-variant/30 text-on-surface-variant">
                        <th class="py-4 px-4 font-bold">User</th>
                        <th class="py-4 px-4 font-bold">Role</th>
                        <th class="py-4 px-4 font-bold">IP Address</th>
                        <th class="py-4 px-4 font-bold">Location</th>
                        <th class="py-4 px-4 font-bold">Login Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activities as $activity)
                    <tr class="border-b border-outline-variant/10 hover:bg-surface-container-low transition-colors">
                        <td class="py-4 px-4">
                            <div class="font-bold text-on-surface">{{ $activity->user->name ?? 'Deleted User' }}</div>
                            <div class="text-sm text-on-surface-variant">{{ $activity->user->email ?? '' }}</div>
                        </td>
                        <td class="py-4 px-4">
                            <span class="px-2 py-1 bg-secondary-container text-on-secondary-container text-xs rounded-full font-bold uppercase">
                                {{ $activity->user->role ?? 'N/A' }}
                            </span>
                        </td>
                        <td class="py-4 px-4 text-on-surface font-mono text-sm">
                            {{ $activity->ip_address ?? 'N/A' }}
                        </td>
                        <td class="py-4 px-4 text-on-surface">
                            {{ $activity->location ?? 'Unknown' }}
                        </td>
                        <td class="py-4 px-4 text-on-surface">
                            <span class="dynamic-local-time" data-utc="{{ $activity->created_at->toIso8601String() }}">
                                <x-local-time :date="$activity->created_at" format="datetime" />
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-on-surface-variant">No login activities found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-6">
            {{ $activities->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
