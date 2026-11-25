<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Services\AssignmentService;
use App\Services\StudentApiService;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    use RespondsWithJson;

    public function __construct(
        private AssignmentService $assignmentService,
        private StudentApiService $studentApi,
    ) {
    }

    public function index(Request $request)
    {
        $assignments = $request->user()->assignments()
            ->with(['course', 'submissions' => fn ($q) => $q->where('user_id', $request->user()->id)])
            ->orderBy('due_at')
            ->get()
            ->map(fn ($a) => $this->studentApi->formatAssignmentSummary($a, $request->user()->id));

        return $this->success(['assignments' => $assignments]);
    }

    public function show(Request $request, Assignment $assignment)
    {
        $isAssigned = $request->user()->assignments()->where('assignment_id', $assignment->id)->exists();
        if (!$isAssigned) {
            return $this->error('You are not assigned to this assignment.', 403);
        }

        $submission = AssignmentSubmission::where('assignment_id', $assignment->id)
            ->where('user_id', $request->user()->id)
            ->first();

        return $this->success($this->studentApi->formatAssignmentDetail($assignment->load('course'), $submission));
    }

    public function submit(Request $request, Assignment $assignment)
    {
        $isAssigned = $request->user()->assignments()->where('assignment_id', $assignment->id)->exists();
        if (!$isAssigned) {
            return $this->error('You are not assigned to this assignment.', 403);
        }

        $existingSubmission = AssignmentSubmission::where('assignment_id', $assignment->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($assignment->hasQuestions()) {
            try {
                $result = $this->assignmentService->processSubmissionAnswers($request, $assignment);
            } catch (\InvalidArgumentException $e) {
                return $this->error($e->getMessage(), 422);
            }

            $data = [
                'answers' => $result['answers'],
                'status' => 'submitted',
            ];

            if ($result['can_auto_grade'] && $result['auto_score'] !== null) {
                $data['grade'] = $result['auto_score'];
                $data['status'] = 'graded';
                $data['feedback'] = 'Auto-graded based on your answers.';
            }

            if (!empty($result['earned_xp']) && (!$existingSubmission || $existingSubmission->status !== 'graded')) {
                $user = $request->user();
                $user->xp = ($user->xp ?? 0) + $result['earned_xp'];
                $user->save();
            }

            $submission = AssignmentSubmission::updateOrCreate(
                ['assignment_id' => $assignment->id, 'user_id' => $request->user()->id],
                $data
            );
        } else {
            $request->validate([
                'content' => 'nullable|string|max:5000',
                'submission_file' => 'nullable|file|mimes:pdf,doc,docx,txt,jpg,jpeg,png|max:10240',
            ]);

            if (!$request->content && !$request->hasFile('submission_file')) {
                return $this->error('Please provide text content or upload a file.', 422);
            }

            $data = ['content' => $request->content, 'status' => 'submitted'];
            if ($request->hasFile('submission_file')) {
                $data['file_path'] = $request->file('submission_file')->store('submissions', 'public');
            }

            $earnedXp = (int) ($assignment->xp_reward ?? 0);
            if ($earnedXp > 0 && !$existingSubmission) {
                $user = $request->user();
                $user->xp = ($user->xp ?? 0) + $earnedXp;
                $user->save();
            }

            $submission = AssignmentSubmission::updateOrCreate(
                ['assignment_id' => $assignment->id, 'user_id' => $request->user()->id],
                $data
            );
        }

        return $this->success(
            $this->studentApi->formatAssignmentDetail($assignment->load('course'), $submission->fresh()),
            'Assignment submitted successfully.'
        );
    }
}
