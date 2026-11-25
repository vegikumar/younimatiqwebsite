<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;

class StudentKanbanController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');

        $students = User::where('role', 'student')
            ->when($search, fn ($q) => $q->where(function ($qq) use ($search) {
                $qq->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('parents', fn ($pq) => $pq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            }))
            ->with(['parents:id,name,email'])
            ->withCount(['enrollments'])
            ->orderByDesc('created_at')
            ->get();

        // Manual admin enrollments often use $0 completed payments — still count as paid/enrolled.
        $paidStudentIds = Payment::where('status', 'completed')
            ->pluck('student_id')
            ->unique()
            ->flip();

        $enrolledStudentIds = Enrollment::query()
            ->whereIn('status', ['active', 'completed'])
            ->pluck('user_id')
            ->unique()
            ->flip();

        $columns = [
            'pending' => collect(),
            'active_trial' => collect(),
            'active_enrolled' => collect(),
            'trial_expiring' => collect(),
            'trial_expired' => collect(),
            'suspended' => collect(),
        ];

        foreach ($students as $student) {
            $bucket = $this->bucketFor($student, $paidStudentIds, $enrolledStudentIds);
            $columns[$bucket]->push([
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'status' => $student->status,
                'timezone' => $student->timezone,
                'timezone_label' => \App\Services\TimezoneService::shortLabel($student->timezone),
                'trial_ends_at' => optional($student->trial_ends_at)?->toIso8601String(),
                'trial_label' => optional($student->trial_ends_at)?->format('M j, Y'),
                'parents' => $student->parents->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'email' => $p->email,
                ])->values()->all(),
            ]);
        }

        $stats = [
            'total' => $students->count(),
            'active' => $students->where('status', 'active')->count(),
            'suspended' => $students->where('status', 'suspended')->count(),
            'pending' => $students->where('status', 'pending')->count(),
            'trial_expiring' => $columns['trial_expiring']->count(),
        ];

        return \App\Support\InertiaPage::react('Admin/Students', [
            'columns' => $columns,
            'search' => $search,
            'stats' => $stats,
            'timezones' => \App\Services\TimezoneService::commonOptionsForSelect(),
        ]);
    }

    public function updateStatus(Request $request, User $user)
    {
        abort_unless($user->role === 'student', 404);
        $data = $request->validate([
            'status' => 'required|in:active,pending,suspended',
        ]);
        $user->update(['status' => $data['status']]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'status' => $user->status]);
        }

        return back()->with('success', 'Student status updated.');
    }

    private function bucketFor(User $student, $paidStudentIds, $enrolledStudentIds): string
    {
        if ($student->status === 'pending') {
            return 'pending';
        }

        $hasPaidOrEnrolled = $paidStudentIds->has($student->id)
            || $enrolledStudentIds->has($student->id)
            || ((int) ($student->enrollments_count ?? 0) > 0);

        $trialEnds = $student->trial_ends_at;
        $trialActive = $trialEnds && $trialEnds->isFuture();

        // Enrolled / paid students always belong in Active (enrolled/paid).
        if ($hasPaidOrEnrolled) {
            return 'active_enrolled';
        }

        // Active/future trial wins over a stale suspended flag after +7d extend.
        if ($trialActive) {
            if ($trialEnds->lte(now()->addDays(7))) {
                return 'trial_expiring';
            }

            return 'active_trial';
        }

        if ($student->status === 'suspended') {
            return 'suspended';
        }

        return 'trial_expired';
    }
}
