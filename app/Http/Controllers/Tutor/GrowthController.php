<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\InterestedLead;
use App\Services\ReEngagementService;
use App\Support\TutorInertiaProps;
use Illuminate\Support\Facades\Auth;

class GrowthController extends Controller
{
    public function index()
    {
        $leads = InterestedLead::where('tutor_id', Auth::guard('tutor')->id())
            ->with(['user', 'course'])
            ->latest()
            ->get();

        return \App\Support\InertiaPage::react('Tutor/Growth', [
            'leads' => $leads->map(fn ($l) => TutorInertiaProps::growthLead($l))->values()->all(),
        ]);
    }

    public function sendNudge(InterestedLead $lead, ReEngagementService $reEngagementService)
    {
        if ($lead->tutor_id !== Auth::guard('tutor')->id()) {
            abort(403);
        }

        $reEngagementService->sendNudge($lead);

        return redirect()->back()->with('success', 'Re-engagement nudge sent to family!');
    }
}
