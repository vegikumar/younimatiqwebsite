<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\LiveSession;
use App\Services\TimezoneService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionBookingController extends Controller
{
    public function store(Request $request, \App\Services\EmailService $emailService)
    {
        $data = $request->validate([
            'live_session_id' => 'required|exists:live_sessions,id',
        ]);

        $session = LiveSession::with('course')->findOrFail($data['live_session_id']);

        $existing = Booking::where('student_id', Auth::id())
            ->where('course_id', $session->course_id)
            ->where('session_type', 'live_session')
            ->where('scheduled_at', $session->start_time)
            ->exists();

        if ($existing) {
            return back()->with('error', 'You already booked this session.');
        }

        Booking::create([
            'student_id' => Auth::id(),
            'tutor_id' => $session->tutor_id,
            'course_id' => $session->course_id,
            'status' => 'active',
            'session_type' => 'live_session',
            'scheduled_at' => $session->start_time,
            'zoom_join_url' => $session->join_url,
            'description' => 'Live session for ' . $session->course->title,
        ]);

        $emailService->notifyAdmins(
            'New Live Session Booked',
            'A student has booked a live session.<br>' .
            'Student: ' . Auth::user()->name . '<br>' .
            'Course: ' . $session->course->title . '<br>' .
            'Scheduled At: ' . TimezoneService::formatForUser($session->start_time)
        );

        return back()->with('success', 'Session booked! Starts at ' . TimezoneService::formatForUser($session->start_time));
    }
}
