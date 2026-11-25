@extends('layouts.dashboard')

@section('title', 'Upcoming Classes')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-secondary relative overflow-hidden">
        <h1 class="font-headline-xl text-headline-xl text-on-surface mb-2">My Assigned & Upcoming Classes</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant">Launch classes as Host, view student details, and manage your teaching schedule.</p>
        
        <div class="absolute right-0 top-0 -mr-20 -mt-20 w-64 h-64 bg-secondary-fixed rounded-full blur-3xl opacity-20 pointer-events-none"></div>
    </div>

    <!-- Assigned 1:1 Course Bookings -->
    <div class="clay-card p-5 md:p-8 mb-8">
        <h2 class="font-bold text-xl text-on-surface mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">person</span> 1:1 Assigned Course Classes
        </h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b-2 border-outline-variant">
                        <th class="py-3 px-4 font-bold text-on-surface">Student Name</th>
                        <th class="py-3 px-4 font-bold text-on-surface">Course</th>
                        <th class="py-3 px-4 font-bold text-on-surface">Scheduled Date & Time</th>
                        <th class="py-3 px-4 font-bold text-on-surface text-right">Teacher Host Link</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $b)
                        <tr class="border-b border-outline-variant hover:bg-surface-container-low transition-colors">
                            <td class="py-4 px-4">
                                <div class="font-bold text-on-surface">{{ $b->student->name ?? 'Student' }}</div>
                                @if($b->student && $b->student->name)
                                <div class="text-xs text-on-surface-variant">{{ $b->student->name }}</div>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                <span class="font-medium text-on-surface">{{ $b->course->title ?? '1:1 Session' }}</span>
                            </td>
                            <td class="py-4 px-4">
                                @if($b->scheduled_at)
                                    <div class="font-bold text-on-surface"><x-local-time :date="$b->scheduled_at" format="date" /></div>
                                    <div class="text-xs text-on-surface font-medium"><x-local-time :date="$b->scheduled_at" format="time" /></div>
                                @else
                                    <span class="text-outline">TBD</span>
                                @endif
                            </td>
                            <td class="py-4 px-4 text-right">
                                @if($b->zoom_start_url && $b->scheduled_at && \Carbon\Carbon::parse($b->scheduled_at)->subHours(4)->isPast())
                                    <div class="flex flex-col gap-2 items-end">
                                        <a href="{{ $b->zoom_start_url }}" target="_blank" class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary text-white font-bold text-xs rounded-xl hover:bg-primary-container transition-colors shadow-sm">
                                            <span class="material-symbols-outlined text-sm">play_circle</span> Launch Class as Host (Web)
                                        </a>
                                    </div>
                                @elseif($b->zoom_start_url && $b->scheduled_at)
                                    <span class="text-xs text-outline italic" title="Link will be available 4 hours before class">Link Available Soon</span>
                                @elseif($b->zoom_start_url)
                                    <div class="flex flex-col gap-2 items-end">
                                        <a href="{{ $b->zoom_start_url }}" target="_blank" class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary text-white font-bold text-xs rounded-xl hover:bg-primary-container transition-colors shadow-sm">
                                            <span class="material-symbols-outlined text-sm">play_circle</span> Launch Class as Host (Web)
                                        </a>
                                    </div>
                                @else
                                    <span class="text-xs text-outline italic">Link Pending</span>
                                @endif
                                <div class="mt-2">
                                    <a href="{{ route('tutor.feedback.create', ['type' => 'regular', 'booking_id' => $b->id]) }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-surface-container-low text-primary font-bold text-xs rounded-xl hover:bg-primary-container transition-colors shadow-sm border border-outline-variant/30">
                                        <span class="material-symbols-outlined text-sm">rate_review</span> Submit Feedback
                                    </a>
                                </div>
                                @if($b->student)
                                    <div class="mt-2">
                                        <button type="button" class="inline-flex items-center gap-1.5 px-4 py-2 bg-warning-container text-warning font-bold text-xs rounded-xl hover:bg-warning-container transition-colors shadow-sm border border-warning-container"
                                            @click="$dispatch('open-reward', { id: {{ $b->student_id }}, name: @js($b->student->name), courseId: {{ $b->course_id }}, bookingId: {{ $b->id }}, isTrial: @js(($b->session_type ?? '') === 'trial') })">
                                            <span class="material-symbols-outlined text-sm">celebration</span> Reward
                                        </button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-on-surface-variant text-sm">No 1:1 course classes currently assigned.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Assigned Demo / Trial Classes -->
    <div class="clay-card p-5 md:p-8 mb-8">
        <h2 class="font-bold text-xl text-on-surface mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-warning">auto_awesome</span> Assigned Free Consultations & Trials
        </h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b-2 border-outline-variant">
                        <th class="py-3 px-4 font-bold text-on-surface">Child & Parent</th>
                        <th class="py-3 px-4 font-bold text-on-surface">Consultation / Trial</th>
                        <th class="py-3 px-4 font-bold text-on-surface">Scheduled Date & Time</th>
                        <th class="py-3 px-4 font-bold text-on-surface text-right">Teacher Host Link</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($campBookings as $cb)
                        <tr class="border-b border-outline-variant hover:bg-surface-container-low transition-colors">
                            <td class="py-4 px-4">
                                <div class="font-bold text-on-surface">{{ $cb->child_name ? $cb->child_name . ' (Student)' : 'Applied Student' }}</div>
                                <div class="text-xs text-on-surface">Parent: {{ $cb->parent_name }}</div>
                            </td>
                            <td class="py-4 px-4">
                                <span class="font-bold text-on-surface">{{ $cb->camp_name }}</span>
                                <div class="text-xs text-on-surface-variant">Age: {{ $cb->age ?? 'N/A' }}</div>
                            </td>
                            <td class="py-4 px-4">
                                @if($cb->scheduled_at)
                                    <div class="font-bold text-on-surface"><x-local-time :date="$cb->scheduled_at" format="date" /></div>
                                    <div class="text-xs text-on-surface font-medium"><x-local-time :date="$cb->scheduled_at" format="time" /></div>
                                @else
                                    <span class="text-outline">TBD</span>
                                @endif
                            </td>
                            <td class="py-4 px-4 text-right">
                                @if($cb->zoom_start_url && $cb->scheduled_at && \Carbon\Carbon::parse($cb->scheduled_at)->subHours(4)->isPast())
                                    <div class="flex flex-col gap-2 items-end">
                                        <a href="{{ $cb->zoom_start_url }}" target="_blank" class="inline-flex items-center gap-1.5 px-4 py-2 bg-warning text-white font-bold text-xs rounded-xl hover:bg-warning transition-colors shadow-sm">
                                            <span class="material-symbols-outlined text-sm">play_circle</span> Launch Demo as Host (Web)
                                        </a>
                                    </div>
                                @elseif($cb->zoom_start_url && $cb->scheduled_at)
                                    <span class="text-xs text-outline italic" title="Link will be available 4 hours before class">Link Available Soon</span>
                                @elseif($cb->zoom_start_url)
                                    <div class="flex flex-col gap-2 items-end">
                                        <a href="{{ $cb->zoom_start_url }}" target="_blank" class="inline-flex items-center gap-1.5 px-4 py-2 bg-warning text-white font-bold text-xs rounded-xl hover:bg-warning transition-colors shadow-sm">
                                            <span class="material-symbols-outlined text-sm">play_circle</span> Launch Demo as Host (Web)
                                        </a>
                                    </div>
                                @else
                                    <span class="text-xs text-outline italic">Link Pending</span>
                                @endif
                                <div class="mt-2">
                                    <a href="{{ route('tutor.feedback.create', ['type' => 'demo', 'booking_id' => $cb->id]) }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-surface-container-low text-warning font-bold text-xs rounded-xl hover:bg-warning-container transition-colors shadow-sm border border-outline-variant/30">
                                        <span class="material-symbols-outlined text-sm">rate_review</span> Submit Demo Feedback
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-on-surface-variant text-sm">No demo / trial classes currently assigned.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('tutor.partials.reward-modal', ['badgeCatalog' => $badgeCatalog])
@endsection
