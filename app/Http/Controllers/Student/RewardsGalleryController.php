<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\StudentReward;
use Illuminate\Support\Facades\Auth;

class RewardsGalleryController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $rewards = StudentReward::with(['badge', 'tutor', 'course'])
            ->where('student_id', $userId)
            ->latest('awarded_at')
            ->paginate(40);

        $badges = $rewards->getCollection()->pluck('badge')->filter()->unique('id')->values();

        return \App\Support\InertiaPage::react('Student/Rewards', [
            'rewards' => $rewards->getCollection()->map(fn ($r) => [
                'id' => $r->id,
                'xp_amount' => (int) ($r->xp_amount ?? 0),
                'compliment' => $r->compliment,
                'awarded_at_label' => optional($r->awarded_at)?->format('M j, Y'),
                'badge' => $r->badge ? [
                    'id' => $r->badge->id,
                    'title' => $r->badge->title,
                    'icon' => $r->badge->badge_icon,
                ] : null,
                'tutor_name' => $r->tutor?->name,
                'course_title' => $r->course?->title,
            ])->values()->all(),
            'badges' => $badges->map(fn ($b) => [
                'id' => $b->id,
                'title' => $b->title,
                'icon' => $b->badge_icon,
            ])->values()->all(),
            'stats' => [
                'total_xp' => (int) StudentReward::where('student_id', $userId)->sum('xp_amount'),
                'count' => StudentReward::where('student_id', $userId)->count(),
                'badges' => $badges->count(),
            ],
            'pagination' => [
                'current_page' => $rewards->currentPage(),
                'last_page' => $rewards->lastPage(),
            ],
        ]);
    }
}
