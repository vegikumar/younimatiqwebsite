<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\PolicyTraining;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OnboardingPolicyController extends Controller
{
    public function index()
    {
        $tutor = Auth::guard('tutor')->user();
        $completedModules = PolicyTraining::where('tutor_id', $tutor->id)->pluck('module_slug')->toArray();

        return \App\Support\InertiaPage::react('Tutor/PolicyTraining', [
            'policyComplete' => $tutor->hasCompletedPolicyTraining(),
            'completedModules' => $completedModules,
        ]);
    }

    public function completeModule(Request $request)
    {
        $request->validate([
            'module_slug' => 'required|in:content_standards,safety_privacy',
        ]);

        $tutor = Auth::guard('tutor')->user();

        PolicyTraining::firstOrCreate([
            'tutor_id' => $tutor->id,
            'module_slug' => $request->module_slug,
        ], [
            'completed_at' => now(),
        ]);

        $completedCount = PolicyTraining::where('tutor_id', $tutor->id)->count();

        // Mark full policy training as completed if both modules done
        if ($completedCount >= 2) {
            $tutor->update(['policy_training_completed_at' => now()]);
        }

        return redirect()->back()->with('success', 'Policy training module completed!');
    }
}
