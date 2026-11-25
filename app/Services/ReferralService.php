<?php

namespace App\Services;

use App\Models\Referral;
use App\Models\SiblingDiscount;
use App\Models\User;
use Illuminate\Support\Str;

class ReferralService
{
    /**
     * Generate or fetch family referral code.
     */
    public function getReferralCode(User $user): string
    {
        $master = Referral::where('referrer_id', $user->id)
            ->whereNull('referred_email')
            ->orderBy('id')
            ->first();
        if ($master) {
            return $master->referral_code;
        }

        // Legacy: share code may live on an attributed row (no separate master).
        $legacy = Referral::where('referrer_id', $user->id)->orderBy('id')->first();
        if ($legacy && ! str_contains($legacy->referral_code, '-')) {
            return $legacy->referral_code;
        }

        $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $user->name), 0, 5)) . rand(10, 99);
        while (Referral::where('referral_code', $code)->exists()) {
            $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $user->name), 0, 5)) . rand(10, 99);
        }

        Referral::create([
            'referrer_id' => $user->id,
            'referral_code' => $code,
            'reward_amount' => 25.00,
        ]);

        return $code;
    }

    /**
     * Attribute a new registrant to a referrer via shared code.
     * Creates a separate referral row (master code row stays for sharing).
     */
    public function attributeReferral(?string $code, User $newUser): ?Referral
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '') {
            return null;
        }

        $master = Referral::where('referral_code', $code)->first();
        if (! $master) {
            return null;
        }

        if ((int) $master->referrer_id === (int) $newUser->id) {
            return null;
        }

        $existing = Referral::where('referrer_id', $master->referrer_id)
            ->where('referred_email', $newUser->email)
            ->first();
        if ($existing) {
            return $existing;
        }

        $attrCode = $master->referral_code . '-' . strtoupper(Str::random(4));
        while (Referral::where('referral_code', $attrCode)->exists()) {
            $attrCode = $master->referral_code . '-' . strtoupper(Str::random(4));
        }

        return Referral::create([
            'referrer_id' => $master->referrer_id,
            'referral_code' => $attrCode,
            'referred_email' => $newUser->email,
            'reward_amount' => $master->reward_amount ?? 25.00,
            'status' => 'pending',
        ]);
    }

    /**
     * Calculate price with 15% Sibling Discount if family has multiple enrolled learners.
     */
    public function applySiblingDiscount(int $parentId, float $originalPrice): array
    {
        $siblingCount = SiblingDiscount::where('parent_id', $parentId)->count();
        if ($siblingCount > 0 || app(SiblingPricingService::class)->familyQualifies($parentId, 0)) {
            $discountAmount = round($originalPrice * 0.15, 2);
            $finalPrice = max(0, $originalPrice - $discountAmount);

            return [
                'has_sibling_discount' => true,
                'discount_percent' => 15,
                'discount_amount' => $discountAmount,
                'final_price' => $finalPrice,
            ];
        }

        return [
            'has_sibling_discount' => false,
            'discount_percent' => 0,
            'discount_amount' => 0.00,
            'final_price' => $originalPrice,
        ];
    }
}
