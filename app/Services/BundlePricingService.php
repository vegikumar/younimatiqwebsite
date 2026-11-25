<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseBundle;
use App\Models\Enrollment;
use App\Models\User;

class BundlePricingService
{
    /**
     * Apply the best active bundle discount when the student already owns
     * (or is buying into) another course in the same bundle.
     */
    public function forStudentCourse(?User $student, Course $course, float $originalPrice): array
    {
        if (! $student || $originalPrice <= 0) {
            return $this->none($originalPrice);
        }

        $bundles = CourseBundle::active()
            ->whereHas('courses', fn ($q) => $q->where('courses.id', $course->id))
            ->with('courses:id')
            ->get();

        if ($bundles->isEmpty()) {
            return $this->none($originalPrice);
        }

        $ownedIds = Enrollment::where('user_id', $student->id)
            ->whereIn('status', ['active', 'completed'])
            ->pluck('course_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $best = null;
        foreach ($bundles as $bundle) {
            $otherIds = $bundle->courses->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->reject(fn ($id) => $id === (int) $course->id)
                ->values()
                ->all();

            if (empty($otherIds)) {
                continue;
            }

            $ownsOther = count(array_intersect($ownedIds, $otherIds)) > 0;
            if (! $ownsOther) {
                continue;
            }

            $pct = (int) $bundle->discount_percent;
            if ($best === null || $pct > $best['discount_percent']) {
                $best = [
                    'has_bundle_discount' => true,
                    'bundle_id' => $bundle->id,
                    'bundle_name' => $bundle->name,
                    'discount_percent' => $pct,
                    'discount_amount' => round($originalPrice * ($pct / 100), 2),
                    'final_price' => max(0, round($originalPrice * (1 - $pct / 100), 2)),
                ];
            }
        }

        return $best ?? $this->none($originalPrice);
    }

    private function none(float $originalPrice): array
    {
        return [
            'has_bundle_discount' => false,
            'bundle_id' => null,
            'bundle_name' => null,
            'discount_percent' => 0,
            'discount_amount' => 0.0,
            'final_price' => $originalPrice,
        ];
    }
}
