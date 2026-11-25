<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ParentClassFeedback;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeedbackController extends Controller
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
            ])->values()->all(),
            'selectedChildId' => $child?->id,
            'storeUrl' => route('parent.feedback.store'),
            'reviewsUrl' => route('parent.reviews'),
            'child' => null,
        ];

        if ($child) {
            $recentClasses = Booking::with(['course:id,title', 'tutor:id,name'])
                ->where('student_id', $child->id)
                ->where('scheduled_at', '<=', now())
                ->whereNotIn('status', ['cancelled'])
                ->orderByDesc('scheduled_at')
                ->take(12)
                ->get();

            $myFeedback = ParentClassFeedback::with(['tutor:id,name', 'booking.course:id,title'])
                ->where('parent_id', $parent->id)
                ->where('student_id', $child->id)
                ->latest()
                ->take(20)
                ->get();

            $payload['child'] = [
                'id' => $child->id,
                'name' => $child->name,
                'recentClasses' => $recentClasses->map(fn (Booking $b) => [
                    'id' => $b->id,
                    'label' => trim(
                        ($b->course?->title ?? 'Class')
                        .' · '
                        .(optional($b->scheduled_at)?->format('M j, Y g:i A') ?? '')
                        .($b->tutor?->name ? ' · '.$b->tutor->name : '')
                    ),
                ])->values()->all(),
                'feedback' => $myFeedback->map(fn (ParentClassFeedback $fb) => [
                    'id' => $fb->id,
                    'rating' => (int) $fb->rating,
                    'class_experience' => $fb->class_experience,
                    'tutor_communication' => $fb->tutor_communication,
                    'would_recommend' => $fb->would_recommend,
                    'comments' => $fb->comments,
                    'tutor_name' => $fb->tutor?->name,
                    'class_title' => $fb->booking?->course?->title,
                    'created_at_label' => optional($fb->created_at)?->format('M j, Y'),
                ])->values()->all(),
            ];
        }

        return \App\Support\InertiaPage::react('Parent/Feedback', $payload);
    }

    public function store(Request $request)
    {
        $parent = Auth::guard('parent')->user();

        $data = $request->validate([
            'student_id' => 'required|exists:users,id',
            'booking_id' => 'nullable|exists:bookings,id',
            'rating' => 'required|integer|min:1|max:5',
            'class_experience' => 'required|string|max:100',
            'tutor_communication' => 'required|string|max:100',
            'would_recommend' => 'required|string|max:50',
            'comments' => 'nullable|string|max:5000',
        ]);

        if (! $parent->children()->where('users.id', $data['student_id'])->exists()) {
            abort(403);
        }

        $tutorId = null;
        if (! empty($data['booking_id'])) {
            $booking = Booking::where('id', $data['booking_id'])
                ->where('student_id', $data['student_id'])
                ->first();

            if (! $booking) {
                return back()->with('error', 'That class was not found for this child.');
            }

            $tutorId = $booking->tutor_id;
        }

        ParentClassFeedback::create([
            'parent_id' => $parent->id,
            'student_id' => $data['student_id'],
            'booking_id' => $data['booking_id'] ?? null,
            'tutor_id' => $tutorId,
            'rating' => $data['rating'],
            'class_experience' => $data['class_experience'],
            'tutor_communication' => $data['tutor_communication'],
            'would_recommend' => $data['would_recommend'],
            'comments' => $data['comments'] ?? null,
        ]);

        return redirect()
            ->route('parent.feedback', ['child' => $data['student_id']])
            ->with('success', 'Class feedback sent to Younimatiq. Course star ratings live under Reviews.');
    }
}
