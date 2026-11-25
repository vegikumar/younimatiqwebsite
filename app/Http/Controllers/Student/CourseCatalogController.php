<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Support\NextLiveBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CourseCatalogController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::guard('web')->id();

        $query = Enrollment::with(['course.tutor', 'course.modules'])
            ->where('user_id', $userId)
            ->whereIn('status', ['active', 'completed']);

        if ($request->filled('search')) {
            $query->whereHas('course', function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $enrollments = $query->orderBy('created_at', 'desc')->get();
        $courses = $enrollments->map(fn ($e) => $e->course)->filter();

        $allBookings = \App\Models\Booking::with('tutor')
            ->where('student_id', $userId)
            ->whereIn('course_id', $courses->pluck('id'))
            ->orderBy('scheduled_at', 'asc')
            ->get();

        $bookings = collect();
        foreach ($courses as $course) {
            $courseBookings = $allBookings->where('course_id', $course->id)->values();
            $bookings[$course->id] = NextLiveBooking::pick($courseBookings);
        }

        $coursesPayload = $courses->values()->map(fn ($course) => [
            'id' => $course->id,
            'title' => $course->title,
            'description' => $course->description,
            'format' => $course->format,
            'course_type' => $course->course_type,
            'modules_count' => $course->modules?->count() ?? 0,
        ])->all();

        $enrollmentsByCourse = [];
        foreach ($enrollments as $enrollment) {
            $enrollmentsByCourse[$enrollment->course_id] = [
                'id' => $enrollment->id,
                'course_id' => $enrollment->course_id,
                'status' => $enrollment->status,
                'delivery_mode' => $enrollment->delivery_mode,
                'progress' => (float) ($enrollment->progress ?? 0),
                'has_certificate' => $enrollment->hasUploadedCertificate(),
            ];
        }

        $bookingsByCourse = [];
        foreach ($bookings as $courseId => $booking) {
            if (! $booking) {
                continue;
            }
            $scheduledAt = $booking->scheduled_at ? \Carbon\Carbon::parse($booking->scheduled_at) : null;
            $displayTz = \App\Services\TimezoneService::resolveForDisplay(
                auth()->user()?->timezone,
                $booking->parent_timezone,
                $booking->timezone,
                config('app.timezone')
            );
            $localAt = $scheduledAt?->copy()->timezone($displayTz);
            $bookingsByCourse[$courseId] = [
                'id' => $booking->id,
                'tutor_id' => $booking->tutor_id,
                'status' => $booking->status,
                'session_type' => $booking->session_type,
                'scheduled_at' => $scheduledAt?->toIso8601String(),
                'scheduled_at_label' => $localAt?->format('M j, Y g:i A'),
                'session_ended' => $scheduledAt && $scheduledAt->copy()->addMinutes(60)->isPast(),
                'zoom_join_url' => $booking->zoom_join_url,
                'join_url' => $booking->zoom_join_url,
                'tutor' => $booking->tutor ? [
                    'name' => $booking->tutor->name,
                ] : null,
            ];
        }

        return \App\Support\InertiaPage::react('Student/Courses', [
            'courses' => $coursesPayload,
            'enrollmentsByCourse' => $enrollmentsByCourse,
            'bookingsByCourse' => $bookingsByCourse,
            'search' => $request->input('search', ''),
        ]);
    }
}
