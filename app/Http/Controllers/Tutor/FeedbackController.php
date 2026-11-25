<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CampBooking;
use App\Models\ClassFeedback;
use App\Support\TutorInertiaProps;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeedbackController extends Controller
{
    public function create(Request $request)
    {
        $type = $request->query('type', 'regular');
        $bookingId = $request->query('booking_id');
        $tutorId = Auth::guard('tutor')->id();

        if (! $bookingId) {
            return redirect()
                ->route('tutor.classes')
                ->with('error', 'Choose a class from your schedule to submit feedback.');
        }

        if ($type === 'demo') {
            $booking = CampBooking::where('id', $bookingId)->where('tutor_id', $tutorId)->first();
            if (! $booking) {
                return redirect()->route('tutor.classes')->with('error', 'That demo class was not found.');
            }
            $studentName = $booking->child_name ?? 'Student';
        } else {
            $booking = Booking::with('student')->where('id', $bookingId)->where('tutor_id', $tutorId)->first();
            if (! $booking) {
                return redirect()->route('tutor.classes')->with('error', 'That class was not found.');
            }
            $studentName = $booking->student->name ?? 'Student';
        }

        // Check if feedback already submitted
        $existing = ClassFeedback::where('bookable_id', $bookingId)
            ->where('bookable_type', $type === 'demo' ? CampBooking::class : Booking::class)
            ->first();

        if ($existing) {
            return redirect()->route('tutor.classes')->with('error', 'Feedback already submitted for this class.');
        }

        return \App\Support\InertiaPage::react('Tutor/Feedback/Create', TutorInertiaProps::feedbackCreate($booking->id, $type, $studentName));
    }

    public function store(Request $request)
    {
        $type = $request->input('type');
        $bookingId = $request->input('booking_id');
        $tutorId = Auth::guard('tutor')->id();

        if ($type === 'demo') {
            $booking = CampBooking::where('id', $bookingId)->where('tutor_id', $tutorId)->firstOrFail();
            $studentId = null; // No user account
            $bookableType = CampBooking::class;
            
            $request->validate([
                'enjoyed_class' => 'required|string',
                'skill_level' => 'required|string',
                'customer_type_recommendation' => 'required|string',
                'notes' => 'nullable|string',
            ]);
            
            $feedbackData = $request->only(['enjoyed_class', 'skill_level', 'customer_type_recommendation', 'notes']);

        } else {
            $booking = Booking::where('id', $bookingId)->where('tutor_id', $tutorId)->firstOrFail();
            $studentId = $booking->student_id;
            $bookableType = Booking::class;

            $request->validate([
                'progress' => 'required|string',
                'improvement_areas' => 'required|string',
                'behavior' => 'required|string',
                'notes' => 'nullable|string',
            ]);

            $feedbackData = $request->only(['progress', 'improvement_areas', 'behavior', 'notes']);
        }

        ClassFeedback::create([
            'bookable_id' => $booking->id,
            'bookable_type' => $bookableType,
            'tutor_id' => $tutorId,
            'student_id' => $studentId,
            'session_type' => $type,
            'feedback_data' => $feedbackData,
        ]);

        return redirect()->route('tutor.classes')->with('success', 'Feedback submitted successfully.');
    }
}
