@extends('layouts.dashboard')

@section('title', 'Enrolled Courses')

@section('content')
<div class="p-5 md:p-8 space-y-8">
    <div>
        <h1 class="font-headline-lg text-headline-lg font-bold mb-2">My Enrolled Courses 🎓</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant">Continue learning from your active and completed courses.</p>
    </div>

    <form action="{{ route('student.courses') }}" method="GET" class="flex gap-4 max-w-lg">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search your courses..." class="flex-1 px-4 py-3 bg-surface-container-low border border-surface-container-high rounded-xl focus:outline-none focus:border-primary">
        <button type="submit" class="btn-primary btn-sm">Search</button>
    </form>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($courses as $course)
            @php $enrollment = $enrollments->firstWhere('course_id', $course->id); @endphp
            <div class="bg-white rounded-2xl border border-surface-container-high shadow-sm overflow-hidden flex flex-col hover:border-primary/30 transition-colors">
                @php 
                    $is1to1 = $course->format === '1to1';
                    $booking = $is1to1 ? $bookings->get($course->id) : null;
                    $tutor = $booking ? $booking->tutor : null;
                @endphp

                <!-- Card Header -->
                @if($is1to1)
                    <div class="h-32 bg-gradient-to-br from-tertiary/20 to-primary/20 flex flex-col items-center justify-center p-4 relative">
                        <span class="absolute top-2 right-3 text-[10px] font-bold uppercase tracking-wider bg-white/60 text-on-tertiary-container px-2 py-0.5 rounded-full">1:1 Tutoring</span>
                        @if($tutor)
                            <div class="w-14 h-14 rounded-full bg-white border-2 border-tertiary shadow-sm flex items-center justify-center overflow-hidden mb-2 text-xl font-bold text-tertiary">
                                {{ substr($tutor->name, 0, 1) }}
                            </div>
                            <span class="text-xs font-bold text-on-surface">with {{ explode(' ', $tutor->name)[0] }}</span>
                        @else
                            <div class="w-12 h-12 rounded-full bg-white/50 border border-white/80 shadow-sm flex items-center justify-center mb-2 animate-pulse">
                                <span class="material-symbols-outlined text-tertiary/50">person_search</span>
                            </div>
                            <span class="text-xs font-bold text-on-surface-variant">Matching Tutor...</span>
                        @endif
                    </div>
                @else
                    <div class="h-32 bg-gradient-to-br from-primary/20 to-secondary/20 flex items-center justify-center">
                        <span class="material-symbols-outlined text-5xl text-primary/40">school</span>
                    </div>
                @endif

                <div class="p-6 flex-1 flex flex-col">
                    <h3 class="font-headline-md font-bold mb-2">{{ $course->title }}</h3>
                    <p class="text-sm text-on-surface-variant line-clamp-2 mb-4 flex-1">{!! strip_tags($course->description) !!}</p>

                    @if($enrollment)
                        @if($is1to1)
                            @if($booking)
                                @if(!$booking->tutor_id)
                                    <div class="bg-warning-container border border-warning-container text-warning text-sm p-4 rounded-xl mb-4 font-medium">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="material-symbols-outlined text-warning text-lg">schedule</span>
                                            <span class="font-bold">Not Yet Allotted</span>
                                        </div>
                                        <p class="text-xs text-warning/80">We are assigning the best expert for you.</p>
                                    </div>
                                @elseif(!$booking->scheduled_at)
                                    <div class="bg-secondary-container border border-secondary-container text-on-secondary-container text-sm p-4 rounded-xl mb-4 font-medium">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="material-symbols-outlined text-secondary text-lg">calendar_month</span>
                                            <span class="font-bold">Not Yet Scheduled</span>
                                        </div>
                                        <p class="text-xs text-on-secondary-container/80">Your tutor will contact you to schedule.</p>
                                    </div>
                                @elseif(isset($booking->status) && $booking->status === 'completed' || ($booking->scheduled_at && \Carbon\Carbon::parse($booking->scheduled_at)->addMinutes(60)->isPast()))
                                    <div class="bg-surface-container border border-surface-container-high text-on-surface-variant text-sm p-4 rounded-xl mb-4 font-medium flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[18px]">event_busy</span> Session Ended
                                    </div>
                                @else
                                    <div class="bg-tertiary-fixed border border-tertiary-fixed-dim text-tertiary text-sm p-4 rounded-xl mb-4 font-medium flex flex-col gap-3">
                                        <div>
                                            <div class="flex items-center gap-2 text-xs text-on-tertiary-container/80 uppercase tracking-wider font-bold mb-1">
                                                <span class="material-symbols-outlined text-[14px]">event</span> Next Session
                                            </div>
                                            <div class="text-base font-bold text-on-tertiary-container">
                                                <x-local-time :date="$booking->scheduled_at" format="datetime" />
                                            </div>
                                        </div>
                                        @if($booking->zoom_join_url && \Carbon\Carbon::parse($booking->scheduled_at)->subHours(4)->isPast())
                                            <x-join-class-action
                                                :url="$booking->zoom_join_url"
                                                :provider="$booking->meeting_provider"
                                                :room-id="$booking->meeting_room_id"
                                                :passcode="$booking->zoom_passcode"
                                                button-class="flex items-center justify-center gap-1.5 bg-success text-white w-full py-2 rounded-lg text-sm font-bold hover:bg-success transition-colors shadow-sm"
                                                :icon="true"
                                            />
                                        @elseif($booking->zoom_join_url)
                                            <span class="flex items-center justify-center gap-1.5 bg-tertiary-fixed text-on-tertiary-container w-full py-2 rounded-lg text-sm font-bold opacity-80 cursor-not-allowed shadow-sm" title="Link will be available 4 hours before class">
                                                <span class="material-symbols-outlined text-[18px]">schedule</span> Link Available Soon
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            @else
                                <div class="bg-surface-container-low border border-outline-variant text-on-surface text-sm p-4 rounded-xl mb-4 font-medium">
                                    Processing your 1:1 booking...
                                </div>
                            @endif
                        @else
                            <div class="mb-4">
                                <div class="flex justify-between text-xs text-on-surface-variant mb-1">
                                    <span>Progress</span>
                                    <span>{{ round($enrollment->progress ?? 0) }}%</span>
                                </div>
                                <div class="w-full bg-surface-container-high rounded-full h-2">
                                    <div class="bg-primary h-2 rounded-full" style="width: {{ $enrollment->progress ?? 0 }}%"></div>
                                </div>
                            </div>
                            <span class="inline-block px-3 py-1 rounded-full text-[10px] uppercase tracking-wider font-bold mb-4 {{ $enrollment->status === 'completed' ? 'bg-secondary-container text-on-secondary-container' : 'bg-primary-container text-on-primary-container' }}">
                                {{ $enrollment->status }}
                            </span>
                        @endif
                    @endif

                    <div class="flex flex-wrap items-center justify-between gap-2 pt-4 border-t border-surface-container-high mt-auto">
                        @if($is1to1)
                            <span class="text-xs text-on-surface-variant font-medium">1:1 Coaching</span>
                            <div class="flex flex-wrap items-center gap-2 justify-end">
                                @if($enrollment)
                                    <x-enrollment-certificate-button :enrollment="$enrollment" :course="$course" />
                                @endif
                                @if($booking && $booking->tutor_id)
                                    <a href="mailto:support@younimatiq.com" class="btn-secondary btn-sm !px-4 text-xs">
                                        Contact Support
                                    </a>
                                @else
                                    <span class="text-xs text-on-surface-variant italic">Pending...</span>
                                @endif
                            </div>
                        @else
                            <span class="text-xs text-on-surface-variant font-medium">{{ $course->modules->count() }} modules</span>
                            <div class="flex flex-wrap items-center gap-2 justify-end">
                                @if($enrollment)
                                    <x-enrollment-certificate-button :enrollment="$enrollment" :course="$course" />
                                @endif
                                <a href="{{ route('student.courses.player', $course) }}" class="btn-primary btn-sm !px-6">
                                    {{ ($enrollment->progress ?? 0) > 0 ? 'Continue' : 'Start' }}
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if($courses->isEmpty())
        <div class="bg-surface-container-low border-2 border-dashed border-surface-container-high rounded-2xl p-6 md:p-12 text-center">
            <span class="material-symbols-outlined text-5xl text-on-surface-variant mb-4">school</span>
            <h3 class="font-bold text-lg mb-2">No enrolled courses yet</h3>
            <p class="text-on-surface-variant mb-6">Browse our catalog and enroll in your first adventure.</p>
            <a href="{{ route('courses.index') }}" class="btn-primary btn-sm">Explore Courses</a>
        </div>
    @endif
</div>
@endsection
