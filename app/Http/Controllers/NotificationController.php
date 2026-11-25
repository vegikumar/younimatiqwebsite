<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function index(Request $request)
    {
        $user = $this->currentUser();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $items = $this->notifications->recent($user->id, 30, true);

        return response()->json([
            'unread_count' => $this->notifications->unreadCount($user->id),
            'notifications' => $items->map(fn ($n) => [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'body' => $n->body,
                'action_url' => $n->action_url,
                'read_at' => $n->read_at?->toIso8601String(),
                'created_at' => $n->created_at->diffForHumans(),
            ]),
        ]);
    }

    public function markRead(Request $request, int $id)
    {
        $user = $this->currentUser();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $notification = $user->notifications()->findOrFail($id);
        $notification->markRead();

        return response()->json(['success' => true, 'unread_count' => $this->notifications->unreadCount($user->id)]);
    }

    public function markAllRead(Request $request)
    {
        $user = $this->currentUser();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $this->notifications->markAllRead($user->id);

        return response()->json(['success' => true, 'unread_count' => 0]);
    }

    private function currentUser()
    {
        return auth('admin')->user() ?? auth('tutor')->user() ?? auth('web')->user();
    }
}
