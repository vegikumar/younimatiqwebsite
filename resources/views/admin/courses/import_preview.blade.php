@extends('layouts.dashboard')

@section('title', 'Import Preview')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden">
        <div class="flex items-center gap-3 mb-2">
            <span class="material-symbols-outlined text-primary text-3xl">fact_check</span>
            <h1 class="font-headline-xl text-headline-xl text-on-surface">Review Import</h1>
        </div>
        <p class="font-body-lg text-body-lg text-on-surface-variant">Nothing has been saved yet. Check the courses below, then confirm to import.</p>
        <div class="absolute right-0 top-0 -mr-20 -mt-20 w-64 h-64 bg-primary-fixed rounded-full blur-3xl opacity-20 pointer-events-none"></div>
    </div>

    {{-- Summary cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="clay-card p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-tertiary-fixed text-on-tertiary-container flex items-center justify-center">
                <span class="material-symbols-outlined">add_circle</span>
            </div>
            <div>
                <div class="text-2xl font-bold text-on-surface">{{ $analysis['summary']['new'] }}</div>
                <div class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">New Courses</div>
            </div>
        </div>
        <div class="clay-card p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-secondary-container text-on-secondary-container flex items-center justify-center">
                <span class="material-symbols-outlined">sync</span>
            </div>
            <div>
                <div class="text-2xl font-bold text-on-surface">{{ $analysis['summary']['updated'] }}</div>
                <div class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Updates</div>
            </div>
        </div>
        <div class="clay-card p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-primary-fixed text-primary flex items-center justify-center">
                <span class="material-symbols-outlined">menu_book</span>
            </div>
            <div>
                <div class="text-2xl font-bold text-on-surface">{{ $analysis['summary']['classes'] }}</div>
                <div class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Classes</div>
            </div>
        </div>
        <div class="clay-card p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl {{ count($analysis['warnings']) ? 'bg-warning-container text-warning' : 'bg-surface-container text-on-surface-variant' }} flex items-center justify-center">
                <span class="material-symbols-outlined">warning</span>
            </div>
            <div>
                <div class="text-2xl font-bold text-on-surface">{{ count($analysis['warnings']) }}</div>
                <div class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Warnings</div>
            </div>
        </div>
    </div>

    @if(count($analysis['warnings']))
        <div class="mb-6 p-4 bg-warning-container border border-warning-container text-warning rounded-2xl">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-2">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-warning">warning</span>
                    <div class="font-bold text-sm">These are non-blocking warnings. The import will still proceed.</div>
                </div>
                <a href="{{ route('admin.courses.bulk-import.warnings', ['token' => $token]) }}" class="text-warning font-bold text-xs hover:underline inline-flex items-center gap-1">
                    <span class="material-symbols-outlined text-xs">download</span> Download warnings CSV
                </a>
            </div>
            <ul class="list-disc pl-10 space-y-1 text-sm max-h-56 overflow-y-auto">
                @foreach($analysis['warnings'] as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Per-course table --}}
    <div class="clay-card p-5 md:p-8 mb-6">
        <h2 class="text-lg font-bold text-on-surface mb-4">Courses to import</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b-2 border-outline-variant">
                        <th class="py-3 px-4 font-bold text-on-surface text-sm">Course</th>
                        <th class="py-3 px-4 font-bold text-on-surface text-sm">Action</th>
                        <th class="py-3 px-4 font-bold text-on-surface text-sm">Category</th>
                        <th class="py-3 px-4 font-bold text-on-surface text-sm">Age / Grades</th>
                        <th class="py-3 px-4 font-bold text-on-surface text-sm">Tutor</th>
                        <th class="py-3 px-4 font-bold text-on-surface text-sm text-right">Classes</th>
                        <th class="py-3 px-4 font-bold text-on-surface text-sm text-right">Price (USD)</th>
                        <th class="py-3 px-4 font-bold text-on-surface text-sm">Details</th>
                        <th class="py-3 px-4 font-bold text-on-surface text-sm">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($analysis['courses'] as $course)
                        <tr class="border-b border-outline-variant align-top">
                            <td class="py-3 px-4">
                                <div class="font-medium text-on-surface">{{ $course['title'] }}</div>
                                <div class="text-xs text-on-surface-variant font-mono">{{ $course['slug'] }}</div>
                            </td>
                            <td class="py-3 px-4">
                                @if($course['action'] === 'create')
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-tertiary-fixed text-on-tertiary-container">New</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-secondary-container text-on-secondary-container">Update</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-sm text-on-surface-variant">{{ $course['category'] ?: '—' }}</td>
                            <td class="py-3 px-4 text-sm text-on-surface-variant">
                                {{ $course['age_group'] ?: '—' }}
                                @if($course['grade_range'])
                                    <span class="text-xs">({{ $course['grade_range'] }})</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-sm text-on-surface-variant">{{ $course['tutor'] ?: '—' }}</td>
                            <td class="py-3 px-4 text-sm text-on-surface text-right">{{ $course['classes'] }}</td>
                            <td class="py-3 px-4 text-sm text-on-surface text-right">${{ number_format($course['price_usd'], 2) }}</td>
                            <td class="py-3 px-4">
                                <div class="flex flex-wrap gap-1">
                                    @if($course['is_camp'])
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-primary-fixed text-primary">CAMP</span>
                                    @endif
                                    @if($course['is_top_course'])
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-warning-container text-warning">TOP</span>
                                    @endif
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-surface-container text-on-surface-variant">{{ $course['outcomes'] }} outcomes</span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-surface-container text-on-surface-variant">{{ $course['highlights'] }} highlights</span>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">{{ $course['status'] }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-6 px-4 text-center text-on-surface-variant">No courses found in this file.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="clay-card p-6 flex flex-wrap items-center justify-between gap-4">
        <div class="text-sm text-on-surface-variant">
            Default status: <strong class="text-on-surface">{{ ucfirst($options['default_status']) }}</strong>
            &middot; Update existing: <strong class="text-on-surface">{{ $options['update_existing'] ? 'Yes' : 'No' }}</strong>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.courses') }}" class="px-5 py-2.5 font-bold text-on-surface-variant hover:bg-surface-container rounded-xl transition-colors text-sm">Cancel</a>
            <form action="{{ route('admin.courses.bulk-import') }}" method="POST" onsubmit="this.querySelector('button[type=submit]').disabled=true; this.querySelector('button[type=submit]').innerText='Importing...';">
                @csrf
                <input type="hidden" name="import_token" value="{{ $token }}">
                <button type="submit" class="btn-primary px-6 py-2.5 text-sm font-bold shadow-md inline-flex items-center gap-2">
                    <span class="material-symbols-outlined text-base">check</span> Confirm &amp; Import
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
