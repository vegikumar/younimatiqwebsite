<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLink;
use App\Models\ClassroomGameAssignment;
use App\Models\ClassroomGameReward;
use App\Models\ClassroomGameTutor;
use App\Models\User;
use App\Services\EmailService;
use App\Support\ClassroomGames;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ClassroomGameController extends Controller
{
    public function assign(Request $request, string $game)
    {
        $meta = ClassroomGames::resolve($game, false);
        abort_unless($meta, 404);

        $data = $request->validate([
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer', 'exists:users,id'],
            'tutor_ids' => ['nullable', 'array'],
            'tutor_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $adminId = Auth::id();
        $title = $meta['title'] ?? 'Game';

        $studentNote = $this->syncStudents($game, $meta, $data['student_ids'] ?? [], $adminId);
        $tutorNote = $this->syncTutors($game, $data['tutor_ids'] ?? [], $adminId);

        return back()->with('success', trim($title.'. '.$studentNote.' '.$tutorNote));
    }

    /**
     * @param  list<int|string>  $rawIds
     */
    private function syncStudents(string $game, array $meta, array $rawIds, ?int $adminId): string
    {
        $ids = User::query()
            ->where('role', 'student')
            ->whereIn('id', $rawIds)
            ->pluck('id');

        $completedIds = ClassroomGameReward::query()
            ->where('game_key', $game)
            ->pluck('user_id');

        ClassroomGameAssignment::query()
            ->where('game_key', $game)
            ->whereIn('user_id', $completedIds)
            ->delete();

        $alreadyAssigned = ClassroomGameAssignment::query()
            ->where('game_key', $game)
            ->whereIn('user_id', $ids)
            ->pluck('user_id');

        $newlyAssigned = $ids->diff($alreadyAssigned)->diff($completedIds)->values();

        ClassroomGameAssignment::query()
            ->where('game_key', $game)
            ->when($ids->isNotEmpty(), fn ($query) => $query->whereNotIn('user_id', $ids))
            ->delete();

        foreach ($ids as $id) {
            if ($completedIds->contains($id)) {
                continue;
            }
            ClassroomGameAssignment::query()->updateOrCreate(
                ['game_key' => $game, 'user_id' => $id],
                ['assigned_by' => $adminId]
            );
        }

        if ($newlyAssigned->isNotEmpty()) {
            try {
                app(EmailService::class)->notifyActivityLinkAssigned($meta, $newlyAssigned->all());
            } catch (\Throwable $e) {
                Log::error('Failed to send activity-link assignment emails: '.$e->getMessage());
            }
        }

        $activeCount = ClassroomGameAssignment::query()->where('game_key', $game)->count();
        $mailNote = $newlyAssigned->isNotEmpty()
            ? ' Notes sent to '.$newlyAssigned->count().' new student'.($newlyAssigned->count() === 1 ? '' : 's').'.'
            : '';

        return 'Students: '.$activeCount.' active.'.$mailNote;
    }

    /**
     * @param  list<int|string>  $rawIds
     */
    private function syncTutors(string $game, array $rawIds, ?int $adminId): string
    {
        $tutorIds = User::query()
            ->where('role', 'tutor')
            ->whereIn('id', $rawIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if (ClassroomGames::isUploadKey($game)) {
            $linkId = (int) str_replace('upload-', '', $game);
            $link = ActivityLink::query()->find($linkId);
            if ($link) {
                $sync = [];
                foreach ($tutorIds as $tutorId) {
                    $sync[$tutorId] = ['assigned_by' => $adminId];
                }
                $link->tutors()->sync($sync);
            }
        }

        ClassroomGameTutor::query()
            ->where('game_key', $game)
            ->whereNotIn('tutor_id', $tutorIds ?: [0])
            ->delete();

        foreach ($tutorIds as $tutorId) {
            ClassroomGameTutor::query()->updateOrCreate(
                ['game_key' => $game, 'tutor_id' => $tutorId],
                ['assigned_by' => $adminId]
            );
        }

        $count = count($tutorIds);

        return 'Tutors: '.$count.' assigned.';
    }

    public function preview(string $game)
    {
        $meta = ClassroomGames::resolve($game, false);
        abort_unless($meta, 404);

        return \App\Support\InertiaPage::react('Student/Games/Play', [
            'game' => $meta,
            'backUrl' => route('admin.activity-links.index'),
        ]);
    }
}
