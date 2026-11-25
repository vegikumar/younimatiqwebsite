<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TutorAnalyticsService;

class OpsRosterController extends Controller
{
    public function index(TutorAnalyticsService $analyticsService)
    {
        $rosterData = $analyticsService->getOpsRosterData();
        $leaderboard = $rosterData->take(10);

        $mapRow = function ($row) {
            return [
                'name' => $row['tutor']->name,
                'email' => $row['tutor']->email,
                'rating' => $row['rating'],
                'sectionsCount' => $row['sections_count'],
                'learnersTaught' => $row['learners_taught'],
                'punctualityRate' => $row['punctuality_rate'],
                'reEnrollmentRate' => $row['re_enrollment_rate'],
                'monthlyEarnings' => number_format((float) $row['monthly_earnings'], 2),
            ];
        };

        return \App\Support\InertiaPage::react('Admin/Ops/Roster', [
            'leaderboard' => $leaderboard->map($mapRow)->values()->all(),
            'rosterData' => $rosterData->map($mapRow)->values()->all(),
        ]);
    }
}
