<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HrAuditEvent;
use App\Models\TutorMatchSuggestion;
use App\Services\TutorMatchingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MatchSuggestionController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'open');

        $query = TutorMatchSuggestion::query()
            ->with([
                'tutor:id,name,email,nps_score',
                'course:id,title',
                'booking:id,student_id,course_id,scheduled_at,status,tutor_id',
                'booking.student:id,name,email',
            ])
            ->latest();

        if ($status === 'open') {
            $query->whereIn('status', ['pending', 'suggested']);
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        $paginator = $query->paginate(30)->withQueryString();

        $suggestions = $paginator->getCollection()->map(function (TutorMatchSuggestion $s) {
            return [
                'id' => $s->id,
                'status' => $s->status,
                'reason' => $s->reason,
                'match_score' => $s->match_score,
                'score_breakdown' => $s->score_breakdown,
                'created_at' => optional($s->created_at)->toIso8601String(),
                'tutor' => $s->tutor ? [
                    'id' => $s->tutor->id,
                    'name' => $s->tutor->name,
                    'email' => $s->tutor->email,
                    'nps' => $s->tutor->nps_score,
                ] : null,
                'course' => $s->course ? [
                    'id' => $s->course->id,
                    'title' => $s->course->title,
                ] : null,
                'booking' => $s->booking ? [
                    'id' => $s->booking->id,
                    'scheduled_at' => optional($s->booking->scheduled_at)->toIso8601String(),
                    'status' => $s->booking->status,
                    'has_tutor' => (bool) $s->booking->tutor_id,
                    'student' => $s->booking->student ? [
                        'id' => $s->booking->student->id,
                        'name' => $s->booking->student->name,
                        'email' => $s->booking->student->email,
                    ] : null,
                ] : null,
            ];
        })->values()->all();

        $counts = [
            'open' => TutorMatchSuggestion::whereIn('status', ['pending', 'suggested'])->count(),
            'assigned' => TutorMatchSuggestion::where('status', 'assigned')->count(),
            'dismissed' => TutorMatchSuggestion::where('status', 'dismissed')->count(),
            'all' => TutorMatchSuggestion::count(),
        ];

        $recentAudit = HrAuditEvent::query()
            ->whereIn('event_type', ['match_suggest', 'match_assign', 'match_dismiss', 'skill_grant', 'training_approve'])
            ->with(['actor:id,name', 'subjectUser:id,name'])
            ->latest()
            ->limit(12)
            ->get()
            ->map(fn (HrAuditEvent $e) => [
                'id' => $e->id,
                'event_type' => $e->event_type,
                'actor' => $e->actor?->name,
                'subject' => $e->subjectUser?->name,
                'payload' => $e->payload,
                'created_at' => optional($e->created_at)->toIso8601String(),
            ])
            ->all();

        return \App\Support\InertiaPage::react('Admin/MatchSuggestions/Index', [
            'suggestions' => $suggestions,
            'filters' => ['status' => $status],
            'counts' => $counts,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
            'recentAudit' => $recentAudit,
        ]);
    }

    public function assign(TutorMatchSuggestion $suggestion, TutorMatchingService $matcher)
    {
        if (! in_array($suggestion->status, ['pending', 'suggested'], true)) {
            return back()->with('error', 'Suggestion is no longer open.');
        }
        if (! $suggestion->booking_id) {
            return back()->with('error', 'Course-level suggestions need a booking before assign. Open the booking matcher from enrollments.');
        }

        $matcher->assignSuggestion($suggestion, Auth::guard('admin')->user());

        return back()->with('success', 'Tutor assigned from match suggestion.');
    }

    public function dismiss(TutorMatchSuggestion $suggestion, TutorMatchingService $matcher)
    {
        $matcher->dismissSuggestion($suggestion, Auth::guard('admin')->user());

        return back()->with('success', 'Suggestion dismissed.');
    }
}
