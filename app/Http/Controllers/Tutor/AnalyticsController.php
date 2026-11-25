<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\TutorPayout;
use App\Services\TutorAnalyticsService;
use App\Support\TutorInertiaProps;
use Illuminate\Support\Facades\Auth;

class AnalyticsController extends Controller
{
    public function index(TutorAnalyticsService $analyticsService)
    {
        $tutorId = Auth::guard('tutor')->id();
        $metrics = $analyticsService->getMetrics($tutorId);

        $recentPayouts = TutorPayout::where('tutor_id', $tutorId)->latest()->get();

        return \App\Support\InertiaPage::react('Tutor/Analytics', [
            'metrics' => TutorInertiaProps::metrics($metrics),
        ]);
    }

    public function payouts()
    {
        $tutorId = Auth::guard('tutor')->id();
        $payouts = TutorPayout::where('tutor_id', $tutorId)->latest()->get();

        return \App\Support\InertiaPage::react('Tutor/Payouts', [
            'payouts' => $payouts->map(fn ($p) => TutorInertiaProps::payout($p))->values()->all(),
        ]);
    }
}
