@extends('layouts.dashboard')

@section('title', 'Tutor Bookings')

@section('content')
<div class="p-5 md:p-8 space-y-8">
    <div>
        <h1 class="font-headline-lg text-headline-lg font-bold mb-2">Tutor Bookings 📅</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant">Book live sessions with your course tutors.</p>
    </div>

    
    

    @if($availableSessions->isNotEmpty())
    <div class="bg-white rounded-2xl border border-surface-container-high shadow-sm p-5 md:p-8">
        <h2 class="font-headline-md font-bold mb-6">Available Sessions</h2>
        <div class="space-y-4">
            @foreach($availableSessions as $session)
                <div class="flex items-center justify-between p-4 bg-surface-container-low rounded-xl">
                    <div>
                        <h3 class="font-bold">{{ $session->course->title ?? 'Live Session' }}</h3>
                        <p class="text-sm text-on-surface-variant">
                            <x-local-time :date="$session->start_time" format="datetime" />
                            · {{ $session->duration_minutes }} min
                        </p>
                    </div>
                    <form method="POST" action="{{ route('student.bookings.store') }}">
                        @csrf
                        <input type="hidden" name="live_session_id" value="{{ $session->id }}">
                        <button type="submit" class="btn-primary btn-sm">Book Session</button>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="bg-white rounded-2xl border border-surface-container-high shadow-sm overflow-hidden">
        <div class="p-6 border-b border-surface-container-high">
            <h2 class="font-headline-md font-bold">My Bookings</h2>
        </div>
        <div class="overflow-x-auto w-full"><table class="w-full text-left">
            <thead class="bg-surface-container-low text-on-surface-variant font-bold border-b border-surface-container-high">
                <tr>
                    <th class="p-4">Tutor</th>
                    <th class="p-4">Course</th>
                    <th class="p-4">Scheduled</th>
                    <th class="p-4">Status</th>
                    <th class="p-4">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-container-high">
                @forelse($bookings as $booking)
                <tr class="hover:bg-surface-container-low transition-colors">
                    <td class="p-4">
                        <div class="flex items-center gap-3">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($booking->tutor->name ?? 'Tutor') }}&background=046bd2&color=fff" class="w-10 h-10 rounded-full" alt="">
                            <span class="font-bold">{{ $booking->tutor->name ?? 'Tutor' }}</span>
                        </div>
                    </td>
                    <td class="p-4 font-bold">{{ $booking->course->title ?? 'N/A' }}</td>
                    <td class="p-4 text-on-surface-variant text-sm">
                        @if($booking->scheduled_at)
                            <x-local-time :date="$booking->scheduled_at" format="datetime" />
                        @else
                            <x-local-time :date="$booking->created_at" format="date" />
                        @endif
                    </td>
                    <td class="p-4">
                        <span class="px-3 py-1 {{ $booking->status === 'active' ? 'bg-tertiary-container text-on-tertiary-container' : 'bg-surface-container text-on-surface-variant' }} rounded-full text-xs font-bold">{{ ucfirst($booking->status) }}</span>
                    </td>
                    <td class="p-4">
                        @php $effectiveDate = $booking->scheduled_at ?? $booking->created_at; @endphp
                        @if(isset($booking->status) && $booking->status === 'completed')
                            <span class="text-on-surface-variant text-xs opacity-70 flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">event_busy</span> Session Ended</span>
                        @elseif($effectiveDate && \Carbon\Carbon::parse($effectiveDate)->addMinutes(60)->isPast())
                            <span class="text-on-surface-variant text-xs opacity-70 flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">event_busy</span> Session Ended</span>
                        @elseif($booking->zoom_join_url && $effectiveDate && \Carbon\Carbon::parse($effectiveDate)->subHours(4)->isPast())
                            <x-join-class-action
                                :url="$booking->zoom_join_url"
                                :provider="$booking->meeting_provider"
                                :room-id="$booking->meeting_room_id"
                                :passcode="$booking->zoom_passcode"
                            />
                        @elseif($booking->zoom_join_url)
                            <x-join-class-action
                                :url="$booking->zoom_join_url"
                                :provider="$booking->meeting_provider"
                                :room-id="$booking->meeting_room_id"
                                :passcode="$booking->zoom_passcode"
                            />
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="p-5 md:p-8 text-center text-on-surface-variant">No bookings yet. Book an available session above.</td>
                </tr>
                @endforelse
            </tbody>
        </table></div>
    </div>

    {{-- Demo / Trial Classes --}}
    @if($demoSessions->isNotEmpty())
    <div class="bg-white rounded-2xl border border-surface-container-high shadow-sm overflow-hidden">
        <div class="p-6 border-b border-surface-container-high flex items-center gap-3">
            <span class="material-symbols-outlined text-secondary text-2xl">star</span>
            <h2 class="font-headline-md font-bold">Free Consultations & Trial Classes</h2>
        </div>
        <div class="overflow-x-auto w-full">
            <table class="w-full text-left">
                <thead class="bg-surface-container-low text-on-surface-variant font-bold border-b border-surface-container-high">
                    <tr>
                        <th class="p-4">Class</th>
                        <th class="p-4">Scheduled Date & Time</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-container-high">
                    @foreach($demoSessions as $demo)
                    <tr class="hover:bg-surface-container-low transition-colors">
                        <td class="p-4">
                            <div class="font-bold">{{ str_replace(['Free Trial - ', 'Consultation - '], '', $demo->camp_name) }}</div>
                            <div class="text-xs text-on-surface-variant mt-0.5">{{ str_starts_with($demo->camp_name ?? '', 'Consultation') ? 'Free Consultation' : 'Free Trial Class' }}</div>
                        </td>
                        <td class="p-4 text-sm">
                            @if($demo->scheduled_at)
                                <div class="font-bold"><x-local-time :date="$demo->scheduled_at" format="date" /></div>
                                <div class="text-xs text-on-surface-variant"><x-local-time :date="$demo->scheduled_at" format="time" /></div>
                            @else
                                <span class="text-warning font-medium text-xs">⏳ Awaiting schedule</span>
                            @endif
                        </td>
                        <td class="p-4">
                            @if(!$demo->scheduled_at)
                                <span class="px-3 py-1 bg-warning-container text-warning rounded-full text-xs font-bold">Pending Schedule</span>
                            @elseif($demo->scheduled_at && \Carbon\Carbon::parse($demo->scheduled_at)->addMinutes(60)->isPast())
                                <span class="px-3 py-1 bg-surface-container text-on-surface-variant rounded-full text-xs font-bold">Completed</span>
                            @elseif($demo->zoom_join_url)
                                <span class="px-3 py-1 bg-tertiary-container text-on-tertiary-container rounded-full text-xs font-bold">Confirmed</span>
                            @else
                                <span class="px-3 py-1 bg-surface-container text-on-surface-variant rounded-full text-xs font-bold">Pending Link</span>
                            @endif
                        </td>
                        <td class="p-4">
                            @if(!$demo->scheduled_at)
                                <span class="text-xs text-on-surface-variant opacity-70">Our team will contact you</span>
                            @elseif($demo->scheduled_at && \Carbon\Carbon::parse($demo->scheduled_at)->addMinutes(60)->isPast())
                                <span class="text-xs text-on-surface-variant opacity-70 flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">event_busy</span> Session Ended</span>
                            @elseif($demo->zoom_join_url && \Carbon\Carbon::parse($demo->scheduled_at)->subHours(4)->isPast())
                                <x-join-class-action
                                    :url="$demo->zoom_join_url"
                                    :provider="$demo->meeting_provider"
                                    :room-id="$demo->meeting_room_id"
                                    :passcode="$demo->zoom_passcode"
                                />
                            @else
                                <span class="text-xs text-on-surface-variant opacity-70">Link Pending</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
@endsection
