<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CampBooking;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\InterestedLead;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', '30');
        $since = now()->subDays((int) $period);

        $revenue = Payment::where('status', 'completed')
            ->where('created_at', '>=', $since)
            ->sum('amount');

        $enrollments = Enrollment::where('created_at', '>=', $since)->count();
        $campLeads = CampBooking::where('created_at', '>=', $since)->count();
        $newStudents = User::where('role', 'student')
            ->where('created_at', '>=', $since)
            ->count();

        $revenueByGateway = Payment::selectRaw('gateway, SUM(CASE WHEN status="completed" THEN amount ELSE 0 END) as total')
            ->where('created_at', '>=', $since)
            ->groupBy('gateway')
            ->get();

        $topCourses = Course::withCount(['enrollments' => function ($query) use ($since) {
            $query->where('created_at', '>=', $since);
        }])
            ->orderBy('enrollments_count', 'desc')
            ->take(10)
            ->get();

        // Funnel: register → trial → checkout started → paid → enrolled
        $registered = User::where('role', 'student')->where('created_at', '>=', $since)->count();
        $onTrial = User::where('role', 'student')
            ->where('created_at', '>=', $since)
            ->whereNotNull('trial_ends_at')
            ->count();
        $checkoutStarted = Payment::where('created_at', '>=', $since)->count();
        $paid = Payment::where('status', 'completed')->where('created_at', '>=', $since)->count();
        $abandoned = Payment::where('status', 'pending')
            ->where('created_at', '>=', $since)
            ->where('created_at', '<=', now()->subHour())
            ->count();
        $interested = InterestedLead::where('created_at', '>=', $since)->count();
        $trialConverted = User::where('role', 'student')
            ->where('created_at', '>=', $since)
            ->whereNotNull('trial_ends_at')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('payments')
                    ->whereColumn('payments.student_id', 'users.id')
                    ->where('payments.status', 'completed');
            })
            ->count();

        $pct = fn (int $part, int $whole) => $whole > 0 ? round(($part / $whole) * 100, 1) : 0.0;

        $funnel = [
            ['key' => 'registered', 'label' => 'New students', 'count' => $registered, 'rate' => 100.0],
            ['key' => 'trial', 'label' => 'On trial', 'count' => $onTrial, 'rate' => $pct($onTrial, max(1, $registered))],
            ['key' => 'interest', 'label' => 'Course interest leads', 'count' => $interested, 'rate' => $pct($interested, max(1, $registered))],
            ['key' => 'checkout', 'label' => 'Checkout started', 'count' => $checkoutStarted, 'rate' => $pct($checkoutStarted, max(1, $registered))],
            ['key' => 'paid', 'label' => 'Paid', 'count' => $paid, 'rate' => $pct($paid, max(1, $checkoutStarted))],
            ['key' => 'enrolled', 'label' => 'Enrollments', 'count' => $enrollments, 'rate' => $pct($enrollments, max(1, $paid))],
            ['key' => 'abandoned', 'label' => 'Abandoned checkouts', 'count' => $abandoned, 'rate' => $pct($abandoned, max(1, $checkoutStarted))],
            ['key' => 'trial_paid', 'label' => 'Trial → paid', 'count' => $trialConverted, 'rate' => $pct($trialConverted, max(1, $onTrial))],
        ];

        return \App\Support\InertiaPage::react('Admin/Reports/Index', [
            'revenue' => (float) $revenue,
            'enrollments' => (int) $enrollments,
            'campLeads' => (int) $campLeads,
            'newStudents' => (int) $newStudents,
            'revenueByGateway' => $revenueByGateway->map(fn ($row) => [
                'gateway' => $row->gateway,
                'total' => (float) $row->total,
            ])->values()->all(),
            'topCourses' => $topCourses->map(fn (Course $c) => [
                'id' => $c->id,
                'title' => $c->title,
                'enrollments_count' => (int) $c->enrollments_count,
            ])->values()->all(),
            'funnel' => $funnel,
            'period' => (string) $period,
        ]);
    }
}
