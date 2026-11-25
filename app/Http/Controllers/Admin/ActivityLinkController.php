<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLink;
use App\Models\ClassroomGameAssignment;
use App\Models\ClassroomGameReward;
use App\Models\ClassroomGameTutor;
use App\Models\Setting;
use App\Models\User;
use App\Services\ActivityLinkService;
use App\Support\ClassroomGames;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLinkController extends Controller
{
    public function index()
    {
        $links = ActivityLink::query()
            ->with(['tutors:id,name,email'])
            ->orderByDesc('id')
            ->get()
            ->map(fn (ActivityLink $link) => [
                'id' => $link->id,
                'title' => $link->title,
                'description' => $link->description,
                'subject' => $link->subject,
                'grade' => $link->grade,
                'access' => $link->access ?: 'members',
                'status' => $link->status ?: 'published',
                'slug' => $link->slug,
                'url' => $link->publicUrl(),
                'original_filename' => $link->original_filename,
                // Never send game source HTML to the browser list — replace via upload/paste only.
                'html' => '',
                'thumbnail_url' => $link->thumbnailUrl(),
                'is_active' => $link->is_active,
                'assigned_tutor_ids' => $link->tutors->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
                'updated_at' => optional($link->updated_at)->timezone(config('app.timezone'))->format('d M Y, H:i'),
            ])
            ->values()
            ->all();

        $tutors = User::query()
            ->where('role', 'tutor')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $tutor) => [
                'id' => $tutor->id,
                'name' => $tutor->name,
                'email' => $tutor->email,
            ])
            ->values()
            ->all();

        // Drop finished student missions from the active assign list.
        $completed = ClassroomGameReward::query()
            ->get(['game_key', 'user_id'])
            ->groupBy('game_key')
            ->map(fn ($rows) => $rows->pluck('user_id')->map(fn ($id) => (int) $id)->all());

        foreach ($completed as $gameKey => $userIds) {
            if ($userIds === []) {
                continue;
            }
            ClassroomGameAssignment::query()
                ->where('game_key', $gameKey)
                ->whereIn('user_id', $userIds)
                ->delete();
        }

        $assignedStudents = ClassroomGameAssignment::query()
            ->get(['game_key', 'user_id'])
            ->groupBy('game_key')
            ->map(fn ($rows) => $rows->pluck('user_id')->map(fn ($id) => (int) $id)->values()->all());

        $assignedTutors = ClassroomGameTutor::query()
            ->get(['game_key', 'tutor_id'])
            ->groupBy('game_key')
            ->map(fn ($rows) => $rows->pluck('tutor_id')->map(fn ($id) => (int) $id)->values()->all());

        $students = User::query()
            ->where('role', 'student')
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $student) => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
            ])
            ->values()
            ->all();

        $builtKeys = collect(ClassroomGames::all())->pluck('key');
        $games = collect(ClassroomGames::all())->map(function (array $game) use ($assignedStudents, $assignedTutors) {
            return [
                ...$game,
                'preview_url' => route('admin.activity-links.preview', $game['key']),
                'share_url' => url('/student/games/'.$game['key']),
                'tutor_share_url' => route('tutor.games.preview', $game['key']),
                'assigned_ids' => $assignedStudents[$game['key']] ?? [],
                'assigned_tutor_ids' => $assignedTutors[$game['key']] ?? [],
            ];
        })->merge(
            ActivityLink::query()
                ->with(['tutors:id'])
                ->where('status', 'published')
                ->where('is_active', true)
                ->whereNotIn('slug', $builtKeys)
                ->orderByDesc('id')
                ->get()
                ->map(function (ActivityLink $link) use ($assignedStudents, $assignedTutors) {
                    $key = ClassroomGames::uploadKey($link->id);
                    $fromPivot = $link->tutors->pluck('id')->map(fn ($id) => (int) $id)->all();
                    $fromTable = $assignedTutors[$key] ?? [];

                    return [
                        'key' => $key,
                        'title' => $link->title,
                        'summary' => trim($link->description ?: 'Uploaded HTML activity.'),
                        'preview_url' => route('admin.activity-links.preview', $key),
                        'share_url' => $link->publicUrl(),
                        'tutor_share_url' => $link->publicUrl(),
                        'access' => $link->access ?: 'members',
                        'assigned_ids' => $assignedStudents[$key] ?? [],
                        'assigned_tutor_ids' => array_values(array_unique(array_merge($fromPivot, $fromTable))),
                    ];
                })
        )->values()->all();

        return \App\Support\InertiaPage::react('Admin/ActivityLinks/Index', [
            'links' => $links,
            'games' => $games,
            'students' => $students,
            'tutors' => $tutors,
            'htmlGamePoints' => ClassroomGames::htmlGamePoints(),
        ]);
    }

    public function assignTutors(Request $request, ActivityLink $activityLink)
    {
        $data = $request->validate([
            'tutor_ids' => ['nullable', 'array'],
            'tutor_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $tutorIds = User::query()
            ->where('role', 'tutor')
            ->whereIn('id', $data['tutor_ids'] ?? [])
            ->pluck('id')
            ->all();

        $sync = [];
        $adminId = Auth::id();
        foreach ($tutorIds as $tutorId) {
            $sync[$tutorId] = ['assigned_by' => $adminId];
        }
        $activityLink->tutors()->sync($sync);

        $count = count($tutorIds);

        return back()->with(
            'success',
            $activityLink->title.' assigned to '.$count.' tutor'.($count === 1 ? '' : 's').'.'
        );
    }

    public function updateGamePoints(Request $request)
    {
        $data = $request->validate([
            'html_game_points' => ['required', 'integer', 'min:0', 'max:1000'],
        ]);

        Setting::query()->updateOrCreate(
            ['key' => 'html_game_points'],
            ['value' => (string) $data['html_game_points']]
        );

        return back()->with('success', 'HTML game XP updated. Students earn this once per game per day after playing at least 20 seconds.');
    }

    public function store(Request $request, ActivityLinkService $service)
    {
        $service->createFromRequest($request, Auth::id());

        return back()->with('success', 'Game created. Assign students below so it appears in their Activities.');
    }

    public function update(Request $request, ActivityLink $activityLink, ActivityLinkService $service)
    {
        $data = $service->validatedMeta($request, creating: false);
        $slug = $service->normalizeSlug($data['slug']);
        $service->assertSlug($slug, $activityLink->id);

        $path = $activityLink->file_path;
        $original = $activityLink->original_filename;
        $html = $service->resolveHtml($request, creating: false);
        if ($html !== null) {
            $path = $service->storeHtmlString($html);
            $original = $request->hasFile('file')
                ? $service->originalName($request->file('file'))
                : 'pasted.html';
            $activityLink->deleteStoredFile();
        }

        $thumbnail = $activityLink->thumbnail_path;
        if ($request->boolean('remove_thumbnail')) {
            $activityLink->deleteThumbnail();
            $thumbnail = null;
        }
        if ($request->hasFile('thumbnail')) {
            $activityLink->deleteThumbnail();
            $thumbnail = $service->storeThumbnail($request->file('thumbnail'));
        }

        $status = $data['status'];

        $activityLink->update([
            'title' => trim($data['title']),
            'description' => $data['description'] ?? '',
            'subject' => $data['subject'] ?? '',
            'grade' => $data['grade'] ?? '',
            'access' => 'members',
            'status' => $status,
            'slug' => $slug,
            'is_active' => $status === 'published',
            'file_path' => $path,
            'original_filename' => $original,
            'thumbnail_path' => $thumbnail,
        ]);

        return back()->with('success', 'Game updated.');
    }

    public function destroy(ActivityLink $activityLink)
    {
        ClassroomGameAssignment::query()
            ->where('game_key', ClassroomGames::uploadKey($activityLink->id))
            ->delete();
        $activityLink->tutors()->detach();
        $activityLink->deleteStoredFile();
        $activityLink->deleteThumbnail();
        $activityLink->delete();

        return back()->with('success', 'Activity link deleted.');
    }
}
