<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    use RespondsWithJson;

    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $courseIds = Enrollment::where('user_id', $userId)
            ->whereIn('status', ['active', 'completed'])
            ->pluck('course_id');

        $announcements = Announcement::with(['course:id,title', 'tutor:id,name'])
            ->published()
            ->where(function ($q) use ($courseIds) {
                $q->whereIn('course_id', $courseIds)
                    ->orWhere('is_welcome_post', true);
            })
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $readIds = AnnouncementRead::where('user_id', $userId)
            ->whereIn('announcement_id', $announcements->pluck('id'))
            ->pluck('announcement_id')
            ->flip();

        $items = $announcements->map(function (Announcement $a) use ($readIds) {
            $isRead = $readIds->has($a->id);

            return [
                'id' => $a->id,
                'title' => $a->title,
                'content' => $a->content,
                'video_url' => $a->video_url,
                'is_welcome_post' => (bool) $a->is_welcome_post,
                'course_id' => $a->course_id,
                'course_title' => $a->course?->title,
                'tutor_name' => $a->tutor?->name,
                'read_at' => $isRead ? now()->toIso8601String() : null,
                'is_read' => $isRead,
                'published_at' => ($a->published_at ?? $a->created_at)?->toIso8601String(),
            ];
        });

        return $this->success([
            'unread_count' => $items->where('is_read', false)->count(),
            'announcements' => $items->values()->all(),
        ]);
    }

    public function markRead(Request $request, int $id)
    {
        $userId = $request->user()->id;
        $courseIds = Enrollment::where('user_id', $userId)
            ->whereIn('status', ['active', 'completed'])
            ->pluck('course_id');

        $announcement = Announcement::published()
            ->where(function ($q) use ($courseIds) {
                $q->whereIn('course_id', $courseIds)
                    ->orWhere('is_welcome_post', true);
            })
            ->findOrFail($id);

        AnnouncementRead::updateOrCreate(
            [
                'announcement_id' => $announcement->id,
                'user_id' => $userId,
            ],
            ['read_at' => now()]
        );

        $unread = Announcement::published()
            ->where(function ($q) use ($courseIds) {
                $q->whereIn('course_id', $courseIds)
                    ->orWhere('is_welcome_post', true);
            })
            ->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $userId))
            ->count();

        return $this->success([
            'unread_count' => $unread,
            'announcement_id' => $announcement->id,
        ]);
    }
}
