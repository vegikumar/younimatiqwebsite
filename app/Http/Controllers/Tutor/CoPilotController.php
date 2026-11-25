<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\StudentStickingPoint;
use App\Services\CoPilotService;
use App\Support\TutorInertiaProps;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CoPilotController extends Controller
{
    public function show(Course $course, CoPilotService $coPilotService)
    {
        if ($course->tutor_id !== Auth::guard('tutor')->id()) {
            abort(403);
        }

        $coPilotData = $coPilotService->getCoPilotPrep($course->id);

        return \App\Support\InertiaPage::react('Tutor/Copilot', [
            'course' => TutorInertiaProps::copilotCourse($course),
            'coPilotData' => $coPilotData,
        ]);
    }

    public function updateStickingPoint(Request $request, Course $course)
    {
        if ($course->tutor_id !== Auth::guard('tutor')->id()) {
            abort(403);
        }

        $request->validate([
            'student_id' => 'required|exists:users,id',
            'last_sticking_point' => 'nullable|string',
            'tutor_prep_notes' => 'nullable|string',
        ]);

        StudentStickingPoint::updateOrCreate(
            [
                'student_id' => $request->student_id,
                'course_id' => $course->id,
            ],
            [
                'last_sticking_point' => $request->last_sticking_point,
                'tutor_prep_notes' => $request->tutor_prep_notes,
            ]
        );

        return redirect()->back()->with('success', 'Co-Pilot prep notes updated!');
    }
}
