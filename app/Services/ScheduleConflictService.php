<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\LiveSession;
use App\Models\TutorAvailability;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ScheduleConflictService
{
    /**
     * Check if a proposed time window overlaps with any existing booked sessions for the tutor.
     */
    public function hasConflict(int $tutorId, Carbon $startTime, int $durationMinutes, ?int $ignoreBookingId = null): bool
    {
        $endTime = (clone $startTime)->addMinutes($durationMinutes);

        // Check Live Sessions
        $liveConflict = LiveSession::where('tutor_id', $tutorId)
            ->where('status', '!=', 'cancelled')
            ->get()
            ->contains(function ($session) use ($startTime, $endTime) {
                $sessionStart = Carbon::parse($session->start_time);
                $sessionEnd = (clone $sessionStart)->addMinutes($session->duration_minutes ?: 60);
                return $startTime->lt($sessionEnd) && $endTime->gt($sessionStart);
            });

        if ($liveConflict) {
            return true;
        }

        // Check Bookings (1:1 tutoring)
        $bookingQuery = Booking::where('tutor_id', $tutorId)
            ->whereIn('status', ['active', 'scheduled', 'confirmed', 'pending'])
            ->whereNotNull('scheduled_at');

        if ($ignoreBookingId) {
            $bookingQuery->where('id', '!=', $ignoreBookingId);
        }

        $bookingConflict = $bookingQuery->get()->contains(function ($booking) use ($startTime, $endTime) {
            $bookingStart = Carbon::parse($booking->scheduled_at);
            $bookingEnd = (clone $bookingStart)->addMinutes(60); // Default 60 min session
            return $startTime->lt($bookingEnd) && $endTime->gt($bookingStart);
        });

        return $bookingConflict;
    }

    /**
     * Detect all overlapping bookings and sessions for a tutor to display conflict alerts.
     */
    public function detectAllConflicts(int $tutorId): Collection
    {
        $conflicts = collect();

        $liveSessions = LiveSession::where('tutor_id', $tutorId)
            ->where('status', '!=', 'cancelled')
            ->where('start_time', '>=', now())
            ->with('course')
            ->get();

        $bookings = Booking::where('tutor_id', $tutorId)
            ->whereIn('status', ['active', 'confirmed'])
            ->where('scheduled_at', '>=', now())
            ->with(['course', 'student'])
            ->get();

        // Compare Live Sessions vs Bookings
        foreach ($liveSessions as $session) {
            $sessionStart = Carbon::parse($session->start_time);
            $sessionEnd = (clone $sessionStart)->addMinutes($session->duration_minutes ?: 60);

            foreach ($bookings as $booking) {
                $bookingStart = Carbon::parse($booking->scheduled_at);
                $bookingEnd = (clone $bookingStart)->addMinutes(60);

                if ($sessionStart->lt($bookingEnd) && $sessionEnd->gt($bookingStart)) {
                    $conflicts->push([
                        'type' => 'Group vs 1:1 Overlap',
                        'message' => "Group Session '{$session->course->title}' overlaps with 1:1 Booking for " . ($booking->student->name ?? 'Student'),
                        'start_time' => $sessionStart,
                        'end_time' => $sessionEnd,
                    ]);
                }
            }
        }

        // Compare Bookings vs Bookings
        $bookingArray = $bookings->all();
        $count = count($bookingArray);
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $b1 = $bookingArray[$i];
                $b2 = $bookingArray[$j];

                $start1 = Carbon::parse($b1->scheduled_at);
                $end1 = (clone $start1)->addMinutes(60);
                $start2 = Carbon::parse($b2->scheduled_at);
                $end2 = (clone $start2)->addMinutes(60);

                if ($start1->lt($end2) && $end1->gt($start2)) {
                    $conflicts->push([
                        'type' => 'Double Booking Overlap',
                        'message' => "Double booking detected between " . ($b1->student->name ?? 'Student 1') . " and " . ($b2->student->name ?? 'Student 2'),
                        'start_time' => $start1,
                        'end_time' => $end1,
                    ]);
                }
            }
        }

        return $conflicts;
    }
}
