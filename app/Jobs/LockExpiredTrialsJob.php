<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Enrollment;
use App\Http\Middleware\CheckTrialAccess;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class LockExpiredTrialsJob implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        //
    }

    public function handle(): void
    {
        $students = User::where('role', 'student')
            ->where('status', 'active')
            ->get();

        $lockedCount = 0;

        foreach ($students as $student) {
            $hasEnrollments = Enrollment::where('user_id', $student->id)
                ->whereIn('status', ['active', 'completed'])
                ->exists();

            if ($hasEnrollments) {
                continue;
            }

            $trialEndsAt = CheckTrialAccess::resolveTrialEndsAt($student);

            if ($trialEndsAt && now()->greaterThanOrEqualTo($trialEndsAt)) {
                $student->update([
                    'status' => 'suspended',
                    'trial_ends_at' => $student->trial_ends_at ?: $trialEndsAt,
                ]);
                $lockedCount++;
                Log::info("Locked trial student account for {$student->email}. Trial ended at {$trialEndsAt->toDateTimeString()}.");
            }
        }

        if ($lockedCount > 0) {
            Log::info("LockExpiredTrialsJob completed. Locked {$lockedCount} expired trial accounts.");
        }
    }
}
