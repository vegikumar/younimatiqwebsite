<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use RespondsWithJson;

    public function __construct(private NotificationService $notifications)
    {
    }

    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $items = $this->notifications->recent($userId, 30, true);

        return $this->success([
            'unread_count' => $this->notifications->unreadCount($userId),
            'notifications' => $items->map(fn ($n) => [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'body' => $n->body,
                'action_url' => $n->action_url,
                'read_at' => $n->read_at?->toIso8601String(),
                'created_at' => $n->created_at->toIso8601String(),
            ]),
        ]);
    }

    public function markRead(Request $request, int $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markRead();

        return $this->success([
            'unread_count' => $this->notifications->unreadCount($request->user()->id),
        ]);
    }

    public function markAllRead(Request $request)
    {
        $this->notifications->markAllRead($request->user()->id);

        return $this->success(['unread_count' => 0]);
    }
}
