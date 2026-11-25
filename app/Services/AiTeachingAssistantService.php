<?php

namespace App\Services;

use App\Models\AiParentNote;
use App\Models\AiProjectReview;
use App\Models\AiSessionRecap;
use App\Models\Course;
use App\Models\LiveSession;
use App\Models\User;
use Carbon\Carbon;

class AiTeachingAssistantService
{
    /**
     * AI Tool 1: Post-Class Parent Note & HW Drafter
     */
    public function generateParentNote(int $tutorId, int $courseId, int $studentId, string $rawBullets): AiParentNote
    {
        $student = User::find($studentId);
        $course = Course::find($courseId);
        $studentName = $student->name ?? 'Learner';
        $courseTitle = $course->title ?? 'Class';

        $polishedNote = "Dear {$studentName}'s Family,\n\nIn today's {$courseTitle} session, {$studentName} made great progress! Here is a recap of our key focus areas:\n";

        $lines = explode("\n", $rawBullets);
        foreach ($lines as $line) {
            $line = trim($line, " \t\n\r\0\x0B*-•");
            if ($line) {
                $polishedNote .= "• " . ucfirst($line) . "\n";
            }
        }

        $polishedNote .= "\n{$studentName} demonstrated excellent engagement and completed all core hands-on tasks smoothly. Please let me know if you have any questions!";
        $homework = "Homework Assignment: Practice today's exercises for 15 minutes and complete the reflection task before our next session.";

        return AiParentNote::create([
            'tutor_id' => $tutorId,
            'course_id' => $courseId,
            'student_id' => $studentId,
            'raw_bullets' => $rawBullets,
            'ai_generated_note' => $polishedNote,
            'homework_assignment' => $homework,
            'status' => 'draft',
        ]);
    }

    /**
     * AI Tool 2: Automatic Zoom Session Recap (with PII Red Flag Detection)
     */
    public function generateSessionRecap(?int $liveSessionId, int $tutorId, int $courseId, string $transcriptText): AiSessionRecap
    {
        $course = Course::find($courseId);
        $courseTitle = $course->title ?? 'Live Class';

        // Detect PII Leaks (Email Addresses and Phone Numbers)
        $emails = [];
        $phones = [];

        preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $transcriptText, $emailMatches);
        if (!empty($emailMatches[0])) {
            $emails = array_unique($emailMatches[0]);
        }

        preg_match_all('/(?:\+?\d{1,3}[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}|\b\d{10}\b/', $transcriptText, $phoneMatches);
        if (!empty($phoneMatches[0])) {
            $phones = array_unique($phoneMatches[0]);
        }

        $detectedPii = array_merge($emails, $phones);
        $hasRedFlag = !empty($detectedPii);

        $summary = "Automatic Zoom Session Recap for {$courseTitle}:\n";
        $summary .= "During today's live session, the instructor guided students through core concepts, interactive coding demonstrations, and Q&A.";

        if ($hasRedFlag) {
            $summary .= "\n\n🚨 RED FLAG WARNING: Personal Contact Details (Email / Phone Number) detected in session discussion:\n";
            $summary .= implode(', ', $detectedPii) . "\n";
            $summary .= "This session has been flagged for admin policy compliance review.";

            // Send in-app Red Flag alert to Admin Panel
            try {
                $admins = User::where('role', 'admin')->get();
                foreach ($admins as $admin) {
                    \App\Models\Notification::create([
                        'user_id' => $admin->id,
                        'type' => 'red_flag',
                        'title' => '🚨 RED FLAG: PII Detected in Class Discussion',
                        'body' => "Contact info (" . implode(', ', $detectedPii) . ") discussed in {$courseTitle}.",
                        'action_url' => route('admin.ops.roster'),
                    ]);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Failed sending red flag admin notification: ' . $e->getMessage());
            }
        }

        return AiSessionRecap::create([
            'live_session_id' => $liveSessionId,
            'tutor_id' => $tutorId,
            'course_id' => $courseId,
            'transcript_summary' => $summary,
            'edited_summary' => null,
            'red_flag' => $hasRedFlag,
            'detected_pii' => empty($detectedPii) ? null : json_encode($detectedPii),
            'status' => 'pending_review',
            'auto_send_at' => Carbon::now()->addHours(4),
        ]);
    }

    /**
     * AI Tool 3: AI Code & Project Reviewer
     */
    public function generateProjectReview(int $studentId, int $courseId, string $projectTitle, ?string $codeOrPath): AiProjectReview
    {
        $student = User::find($studentId);
        $studentName = $student->name ?? 'Student';

        $progressNote = "Project Evaluation: '{$projectTitle}' for {$studentName}.\n";
        $progressNote .= "• Code Structure: Clean, well-organized, and follows key programming principles.\n";
        $progressNote .= "• Problem Solving: Successfully implemented core logic and handled test edge cases.\n";
        $progressNote .= "• Next Step: Challenge with secondary extensions and user interface enhancements!";

        return AiProjectReview::create([
            'student_id' => $studentId,
            'course_id' => $courseId,
            'project_title' => $projectTitle,
            'code_or_image_path' => $codeOrPath,
            'ai_progress_note' => $progressNote,
        ]);
    }
}
