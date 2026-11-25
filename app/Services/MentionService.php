<?php

namespace App\Services;

use App\Models\ClassroomDiscussion;
use App\Models\ClassroomMention;
use App\Models\Enrollment;
use App\Models\User;

class MentionService
{
    public function __construct(
        private EmailService $emailService,
        private NotificationService $notificationService
    ) {}

    /**
     * Parse @mentions in discussion content and dispatch notifications to mentioned students/families.
     */
    public function parseAndNotifyMentions(ClassroomDiscussion $discussion): void
    {
        $content = $discussion->content;

        // Match @Username or @Name patterns (e.g. @John, @Jane_Doe, @"John Doe")
        preg_match_all('/@(["\'])(.*?)\1|@([A-Za-z0-9_\-\.]+)/', $content, $matches);

        $names = array_filter(array_merge($matches[2], $matches[3]));
        if (empty($names)) {
            return;
        }

        // Find enrolled students in this course matching the mentioned names
        $enrolledUserIds = Enrollment::where('course_id', $discussion->course_id)
            ->whereIn('status', ['active', 'completed'])
            ->pluck('user_id');

        $mentionedUsers = User::whereIn('id', $enrolledUserIds)
            ->where(function ($query) use ($names) {
                foreach ($names as $name) {
                    $query->orWhere('name', 'LIKE', '%' . trim($name) . '%');
                }
            })
            ->get();

        foreach ($mentionedUsers as $user) {
            // Avoid duplicate notifications for the same discussion
            $mention = ClassroomMention::firstOrCreate(
                [
                    'discussion_id' => $discussion->id,
                    'mentioned_user_id' => $user->id,
                ],
                [
                    'notified_at' => now(),
                ]
            );

            if ($mention->wasRecentlyCreated) {
                // Email family/parent notification
                $this->emailService->sendTemplate('tutor_mention_notification', $user->email, [
                    'student_name' => $user->name,
                    'tutor_name' => $discussion->author->name ?? 'Your Tutor',
                    'course_title' => $discussion->course->title ?? 'Classroom',
                    'comment_excerpt' => \Illuminate\Support\Str::limit($discussion->content, 150),
                ]);

                // Push notification inside platform
                $this->notificationService->notify(
                    $user->id,
                    "You were mentioned by {$discussion->author->name}",
                    \Illuminate\Support\Str::limit($discussion->content, 100),
                    route('student.courses.player', $discussion->course_id),
                    'info'
                );
            }
        }
    }
}
