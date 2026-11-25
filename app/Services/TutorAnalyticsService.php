<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Review;
use App\Models\TutorAvailability;
use App\Models\TutorPayout;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class TutorAnalyticsService
{
    /**
     * Compute comprehensive tutor performance & business metrics.
     */
    public function getMetrics(int $tutorId): array
    {
        return Cache::remember("tutor:metrics:{$tutorId}", 120, function () use ($tutorId) {
            return $this->computeMetrics($tutorId);
        });
    }

    protected function computeMetrics(int $tutorId): array
    {
        $courseIds = Course::where('tutor_id', $tutorId)->pluck('id');

        $reviewStats = Review::whereIn('course_id', $courseIds)
            ->selectRaw('COUNT(*) as review_count, AVG(rating) as avg_rating')
            ->first();
        $reviewCount = (int) ($reviewStats->review_count ?? 0);
        $classScore = $reviewCount > 0 ? round((float) $reviewStats->avg_rating, 1) : 5.0;

        $learnersTaught = (int) Enrollment::whereIn('course_id', $courseIds)->distinct('user_id')->count('user_id');
        $committedSlots = (int) TutorAvailability::where('tutor_id', $tutorId)->count();
        $cancellations = (int) Booking::where('tutor_id', $tutorId)->where('status', 'cancelled')->count();

        $enrollmentStats = Enrollment::whereIn('course_id', $courseIds)
            ->selectRaw('COUNT(*) as total_enrollments, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as withdrawals, SUM(CASE WHEN upsold_by_tutor_id IS NOT NULL THEN 1 ELSE 0 END) as re_enrollments', ['cancelled'])
            ->first();
        $totalEnrollments = (int) ($enrollmentStats->total_enrollments ?? 0);
        $withdrawals = (int) ($enrollmentStats->withdrawals ?? 0);
        $reEnrollments = (int) ($enrollmentStats->re_enrollments ?? 0);
        $reEnrollmentRate = $totalEnrollments > 0 ? round(($reEnrollments / $totalEnrollments) * 100, 1) : 0;

        $pastStats = Booking::where('tutor_id', $tutorId)
            ->where('session_type', 'scheduled_class')
            ->where('scheduled_at', '<', now())
            ->selectRaw('COUNT(*) as past_classes, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as missed_classes, SUM(CASE WHEN status != ? THEN 1 ELSE 0 END) as completed_classes', ['cancelled', 'cancelled'])
            ->first();
        $pastClasses = (int) ($pastStats->past_classes ?? 0);
        $missedClasses = (int) ($pastStats->missed_classes ?? 0);
        $completedClasses = (int) ($pastStats->completed_classes ?? 0);
        $punctualityRate = $pastClasses > 0
            ? round((($pastClasses - $missedClasses) / $pastClasses) * 100, 1)
            : null;
        $hoursWorked = round($completedClasses, 1);

        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $monthlyPayout = TutorPayout::where('tutor_id', $tutorId)
            ->where('period_month', $currentMonth)
            ->where('period_year', $currentYear)
            ->value('net_payout');

        if ($monthlyPayout !== null) {
            $monthlyEarnings = (float) $monthlyPayout;
        } else {
            $monthlyEarnings = (float) Payment::whereIn('course_id', $courseIds)
                ->where('status', 'completed')
                ->whereMonth('created_at', $currentMonth)
                ->whereYear('created_at', $currentYear)
                ->sum('amount') * 0.70;
        }

        $yearlyEarnings = (float) Payment::whereIn('course_id', $courseIds)
            ->where('status', 'completed')
            ->whereYear('created_at', $currentYear)
            ->sum('amount') * 0.70;

        return [
            'class_score' => $classScore,
            'review_count' => $reviewCount,
            'learners_taught' => $learnersTaught,
            'committed_slots' => $committedSlots,
            'punctuality_rate' => $punctualityRate,
            'past_classes' => $pastClasses,
            'cancellations' => $cancellations,
            'withdrawals' => $withdrawals,
            're_enrollment_rate' => $reEnrollmentRate,
            'monthly_earnings' => round($monthlyEarnings, 2),
            'yearly_earnings' => round($yearlyEarnings, 2),
            'hours_worked' => $hoursWorked,
        ];
    }

    /**
     * Get Ops Admin Roster data for all tutors.
     */
    public function getOpsRosterData(): Collection
    {
        $tutors = User::where('role', 'tutor')->get(['id', 'name', 'email', 'status']);

        return $tutors->map(function ($tutor) {
            $metrics = $this->getMetrics($tutor->id);
            $sectionsCount = Course::where('tutor_id', $tutor->id)->count();

            return [
                'tutor' => $tutor,
                'rating' => $metrics['class_score'],
                'sections_count' => $sectionsCount,
                'learners_taught' => $metrics['learners_taught'],
                'punctuality_rate' => $metrics['punctuality_rate'],
                're_enrollment_rate' => $metrics['re_enrollment_rate'],
                'monthly_earnings' => $metrics['monthly_earnings'],
            ];
        })->sortByDesc('rating');
    }
}
