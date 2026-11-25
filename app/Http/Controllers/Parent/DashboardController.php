<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Booking;
use App\Models\ClassFeedback;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\StudentPortfolio;
use App\Models\User;
use App\Support\AdminInertiaProps;
use App\Support\GradeBand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $parent = Auth::guard('parent')->user();
        $children = $parent->children()->orderBy('name')->get();
        $selectedId = (int) ($request->query('child') ?: ($children->first()?->id ?? 0));
        $child = $children->firstWhere('id', $selectedId) ?: $children->first();

        $payload = [
            'children' => $children->map(fn (User $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'grade' => $c->grade,
                'grade_band' => GradeBand::fromGrade($c->grade),
                'grade_band_label' => GradeBand::label(GradeBand::fromGrade($c->grade)),
            ])->values()->all(),
            'selectedChildId' => $child?->id,
            'child' => null,
        ];

        if ($child) {
            $enrollments = Enrollment::with('course:id,title')
                ->where('user_id', $child->id)
                ->whereIn('status', ['active', 'completed'])
                ->latest()
                ->get();

            $upcoming = Booking::with('course:id,title')
                ->where('student_id', $child->id)
                ->where('scheduled_at', '>=', now())
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->orderByDesc('scheduled_at')
                ->take(8)
                ->get();

            // Hide $0 admin/manual enrollments — parents should not see "USD 0.00".
            $payments = Payment::with('course:id,title')
                ->where('student_id', $child->id)
                ->where('status', 'completed')
                ->where('amount', '>', 0)
                ->latest()
                ->take(10)
                ->get();

            $portfolios = StudentPortfolio::where('user_id', $child->id)
                ->where(function ($q) {
                    $q->where('visibility', 'parents')
                        ->orWhere('visibility', 'public')
                        ->orWhere('status', 'approved');
                })
                ->latest()
                ->take(12)
                ->get();

            $classUpdates = ClassFeedback::with('tutor:id,name')
                ->where('student_id', $child->id)
                ->whereNotNull('shared_with_parent_at')
                ->where('session_type', 'regular')
                ->latest('shared_with_parent_at')
                ->take(8)
                ->get();

            $activities = Assignment::query()
                ->whereHas('students', fn ($q) => $q->where('users.id', $child->id))
                ->latest()
                ->take(8)
                ->get();

            $pivot = $child->pivot;
            $payload['child'] = [
                'id' => $child->id,
                'name' => $child->name,
                'email' => $child->email,
                'grade' => $child->grade,
                'xp' => (int) ($child->xp ?? 0),
                'grade_band_label' => GradeBand::label(GradeBand::fromGrade($child->grade)),
                'ai_assist_allowed' => (bool) ($pivot->ai_assist_allowed ?? true),
                'enrollments' => $enrollments->map(fn ($e) => [
                    'id' => $e->id,
                    'course_title' => $e->course?->title,
                    'progress' => (float) ($e->progress ?? 0),
                    'status' => $e->status,
                ])->values()->all(),
                'upcoming' => $upcoming->map(function ($b) use ($child) {
                    $tz = \App\Services\TimezoneService::resolveForDisplay(
                        $child->timezone ?? null,
                        $b->parent_timezone ?? null,
                        $b->timezone ?? null,
                    );

                    return [
                        'id' => $b->id,
                        'title' => $b->course?->title ?? 'Class',
                        'scheduled_at_label' => $b->scheduled_at
                            ? \App\Services\TimezoneService::formatForUser($b->scheduled_at, $tz, 'M j, Y g:i A')
                            : null,
                    ];
                })->values()->all(),
                'classUpdates' => $classUpdates->map(function (ClassFeedback $fb) {
                    $raw = is_array($fb->feedback_data) ? $fb->feedback_data : [];
                    $highlights = [];
                    foreach ($raw as $question => $answer) {
                        if (in_array((string) $question, ['customer_type_recommendation'], true)) {
                            continue;
                        }
                        $highlights[] = [
                            'label' => AdminInertiaProps::feedbackLabel((string) $question),
                            'value' => is_scalar($answer) ? (string) $answer : json_encode($answer),
                        ];
                    }

                    return [
                        'id' => $fb->id,
                        'tutor_name' => $fb->tutor?->name ?? 'Tutor',
                        'shared_at_label' => optional($fb->shared_with_parent_at)?->format('M j, Y'),
                        'parent_message' => $fb->parent_message,
                        'highlights' => $highlights,
                    ];
                })->values()->all(),
                'postClassActivities' => $activities->map(fn (Assignment $a) => [
                    'id' => $a->id,
                    'title' => $a->title,
                    'description' => $a->description,
                    'due_at_label' => optional($a->due_at)?->format('M j, Y'),
                ])->values()->all(),
                'payments' => $payments->map(fn ($p) => [
                    'id' => $p->id,
                    'course_title' => $p->course?->title,
                    'amount' => (float) $p->amount,
                    'currency' => $p->currency ?? 'USD',
                    'date_label' => optional($p->created_at)?->format('M j, Y'),
                ])->values()->all(),
                'portfolios' => $portfolios->map(fn ($p) => [
                    'id' => $p->id,
                    'project_name' => $p->project_name,
                    'project_url' => $p->project_url,
                    'status' => $p->status ?? 'draft',
                ])->values()->all(),
            ];
        }

        return \App\Support\InertiaPage::react('Parent/Dashboard', $payload);
    }

    public function updateAiConsent(Request $request, User $child)
    {
        $parent = Auth::guard('parent')->user();
        if (! $parent->children()->where('users.id', $child->id)->exists()) {
            abort(403);
        }

        $allowed = $request->boolean('ai_assist_allowed');

        $parent->children()->updateExistingPivot($child->id, [
            'ai_assist_allowed' => $allowed,
        ]);

        return back()->with('success', $allowed
            ? 'AI class summaries enabled for '.$child->name.'.'
            : 'AI class summaries disabled for '.$child->name.'.');
    }
}
