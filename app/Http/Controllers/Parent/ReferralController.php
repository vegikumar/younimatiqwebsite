<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\SiblingDiscount;
use App\Services\ReferralService;
use Illuminate\Support\Facades\Auth;

class ReferralController extends Controller
{
    public function index(ReferralService $referralService)
    {
        $user = Auth::guard('parent')->user() ?? Auth::user();
        $referralCode = $referralService->getReferralCode($user);

        $referrals = Referral::where('referrer_id', $user->id)
            ->whereNotNull('referred_email')
            ->latest()
            ->get();
        $siblingDiscounts = SiblingDiscount::where('parent_id', $user->id)
            ->with(['primaryStudent', 'siblingStudent'])
            ->get();

        $totalEarned = $referrals->where('status', 'completed')->sum('reward_amount');

        return \App\Support\InertiaPage::react('Parent/Referrals', [
            'referralCode' => $referralCode,
            'totalEarned' => (float) $totalEarned,
            'siblingDiscounts' => $siblingDiscounts->map(fn ($s) => [
                'id' => $s->id,
                'discount_percent' => (float) ($s->discount_percent ?? 15),
                'primary' => $s->primaryStudent?->name,
                'sibling' => $s->siblingStudent?->name,
            ])->values()->all(),
            'referrals' => $referrals->map(fn ($ref) => [
                'id' => $ref->id,
                'referralCode' => $ref->referral_code,
                'referredEmail' => $ref->referred_email,
                'rewardAmount' => (float) ($ref->reward_amount ?? 0),
                'status' => $ref->status,
            ])->values()->all(),
        ]);
    }
}
