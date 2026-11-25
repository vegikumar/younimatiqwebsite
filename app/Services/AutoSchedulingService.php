<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Course;
use App\Models\TutorAvailability;
use App\Models\User;
use Carbon\Carbon;

class AutoSchedulingService
{
    public function __construct(private ScheduleConflictService $conflictService) {}

    /**
     * Auto-schedule a meeting request from a parent/student.
     */
    public function scheduleRequest(User $student, Course $course, Carbon $requestedStartTime, string $parentTimezone): array
    {
        $tutor = $course->tutor;
        if (!$tutor) {
            return ['success' => false, 'message' => 'No tutor assigned to this course.'];
        }

        // Convert requested start time to UTC
        $utcStartTime = (clone $requestedStartTime)->setTimezone('UTC');

        // Conflict check
        if ($this->conflictService->hasConflict($tutor->id, $utcStartTime, 60)) {
            return [
                'success' => false,
                'message' => 'The requested time slot has a schedule conflict. Please choose another time.',
            ];
        }

        // Determine format type
        $formatType = $course->format === 'tutoring' ? 'tutoring_1on1' : 'group_class';

        // Check availability slot
        $matchingSlot = TutorAvailability::where('tutor_id', $tutor->id)
            ->where('format_type', $formatType)
            ->where('is_booked', false)
            ->first();

        // Generate Zoom Meeting for normal class
        $zoomJoinUrl = null;
        $zoomStartUrl = null;
        $tutorTimezone = $tutor->timezone ?: 'America/Los_Angeles';

        try {
            $zoomService = app(\App\Services\ZoomService::class);
            $zoomData = $zoomService->createMeeting($course->title . ' Normal Class', $utcStartTime->toDateTimeString(), 60, $parentTimezone);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Zoom meeting creation failed for normal class: ' . $e->getMessage());
            $zoomData = [];
        }

        // Create booking
        $booking = Booking::create(array_merge([
            'student_id' => $student->id,
            'tutor_id' => $tutor->id,
            'course_id' => $course->id,
            'status' => 'active',
            'session_type' => 'live_session',
            'scheduled_at' => $utcStartTime,
            'parent_timezone' => $parentTimezone,
            'tutor_timezone' => $tutorTimezone,
            'description' => "Scheduled {$course->title} session",
        ], \App\Services\ZoomService::bookingFields($zoomData)));

        if ($matchingSlot && $formatType === 'tutoring_1on1') {
            $matchingSlot->update(['is_booked' => true]);
        }

        return [
            'success' => true,
            'booking' => $booking,
            'message' => 'Session auto-scheduled successfully!',
        ];
    }
}
