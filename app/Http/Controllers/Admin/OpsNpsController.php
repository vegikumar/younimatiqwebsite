<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TutorAnalyticsService;

class OpsNpsController extends Controller
{
    public function index(TutorAnalyticsService $analyticsService)
    {
        $roster = $analyticsService->getOpsRosterData();

        // Flag at-risk tutors (NPS below 8.0 or rating below 4.5)
        $atRiskTutors = $roster->filter(function ($item) {
            return $item['rating'] < 4.5 || ($item['tutor']->nps_score ?? 9.5) < 8.0;
        });

        $avgNps = round(User::where('role', 'tutor')->avg('nps_score') ?? 9.5, 1);

        return \App\Support\InertiaPage::react('Admin/Ops/Nps', [
            'avgNps' => $avgNps,
            'atRiskTutors' => $atRiskTutors->map(fn ($item) => [
                'name' => $item['tutor']->name,
                'email' => $item['tutor']->email,
                'rating' => $item['rating'],
            ])->values()->all(),
            'roster' => $roster->map(function ($row) {
                $nps = $row['tutor']->nps_score ?? 9.5;
                $isAtRisk = $row['rating'] < 4.5 || $nps < 8.0;

                return [
                    'name' => $row['tutor']->name,
                    'rating' => $row['rating'],
                    'npsScore' => $nps,
                    'punctualityRate' => $row['punctuality_rate'],
                    'reEnrollmentRate' => $row['re_enrollment_rate'],
                    'isAtRisk' => $isAtRisk,
                ];
            })->values()->all(),
        ]);
    }
}
