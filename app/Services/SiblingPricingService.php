<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\SiblingDiscount;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SiblingPricingService
{
    public const DISCOUNT_PERCENT = 15;

    /**
     * Resolve whether the student qualifies for sibling pricing via linked parents.
     */
    public function forStudent(?User $student, float $originalPrice): array
    {
        if (! $student || $originalPrice <= 0) {
            return $this->none($originalPrice);
        }

        $parentIds = $student->parents()->pluck('users.id');
        if ($parentIds->isEmpty()) {
            // Also check legacy sibling_discounts by student id
            $legacy = SiblingDiscount::where('sibling_student_id', $student->id)
                ->orWhere('primary_student_id', $student->id)
                ->exists();
            if (! $legacy) {
                return $this->none($originalPrice);
            }

            return $this->apply($originalPrice);
        }

        foreach ($parentIds as $parentId) {
            if ($this->familyQualifies((int) $parentId, $student->id)) {
                $this->ensureSiblingRow((int) $parentId, $student->id);

                return $this->apply($originalPrice);
            }
        }

        return $this->none($originalPrice);
    }

    public function familyQualifies(int $parentId, int $currentStudentId): bool
    {
        if (SiblingDiscount::where('parent_id', $parentId)->exists()) {
            return true;
        }

        $childIds = DB::table('parent_student')
            ->where('parent_id', $parentId)
            ->pluck('student_id');

        if ($childIds->count() < 2) {
            return false;
        }

        // Another sibling already enrolled (paid) qualifies the family
        $otherEnrolled = Enrollment::whereIn('user_id', $childIds->reject(fn ($id) => (int) $id === $currentStudentId)->all())
            ->whereIn('status', ['active', 'completed'])
            ->exists();

        return $otherEnrolled || $childIds->count() >= 2;
    }

    public function ensureSiblingRow(int $parentId, int $studentId): void
    {
        $children = DB::table('parent_student')
            ->where('parent_id', $parentId)
            ->orderBy('student_id')
            ->pluck('student_id');

        if ($children->count() < 2) {
            return;
        }

        $primary = (int) $children->first();
        if ($primary === $studentId) {
            $primary = (int) ($children->get(1) ?? $primary);
        }

        SiblingDiscount::firstOrCreate(
            [
                'parent_id' => $parentId,
                'sibling_student_id' => $studentId,
            ],
            [
                'primary_student_id' => $primary,
                'discount_percent' => self::DISCOUNT_PERCENT,
            ]
        );
    }

    private function apply(float $originalPrice): array
    {
        $discountAmount = round($originalPrice * (self::DISCOUNT_PERCENT / 100), 2);

        return [
            'has_sibling_discount' => true,
            'discount_percent' => self::DISCOUNT_PERCENT,
            'discount_amount' => $discountAmount,
            'final_price' => max(0, $originalPrice - $discountAmount),
        ];
    }

    private function none(float $originalPrice): array
    {
        return [
            'has_sibling_discount' => false,
            'discount_percent' => 0,
            'discount_amount' => 0.0,
            'final_price' => $originalPrice,
        ];
    }
}
