<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CampBooking;
use App\Models\ClassRecording;
use App\Models\Enrollment;
use App\Models\LiveSession;
use App\Models\RecordingAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class StudentRecordingAccess
{
    /**
     * Recordings the student can watch (active admin assignment, not expired/purged).
     */
    public function queryForStudent(User $user): Builder
    {
        $assignedIds = RecordingAssignment::query()
            ->where('student_id', $user->id)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->pluck('class_recording_id');

        return ClassRecording::query()
            ->whereIn('id', $assignedIds)
            ->whereNull('deleted_from_storage_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function studentCanAccess(User $user, ClassRecording $recording): bool
    {
        if (! $recording->isAvailable()) {
            return false;
        }

        return RecordingAssignment::query()
            ->where('student_id', $user->id)
            ->where('class_recording_id', $recording->id)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Recordings from classes the student attended — shown so they can request access.
     */
    public function requestableQueryForStudent(User $user): Builder
    {
        $bookingIds = Booking::where('student_id', $user->id)->pluck('id');
        $roomIds = Booking::where('student_id', $user->id)
            ->whereNotNull('meeting_room_id')
            ->pluck('meeting_room_id')
            ->merge(
                CampBooking::where('email', $user->email)
                    ->whereNotNull('meeting_room_id')
                    ->pluck('meeting_room_id')
            )
            ->filter()
            ->unique()
            ->values();

        $liveSessionIds = LiveSession::query()
            ->whereIn('course_id', Enrollment::where('user_id', $user->id)
                ->whereIn('status', ['active', 'completed'])
                ->pluck('course_id'))
            ->pluck('id');

        $courseIds = Enrollment::where('user_id', $user->id)
            ->whereIn('status', ['active', 'completed'])
            ->pluck('course_id');

        $assignedIds = RecordingAssignment::where('student_id', $user->id)->pluck('class_recording_id');

        return ClassRecording::query()
            ->whereNull('deleted_from_storage_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) use ($bookingIds, $roomIds, $liveSessionIds, $courseIds) {
                $q->whereIn('booking_id', $bookingIds)
                    ->orWhereIn('live_session_id', $liveSessionIds)
                    ->orWhere(function ($inner) use ($roomIds) {
                        $inner->where('provider', 'meet')
                            ->whereIn('meeting_room_id', $roomIds);
                    })
                    ->orWhere(function ($inner) use ($courseIds) {
                        $inner->where(function ($p) {
                            $p->whereNull('provider')->orWhere('provider', '!=', 'meet');
                        })->whereIn('course_id', $courseIds);
                    });
            })
            ->whereNotIn('id', $assignedIds);
    }

    public function studentRelatedToRecording(User $user, ClassRecording $recording): bool
    {
        return $this->requestableQueryForStudent($user)
            ->where('class_recordings.id', $recording->id)
            ->exists()
            || $this->studentCanAccess($user, $recording);
    }
}
