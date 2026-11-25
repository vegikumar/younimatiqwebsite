<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Course;
use Illuminate\Http\Request;
use App\Support\TutorInertiaProps;
use Illuminate\Support\Facades\Auth;

class QaController extends Controller
{
    public function index()
    {
        $courseIds = Course::where('tutor_id', Auth::guard('tutor')->id())->pluck('id');
        $questions = Question::with(['course', 'user', 'answers'])
            ->whereIn('course_id', $courseIds)
            ->orderBy('created_at', 'desc')
            ->get();

        return \App\Support\InertiaPage::react('Tutor/Qa', [
            'questions' => $questions->map(fn ($q) => TutorInertiaProps::question($q))->values()->all(),
        ]);
    }

    public function answer(Request $request, Question $question)
    {
        $courseIds = Course::where('tutor_id', Auth::guard('tutor')->id())->pluck('id');
        abort_unless($courseIds->contains($question->course_id), 403);

        $request->validate(['body' => 'required|string|max:2000']);

        Answer::create([
            'question_id' => $question->id,
            'user_id' => Auth::guard('tutor')->id(),
            'body' => $request->body,
        ]);

        $question->load('user', 'course');
        if ($question->user) {
            app(\App\Services\EmailService::class)->sendTemplate('qa_answered', $question->user->email, [
                'student_name' => $question->user->name,
                'course_title' => $question->course->title ?? 'your course',
            ]);
        }

        return back()->with('success', 'Answer posted.');
    }
}
