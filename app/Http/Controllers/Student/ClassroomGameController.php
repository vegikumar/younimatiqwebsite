<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ActivityLink;
use App\Models\ClassroomGameAssignment;
use App\Models\ClassroomGameReward;
use App\Models\GameDailyCompletion;
use App\Models\GamePlaySession;
use App\Models\User;
use App\Support\ClassroomGames;
use App\Support\HtmlGameGuard;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ClassroomGameController extends Controller
{
    public function show(string $game)
    {
        if (! $this->studentMayPlay($game)) {
            return redirect()
                ->route('student.activities')
                ->with('error', 'That game is not assigned to you yet.');
        }

        $meta = ClassroomGames::resolve($game);
        if (! $meta) {
            return redirect()
                ->route('student.activities')
                ->with('error', 'That activity is hidden or no longer available.');
        }

        $xp = $this->xpState($game);

        return \App\Support\InertiaPage::react('Student/Games/Play', [
            'game' => $meta,
            'backUrl' => route('student.activities'),
            'xp' => $xp,
        ]);
    }

    public function issuePlayToken(string $game)
    {
        if (! ClassroomGames::isUploadKey($game)) {
            return response()->json(['message' => 'Play tokens are only for uploaded HTML games.'], 422);
        }

        if (! $this->studentMayPlay($game)) {
            return response()->json(['message' => 'That game is not assigned to you.'], 403);
        }

        $meta = ClassroomGames::resolve($game);
        if (! $meta) {
            return response()->json(['message' => 'That activity is hidden or no longer available.'], 404);
        }

        $jti = Str::lower(Str::random(40));
        GamePlaySession::query()->create([
            'jti' => $jti,
            'game_key' => $game,
            'user_id' => Auth::id(),
            'used' => false,
            'rewarded' => false,
        ]);

        return response()->json([
            'token' => $jti,
            'session_id' => $jti,
            'play_url' => route('student.games.play', $jti),
            'expires_in' => 120,
        ]);
    }

    public function play(string $token, Request $request)
    {
        $blocked = response(
            '<!DOCTYPE html><html><body style="font-family:sans-serif;padding:2rem;text-align:center"><h3>This game can only be played inside Younimatiq.</h3></body></html>',
            403,
            ['Content-Type' => 'text/html; charset=UTF-8', 'Cache-Control' => 'no-store']
        );

        $session = GamePlaySession::query()
            ->where('jti', $token)
            ->where('user_id', Auth::id())
            ->first();

        if (! $session) {
            return $blocked;
        }

        if ($session->created_at->lt(now()->subMinutes(2)) && ! $session->used) {
            return $blocked;
        }

        $dest = strtolower((string) $request->header('Sec-Fetch-Dest', ''));
        if ($dest !== '' && $dest !== 'iframe') {
            return $blocked;
        }

        if (! $session->used) {
            $session->forceFill([
                'used' => true,
                'used_at' => now(),
            ])->save();
        }

        if (! preg_match('/^upload-(\d+)$/', $session->game_key, $matches)) {
            return $blocked;
        }

        $link = ActivityLink::query()
            ->where('id', (int) $matches[1])
            ->where('is_active', true)
            ->where('status', 'published')
            ->first();

        if (! $link || ! preg_match('#^activity-links/[A-Za-z0-9._-]+\.html$#', (string) $link->file_path)) {
            return $blocked;
        }

        if (! Storage::disk('local')->exists($link->file_path)) {
            return $blocked;
        }

        $html = (string) Storage::disk('local')->get($link->file_path);
        $html = HtmlGameGuard::inject($html, requireEmbed: true);

        return response($html, 200, HtmlGameGuard::responseHeaders(allowSameOriginFrame: true));
    }

    public function complete(Request $request, string $game)
    {
        if (! ClassroomGames::isUploadKey($game)) {
            return response()->json(['message' => 'Use the built-in XP claim for this game.'], 422);
        }

        if (! $this->studentMayPlay($game)) {
            return response()->json(['message' => 'That game is not assigned to you.'], 403);
        }

        $data = $request->validate([
            'session_id' => ['required', 'string', 'max:64'],
        ]);

        $session = GamePlaySession::query()
            ->where('jti', $data['session_id'])
            ->where('game_key', $game)
            ->where('user_id', Auth::id())
            ->first();

        if (! $session || ! $session->used) {
            return response()->json(['message' => 'Invalid game session.'], 400);
        }

        $started = $session->used_at ?? $session->created_at;
        $played = max(0, (int) $started->diffInSeconds(now()));
        if ($played > 6 * 3600) {
            return response()->json(['message' => 'Game session expired, please restart the game.'], 400);
        }

        if ($played < ClassroomGames::HTML_MIN_SECONDS) {
            return response()->json([
                'points_awarded' => 0,
                'reason' => 'too_fast',
                'message' => 'Play a bit longer (at least 20 seconds), then finish again.',
            ]);
        }

        if ($session->rewarded) {
            return response()->json([
                'points_awarded' => 0,
                'reason' => 'already',
                'message' => 'XP already claimed for this play session.',
            ]);
        }

        $userId = Auth::id();
        $points = ClassroomGames::htmlGamePoints();
        $day = now()->toDateString();

        try {
            $result = DB::transaction(function () use ($session, $userId, $game, $points, $day) {
                $locked = GamePlaySession::query()->where('id', $session->id)->lockForUpdate()->first();
                if (! $locked || $locked->rewarded) {
                    return [
                        'points_awarded' => 0,
                        'reason' => 'already',
                        'message' => 'XP already claimed for this play session.',
                    ];
                }

                $daily = GameDailyCompletion::query()
                    ->where('user_id', $userId)
                    ->where('game_key', $game)
                    ->whereDate('day', $day)
                    ->lockForUpdate()
                    ->first();

                if ($daily) {
                    $locked->forceFill(['rewarded' => true])->save();

                    return [
                        'points_awarded' => 0,
                        'reason' => 'daily_limit',
                        'message' => 'You already earned XP from this game today. Come back tomorrow.',
                        'points' => (int) $daily->points,
                    ];
                }

                $locked->forceFill(['rewarded' => true])->save();

                if ($points < 1) {
                    GameDailyCompletion::query()->create([
                        'user_id' => $userId,
                        'game_key' => $game,
                        'day' => $day,
                        'points' => 0,
                    ]);

                    return [
                        'points_awarded' => 0,
                        'reason' => 'ok',
                        'message' => 'Finished. HTML game points are currently set to 0.',
                    ];
                }

                GameDailyCompletion::query()->create([
                    'user_id' => $userId,
                    'game_key' => $game,
                    'day' => $day,
                    'points' => $points,
                ]);

                $user = User::query()->lockForUpdate()->find($userId);
                $user->xp = (int) ($user->xp ?? 0) + $points;
                $user->save();

                return [
                    'points_awarded' => $points,
                    'reason' => 'ok',
                    'message' => "+{$points} XP added for today's play.",
                    'xp' => (int) $user->xp,
                ];
            });
        } catch (QueryException $exception) {
            report($exception);

            return response()->json([
                'points_awarded' => 0,
                'reason' => 'daily_limit',
                'message' => 'You already earned XP from this game today. Come back tomorrow.',
            ]);
        }

        return response()->json($result);
    }

    public function claim(Request $request, string $game)
    {
        if (ClassroomGames::isUploadKey($game)) {
            return response()->json(['message' => 'Finish the HTML game to claim daily XP.'], 422);
        }

        $max = ClassroomGames::maxPoints($game);
        if ($max === null) {
            return response()->json(['message' => 'This activity does not award XP.'], 422);
        }

        if (! $this->studentMayPlay($game)) {
            return response()->json(['message' => 'That game is not assigned to you.'], 403);
        }

        $points = (int) $request->validate([
            'points' => 'required|integer|min:1|max:'.$max,
        ])['points'];

        $userId = Auth::id();

        try {
            $result = DB::transaction(function () use ($userId, $game, $points) {
                $existing = ClassroomGameReward::query()
                    ->where('user_id', $userId)
                    ->where('game_key', $game)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    return [
                        'awarded' => false,
                        'already' => true,
                        'points' => (int) $existing->points,
                        'xp' => (int) (User::query()->find($userId)?->xp ?? 0),
                    ];
                }

                ClassroomGameReward::create([
                    'user_id' => $userId,
                    'game_key' => $game,
                    'points' => $points,
                ]);

                // Mission complete — clear active assignment so admin assign list drops this student.
                ClassroomGameAssignment::query()
                    ->where('game_key', $game)
                    ->where('user_id', $userId)
                    ->delete();

                $user = User::query()->lockForUpdate()->find($userId);
                $user->xp = (int) ($user->xp ?? 0) + $points;
                $user->save();

                return [
                    'awarded' => true,
                    'already' => false,
                    'points' => $points,
                    'xp' => (int) $user->xp,
                ];
            });
        } catch (QueryException $exception) {
            $existing = ClassroomGameReward::query()
                ->where('user_id', $userId)
                ->where('game_key', $game)
                ->first();

            if (! $existing) {
                throw $exception;
            }

            $result = [
                'awarded' => false,
                'already' => true,
                'points' => (int) $existing->points,
                'xp' => (int) (User::query()->find($userId)?->xp ?? 0),
            ];
        }

        return response()->json($result);
    }

    private function studentMayPlay(string $game): bool
    {
        $userId = Auth::id();

        // Active assignment, or already completed (reward) so they can reopen if needed.
        if (ClassroomGameAssignment::query()
            ->where('game_key', $game)
            ->where('user_id', $userId)
            ->exists()) {
            return true;
        }

        return ClassroomGameReward::query()
            ->where('game_key', $game)
            ->where('user_id', $userId)
            ->exists();
    }

    private function xpState(string $game): array
    {
        if (ClassroomGames::isUploadKey($game)) {
            $today = GameDailyCompletion::query()
                ->where('user_id', Auth::id())
                ->where('game_key', $game)
                ->whereDate('day', now()->toDateString())
                ->first();

            return [
                'mode' => 'daily',
                'max' => ClassroomGames::htmlGamePoints(),
                'awarded' => $today !== null,
                'points' => (int) ($today->points ?? 0),
                'min_seconds' => ClassroomGames::HTML_MIN_SECONDS,
            ];
        }

        $reward = ClassroomGameReward::query()
            ->where('user_id', Auth::id())
            ->where('game_key', $game)
            ->first();

        return [
            'mode' => 'once',
            'max' => ClassroomGames::maxPoints($game) ?? 0,
            'awarded' => $reward !== null,
            'points' => (int) ($reward->points ?? 0),
        ];
    }
}
