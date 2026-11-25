<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\ActivityLink;
use App\Models\ClassroomGameTutor;
use App\Support\ClassroomGames;
use Illuminate\Support\Facades\Auth;

class GameController extends Controller
{
    public function index()
    {
        $tutorId = Auth::guard('tutor')->id();

        $assignedKeys = ClassroomGameTutor::query()
            ->where('tutor_id', $tutorId)
            ->pluck('game_key')
            ->all();

        $assignedBuiltIn = collect(ClassroomGames::all())
            ->filter(fn (array $game) => in_array($game['key'], $assignedKeys, true))
            ->map(function (array $game) {
                return [
                    ...$game,
                    'kind' => 'assigned',
                    'preview_url' => route('tutor.games.preview', $game['key']),
                    'share_url' => route('tutor.games.preview', $game['key']),
                ];
            })
            ->values()
            ->all();

        $assignedLinks = ActivityLink::query()
            ->where('status', 'published')
            ->where('is_active', true)
            ->where(function ($q) use ($tutorId, $assignedKeys) {
                $q->whereHas('tutors', fn ($t) => $t->where('users.id', $tutorId));
                $uploadKeys = collect($assignedKeys)->filter(fn ($k) => ClassroomGames::isUploadKey($k));
                if ($uploadKeys->isNotEmpty()) {
                    $ids = $uploadKeys->map(fn ($k) => (int) str_replace('upload-', '', $k))->all();
                    $q->orWhereIn('id', $ids);
                }
            })
            ->orderByDesc('id')
            ->get()
            ->map(function (ActivityLink $link) {
                $key = ClassroomGames::uploadKey($link->id);

                return [
                    'key' => $key,
                    'kind' => 'assigned',
                    'title' => $link->title,
                    'summary' => trim($link->description ?: 'Activity link assigned by admin.'),
                    'subject' => $link->subject,
                    'grade' => $link->grade,
                    'preview_url' => route('tutor.games.preview', $key),
                    'share_url' => $link->publicUrl(),
                    'url' => $link->publicUrl(),
                ];
            })
            ->values()
            ->all();

        // All published links remain openable by URL even if not assigned.
        $allLinks = ActivityLink::query()
            ->where('status', 'published')
            ->where('is_active', true)
            ->orderByDesc('id')
            ->get()
            ->map(function (ActivityLink $link) {
                $key = ClassroomGames::uploadKey($link->id);

                return [
                    'key' => $key,
                    'kind' => 'link',
                    'title' => $link->title,
                    'summary' => trim($link->description ?: 'Open via link anytime (login as tutor).'),
                    'subject' => $link->subject,
                    'grade' => $link->grade,
                    'preview_url' => route('tutor.games.preview', $key),
                    'share_url' => $link->publicUrl(),
                    'url' => $link->publicUrl(),
                ];
            })
            ->values()
            ->all();

        $allBuiltIn = collect(ClassroomGames::all())->map(function (array $game) {
            return [
                ...$game,
                'kind' => 'builtin',
                'preview_url' => route('tutor.games.preview', $game['key']),
                'share_url' => route('tutor.games.preview', $game['key']),
            ];
        })->values()->all();

        return \App\Support\InertiaPage::react('Tutor/Games/Index', [
            'assigned' => array_values(array_merge($assignedBuiltIn, $assignedLinks)),
            'games' => $allBuiltIn,
            'activityLinks' => $allLinks,
        ]);
    }

    public function store()
    {
        abort(403, 'Only admins can upload and assign games.');
    }

    public function assign()
    {
        abort(403, 'Only admins can assign activity links.');
    }

    public function preview(string $game)
    {
        // Tutors may open any published game/link by URL (assignment is optional).
        $meta = ClassroomGames::resolve($game, false);
        abort_unless($meta, 404);

        return \App\Support\InertiaPage::react('Student/Games/Play', [
            'game' => $meta,
            'backUrl' => route('tutor.games.index'),
        ]);
    }

    public function destroy()
    {
        abort(403, 'Only admins can delete activity links.');
    }
}
