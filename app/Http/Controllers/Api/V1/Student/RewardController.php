<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\StudentReward;
use Illuminate\Http\Request;

class RewardController extends Controller
{
    use RespondsWithJson;

    public function index(Request $request)
    {
        $user = $request->user();

        $rewards = StudentReward::with(['badge', 'tutor:id,name', 'course:id,title,slug'])
            ->where('student_id', $user->id)
            ->latest('awarded_at')
            ->get();

        $badges = $rewards->pluck('badge')->filter()->unique('id')->values();

        return $this->success([
            'xp' => (int) ($user->xp ?? 0),
            'level' => intval(($user->xp ?? 0) / 500) + 1,
            'xp_from_rewards' => (int) $rewards->sum('xp_amount'),
            'badges' => $badges->map(fn ($badge) => [
                'id' => $badge->id,
                'title' => $badge->title,
                'description' => $badge->description,
                'icon' => $badge->badge_icon,
            ])->values(),
            'rewards' => $rewards->map(fn (StudentReward $reward) => [
                'id' => $reward->id,
                'xp_amount' => (int) $reward->xp_amount,
                'compliments' => $reward->compliments ?? [],
                'level_up_tip' => $reward->level_up_tip,
                'is_trial' => (bool) $reward->is_trial,
                'awarded_at' => optional($reward->awarded_at)->toIso8601String(),
                'tutor' => $reward->tutor ? ['id' => $reward->tutor->id, 'name' => $reward->tutor->name] : null,
                'course' => $reward->course ? ['id' => $reward->course->id, 'title' => $reward->course->title, 'slug' => $reward->course->slug] : null,
                'badge' => $reward->badge ? [
                    'id' => $reward->badge->id,
                    'title' => $reward->badge->title,
                    'description' => $reward->badge->description,
                    'icon' => $reward->badge->badge_icon,
                ] : null,
            ])->values(),
        ]);
    }
}
