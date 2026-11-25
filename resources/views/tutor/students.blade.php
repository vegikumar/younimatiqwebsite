@extends('layouts.dashboard')

@section('title', 'My Students')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12 space-y-8">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 border-t-8 border-tertiary relative overflow-hidden">
        <h1 class="font-headline-xl text-headline-xl text-on-surface mb-2">My Students</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant">View students enrolled in your courses and upload completion certificates.</p>
        <div class="absolute right-0 top-0 -mr-20 -mt-20 w-64 h-64 bg-tertiary-fixed rounded-full blur-3xl opacity-20 pointer-events-none"></div>
    </div>

    @if(session('success'))
        <div class="p-4 bg-tertiary-fixed text-tertiary rounded-xl border border-tertiary-fixed-dim font-medium">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-error-container text-error rounded-xl border border-error-container font-medium">{{ session('error') }}</div>
    @endif

    <div class="clay-card p-5 md:p-8">
        <h2 class="font-bold text-lg mb-4">Enrollments & Certificates</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b-2 border-outline-variant">
                        <th class="py-4 px-4 font-bold text-on-surface">Student</th>
                        <th class="py-4 px-4 font-bold text-on-surface">Course</th>
                        <th class="py-4 px-4 font-bold text-on-surface">Status</th>
                        <th class="py-4 px-4 font-bold text-on-surface">Certificate</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($enrollments as $enrollment)
                        <tr class="border-b border-outline-variant hover:bg-surface-container-low transition-colors">
                            <td class="py-4 px-4">
                                <div class="font-bold text-on-surface">{{ $enrollment->user->name ?? 'Student' }}</div>
                                <div class="text-xs text-on-surface-variant">{{ $enrollment->user->name ?? '' }}</div>
                            </td>
                            <td class="py-4 px-4">
                                <div class="font-bold text-sm">{{ $enrollment->course->title ?? 'Course' }}</div>
                            </td>
                            <td class="py-4 px-4">
                                <span class="px-3 py-1 bg-primary-container text-on-primary-container rounded-full text-[10px] font-bold uppercase tracking-wider">{{ ucfirst($enrollment->status) }}</span>
                            </td>
                            <td class="py-4 px-4">
                                <form method="POST" action="{{ route('tutor.enrollments.certificate.upload', $enrollment) }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-2">
                                    @csrf
                                    <input type="file" name="certificate" accept=".pdf,.jpg,.jpeg,.png" required class="text-xs max-w-[160px]">
                                    <button type="submit" class="btn-primary btn-sm !px-3 !py-1.5 text-xs">Upload</button>
                                </form>
                                @if($enrollment->hasUploadedCertificate())
                                    <span class="inline-block mt-2 text-[10px] font-bold uppercase tracking-wider text-on-tertiary-container bg-tertiary-fixed px-2 py-0.5 rounded-full">Uploaded</span>
                                @endif
                                @if($enrollment->user)
                                    <button type="button" class="mt-2 text-xs font-bold text-primary hover:underline flex items-center gap-1"
                                        @click="$dispatch('open-reward', { id: {{ $enrollment->user_id }}, name: @js($enrollment->user->name), courseId: {{ $enrollment->course_id }} })">
                                        <span class="material-symbols-outlined text-sm">celebration</span> Reward
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-on-surface-variant">No enrollments found for your courses yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="clay-card p-5 md:p-8">
        <h2 class="font-bold text-lg mb-4">Live Bookings</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b-2 border-outline-variant">
                        <th class="py-4 px-4 font-bold text-on-surface">Student Name</th>
                        <th class="py-4 px-4 font-bold text-on-surface">Course Enrolled</th>
                        <th class="py-4 px-4 font-bold text-on-surface">Enrollment Date</th>
                        <th class="py-4 px-4 font-bold text-on-surface">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $booking)
                        <tr class="border-b border-outline-variant hover:bg-surface-container-low transition-colors">
                            <td class="py-4 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-tertiary-container text-on-tertiary-container flex items-center justify-center font-bold">
                                        {{ substr($booking->student->name ?? 'S', 0, 1) }}
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-bold text-on-surface">{{ $booking->student->name ?? 'Unknown Student' }}</span>
                                        @if($booking->student && $booking->student->name)
                                        <span class="text-xs text-on-surface-variant">{{ $booking->student->name }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <div class="font-bold text-sm text-on-surface">{{ $booking->course->title ?? 'Unknown Course' }}</div>
                            </td>
                            <td class="py-4 px-4 text-sm text-on-surface-variant"><x-local-time :date="$booking->created_at" format="date" /></td>
                            <td class="py-4 px-4">
                                <span class="px-3 py-1 bg-tertiary-fixed text-tertiary rounded-full text-[10px] font-bold uppercase tracking-wider">Active</span>
                                @if($booking->student)
                                    <button type="button" class="mt-2 text-xs font-bold text-primary hover:underline flex items-center gap-1"
                                        @click="$dispatch('open-reward', { id: {{ $booking->student_id }}, name: @js($booking->student->name), courseId: {{ $booking->course_id }}, bookingId: {{ $booking->id }}, isTrial: @js(($booking->session_type ?? '') === 'trial') })">
                                        <span class="material-symbols-outlined text-sm">celebration</span> Reward
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-12 text-center text-on-surface-variant">
                                <span class="material-symbols-outlined text-4xl mb-2">group_off</span>
                                <p>You don't have any live bookings yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('tutor.partials.reward-modal', ['badgeCatalog' => $badgeCatalog])
</div>
@endsection
