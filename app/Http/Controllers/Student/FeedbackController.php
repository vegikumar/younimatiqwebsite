<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\StudentClassFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeedbackController extends Controller
{
    public function index()
    {
        $studentId = Auth::id();

        $recentClasses = Booking::with(['course:id,title', 'tutor:id,name'])
            ->where('student_id', $studentId)
            ->where('scheduled_at', '<=', now())
            ->whereNotIn('status', ['cancelled'])
            ->orderByDesc('scheduled_at')
            ->take(12)
            ->get();

        $feedback = StudentClassFeedback::with(['tutor:id,name', 'booking.course:id,title'])
            ->where('student_id', $studentId)
            ->latest()
            ->take(20)
            ->get();

        return \App\Support\InertiaPage::react('Student/Feedback', [
            'storeUrl' => route('student.feedback.store'),
            'reviewsUrl' => route('student.reviews'),
            'recentClasses' => $recentClasses->map(fn (Booking $b) => [
                'id' => $b->id,
                'label' => trim(
                    ($b->course?->title ?? 'Class')
                    .' · '
                    .(optional($b->scheduled_at)?->format('M j, Y g:i A') ?? '')
                    .($b->tutor?->name ? ' · '.$b->tutor->name : '')
                ),
            ])->values()->all(),
            'feedback' => $feedback->map(fn (StudentClassFeedback $fb) => [
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
        ]);
    }

    public function store(Request $request)
    {
        $studentId = Auth::id();

        $data = $request->validate([
            'booking_id' => 'nullable|exists:bookings,id',
            'rating' => 'required|integer|min:1|max:5',
            'class_experience' => 'required|string|max:100',
            'tutor_communication' => 'required|string|max:100',
            'would_recommend' => 'required|string|max:50',
            'comments' => 'nullable|string|max:5000',
        ]);

        $tutorId = null;
        if (! empty($data['booking_id'])) {
            $booking = Booking::where('id', $data['booking_id'])
                ->where('student_id', $studentId)
                ->first();

            if (! $booking) {
                return back()->with('error', 'That class was not found.');
            }

            $tutorId = $booking->tutor_id;
        }

        StudentClassFeedback::create([
            'student_id' => $studentId,
            'booking_id' => $data['booking_id'] ?? null,
            'tutor_id' => $tutorId,
            'rating' => $data['rating'],
            'class_experience' => $data['class_experience'],
            'tutor_communication' => $data['tutor_communication'],
            'would_recommend' => $data['would_recommend'],
            'comments' => $data['comments'] ?? null,
        ]);

        return redirect()
            ->route('student.feedback')
            ->with('success', 'Class feedback sent. Course star ratings live under Reviews.');
    }
}
