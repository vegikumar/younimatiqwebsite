<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\ClassFeedback;
use App\Models\ParentClassFeedback;
use App\Models\StudentClassFeedback;
use App\Services\EmailService;
use App\Support\AdminInertiaProps;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        $filterType = $request->query('type');

        if ($filterType === 'parent') {
            $paginator = ParentClassFeedback::with(['parent', 'student', 'tutor', 'booking.course'])
                ->orderByDesc('created_at')
                ->paginate(15);

            return \App\Support\InertiaPage::react('Admin/Feedback/Index', [
                'source' => 'parent',
                'feedbacks' => AdminInertiaProps::paginate(
                    $paginator,
                    fn (ParentClassFeedback $fb) => AdminInertiaProps::parentFeedbackListItem($fb)
                ),
                'filterType' => 'parent',
            ]);
        }

        if ($filterType === 'student') {
            $paginator = StudentClassFeedback::with(['student', 'tutor', 'booking.course'])
                ->orderByDesc('created_at')
                ->paginate(15);

            return \App\Support\InertiaPage::react('Admin/Feedback/Index', [
                'source' => 'student',
                'feedbacks' => AdminInertiaProps::paginate(
                    $paginator,
                    fn (StudentClassFeedback $fb) => AdminInertiaProps::studentFeedbackListItem($fb)
                ),
                'filterType' => 'student',
            ]);
        }

        $query = ClassFeedback::with(['tutor', 'student']);

        if (in_array($filterType, ['demo', 'regular'], true)) {
            $query->where('session_type', $filterType);
        }

        $paginator = $query->orderBy('created_at', 'desc')->paginate(15);

        return \App\Support\InertiaPage::react('Admin/Feedback/Index', [
            'source' => 'tutor',
            'feedbacks' => AdminInertiaProps::paginate($paginator, fn ($fb) => AdminInertiaProps::feedbackListItem($fb)),
            'filterType' => $filterType,
        ]);
    }

    public function showParent(ParentClassFeedback $parentFeedback)
    {
        $parentFeedback->load(['parent', 'student', 'tutor', 'booking.course']);

        return \App\Support\InertiaPage::react('Admin/Feedback/ParentShow', [
            'feedback' => AdminInertiaProps::parentFeedbackDetail($parentFeedback),
        ]);
    }

    public function updateParent(Request $request, ParentClassFeedback $parentFeedback)
    {
        $request->validate([
            'admin_notes' => 'nullable|string|max:5000',
        ]);

        $parentFeedback->update([
            'admin_notes' => $request->input('admin_notes'),
            'reviewed_at' => $parentFeedback->reviewed_at ?? now(),
        ]);

        return redirect()
            ->route('admin.feedback.parent.show', $parentFeedback)
            ->with('success', 'Parent feedback notes saved.');
    }

    public function showStudent(StudentClassFeedback $studentFeedback)
    {
        $studentFeedback->load(['student', 'tutor', 'booking.course']);

        return \App\Support\InertiaPage::react('Admin/Feedback/ParentShow', [
            'feedback' => AdminInertiaProps::studentFeedbackDetail($studentFeedback),
        ]);
    }

    public function updateStudent(Request $request, StudentClassFeedback $studentFeedback)
    {
        $request->validate([
            'admin_notes' => 'nullable|string|max:5000',
        ]);

        $studentFeedback->update([
            'admin_notes' => $request->input('admin_notes'),
            'reviewed_at' => $studentFeedback->reviewed_at ?? now(),
        ]);

        return redirect()
            ->route('admin.feedback.student.show', $studentFeedback)
            ->with('success', 'Student feedback notes saved.');
    }

    public function show(ClassFeedback $feedback)
    {
        $feedback->load(['tutor', 'student.parents', 'bookable']);

        return \App\Support\InertiaPage::react('Admin/Feedback/Show', [
            'feedback' => AdminInertiaProps::feedbackDetail($feedback),
        ]);
    }

    public function update(Request $request, ClassFeedback $feedback)
    {
        $request->validate([
            'customer_type' => 'nullable|string',
            'admin_notes' => 'nullable|string',
        ]);

        $feedback->update([
            'customer_type' => $request->input('customer_type'),
            'admin_notes' => $request->input('admin_notes'),
        ]);

        return redirect()->route('admin.feedback.show', $feedback)->with('success', 'Feedback updated successfully.');
    }

    public function sendToParent(Request $request, ClassFeedback $feedback, EmailService $emailService)
    {
        $request->validate([
            'parent_message' => 'nullable|string|max:5000',
        ]);

        if ($feedback->session_type === 'demo' || ! $feedback->student_id) {
            return redirect()
                ->route('admin.feedback.show', $feedback)
                ->with('error', 'Only regular class feedback with a linked student can be sent to parents.');
        }

        $feedback->loadMissing(['tutor', 'student.parents']);
        $student = $feedback->student;
        if (! $student) {
            return redirect()
                ->route('admin.feedback.show', $feedback)
                ->with('error', 'Student account not found for this feedback.');
        }

        $parents = $student->parents;
        if ($parents->isEmpty()) {
            return redirect()
                ->route('admin.feedback.show', $feedback)
                ->with('error', 'No parent is linked to this student yet. Link a parent first, then send.');
        }

        $parentMessage = trim((string) $request->input('parent_message', ''));
        $items = $this->parentFacingItems($feedback);
        $activities = $this->recentPostClassActivities($student->id);

        $bodyLines = [
            'Hi ' . ($parents->first()->name ?? 'Parent') . ',',
            '',
            'Here is a post-class update for ' . $student->name . ' from Younimatiq.',
            '',
        ];

        if ($parentMessage !== '') {
            $bodyLines[] = $parentMessage;
            $bodyLines[] = '';
        }

        $bodyLines[] = 'Class update (from ' . ($feedback->tutor?->name ?? 'your tutor') . '):';
        foreach ($items as $item) {
            $bodyLines[] = '• ' . $item['question'] . ': ' . $item['answer'];
        }

        if ($activities->isNotEmpty()) {
            $bodyLines[] = '';
            $bodyLines[] = 'Post-class activities:';
            foreach ($activities as $activity) {
                $line = '• ' . $activity['title'];
                if (! empty($activity['due_at_label'])) {
                    $line .= ' (due ' . $activity['due_at_label'] . ')';
                }
                $bodyLines[] = $line;
            }
        }

        $bodyLines[] = '';
        $bodyLines[] = 'You can also see this update anytime on your Parent Dashboard.';
        $bodyLines[] = url('/parent/dashboard?child=' . $student->id);
        $bodyLines[] = '';
        $bodyLines[] = '— Team Younimatiq';

        $body = nl2br(e(implode("\n", $bodyLines)), false);
        // Keep the dashboard link clickable in HTML email.
        $dashboardUrl = e(url('/parent/dashboard?child=' . $student->id));
        $body = str_replace(
            $dashboardUrl,
            '<a href="' . $dashboardUrl . '">' . $dashboardUrl . '</a>',
            $body
        );
        $subject = 'Post-class update for ' . $student->name;

        $sent = 0;
        foreach ($parents as $parent) {
            if (! empty($parent->email) && $emailService->sendCustomEmail($parent->email, $subject, $body)) {
                $sent++;
            }
        }

        if ($sent === 0) {
            return redirect()
                ->route('admin.feedback.show', $feedback)
                ->with('error', 'Could not send email to linked parents. Please check parent email addresses.');
        }

        $feedback->update([
            'parent_message' => $parentMessage !== '' ? $parentMessage : $feedback->parent_message,
            'shared_with_parent_at' => now(),
            'shared_by' => Auth::guard('admin')->id() ?? Auth::id(),
        ]);

        return redirect()
            ->route('admin.feedback.show', $feedback)
            ->with('success', "Post-class update sent to {$sent} parent(s) and published on the Parent Dashboard.");
    }

    private function parentFacingItems(ClassFeedback $feedback): array
    {
        $raw = is_array($feedback->feedback_data) ? $feedback->feedback_data : [];
        $items = [];
        $skip = ['customer_type_recommendation'];

        foreach ($raw as $question => $answer) {
            if (in_array((string) $question, $skip, true)) {
                continue;
            }
            $items[] = [
                'question' => AdminInertiaProps::feedbackLabel((string) $question),
                'answer' => is_scalar($answer) ? (string) $answer : json_encode($answer),
            ];
        }

        return $items;
    }

    private function recentPostClassActivities(int $studentId)
    {
        return Assignment::query()
            ->whereHas('students', fn ($q) => $q->where('users.id', $studentId))
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (Assignment $a) => [
                'title' => $a->title,
                'due_at_label' => optional($a->due_at)?->format('M j, Y'),
            ]);
    }
}
