<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\ClassRecording;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClassRecordingController extends Controller
{
    public function store(Request $request, Course $course)
    {
        if ($course->tutor_id !== Auth::guard('tutor')->id()) {
            abort(403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'recording_url' => 'required|url',
            'duration_minutes' => 'nullable|integer|min:1',
            'recorded_at' => 'nullable|date',
        ]);

        ClassRecording::create([
            'course_id' => $course->id,
            'title' => $request->title,
            'recording_url' => $request->recording_url,
            'duration_minutes' => $request->duration_minutes,
            'recorded_at' => $request->recorded_at ?? now(),
        ]);

        return redirect()->back()->with('success', 'Post-class recording published to classroom recordings tab!');
    }

    public function destroy(ClassRecording $recording)
    {
        if ($recording->course->tutor_id !== Auth::guard('tutor')->id()) {
            abort(403);
        }

        $recording->delete();
        return redirect()->back()->with('success', 'Recording removed!');
    }
}
