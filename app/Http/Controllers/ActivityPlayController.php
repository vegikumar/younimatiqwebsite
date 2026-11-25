<?php

namespace App\Http\Controllers;

use App\Models\ActivityLink;
use App\Models\ActivityPlayToken;
use App\Support\HtmlGameGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ActivityPlayController extends Controller
{
    public function shell(ActivityLink $activity)
    {
        if (! $activity->isPublished()) {
            abort(404);
        }

        if (! $this->viewerMayAccess($activity)) {
            return redirect()->route('login.student')
                ->with('error', 'Please sign in to play this protected activity.');
        }

        return \App\Support\InertiaPage::react('Public/SecureActivityPlay', [
            'title' => $activity->title,
            'slug' => $activity->slug,
            'tokenUrl' => route('activity-play.token', $activity->slug),
            'description' => $activity->description,
        ]);
    }

    public function issueToken(Request $request, string $slug)
    {
        $activity = ActivityLink::query()->where('slug', $slug)->firstOrFail();

        if (! $activity->isPublished()) {
            return response()->json(['message' => 'This activity is not available.'], 404);
        }

        if (! $this->viewerMayAccess($activity)) {
            return response()->json(['message' => 'Sign in to play this protected activity.'], 401);
        }

        $jti = Str::lower(Str::random(40));
        ActivityPlayToken::query()->create([
            'jti' => $jti,
            'activity_link_id' => $activity->id,
            'user_id' => $this->viewerId(),
            'ip_hash' => hash('sha256', (string) $request->ip()),
            'expires_at' => now()->addMinutes(2),
        ]);

        return response()->json([
            'play_url' => route('activity-play.session', $jti),
            'expires_in' => 120,
        ]);
    }

    public function session(string $token, Request $request)
    {
        $blocked = response(
            '<!DOCTYPE html><html><body style="font-family:sans-serif;padding:2rem;text-align:center"><h3>This activity can only be played inside Younimatiq.</h3></body></html>',
            403,
            array_merge(HtmlGameGuard::responseHeaders(allowSameOriginFrame: false), [
                'Content-Type' => 'text/html; charset=UTF-8',
            ])
        );

        $play = ActivityPlayToken::query()->where('jti', $token)->first();
        if (! $play || $play->isExpired()) {
            return $blocked;
        }

        // Prefer iframe embedding; allow same-origin navigations without Sec-Fetch-Dest.
        $dest = strtolower((string) $request->header('Sec-Fetch-Dest', ''));
        if ($dest !== '' && $dest !== 'iframe') {
            return $blocked;
        }

        if ($play->used_at && $play->used_at->lt(now()->subSeconds(30))) {
            // One short replay window after first use, then lock.
            return $blocked;
        }

        $activity = $play->activityLink;
        if (! $activity || ! $activity->isPublished()) {
            return $blocked;
        }

        if (! $this->viewerMayAccess($activity)) {
            return $blocked;
        }

        if (! preg_match('#^activity-links/[A-Za-z0-9._-]+\.html$#', (string) $activity->file_path)) {
            return $blocked;
        }

        if (! Storage::disk('local')->exists($activity->file_path)) {
            return $blocked;
        }

        if (! $play->used_at) {
            $play->forceFill(['used_at' => now()])->save();
        }

        $html = (string) Storage::disk('local')->get($activity->file_path);
        $html = HtmlGameGuard::inject($html, requireEmbed: true);

        return response($html, 200, HtmlGameGuard::responseHeaders(allowSameOriginFrame: true));
    }

    private function viewerMayAccess(ActivityLink $activity): bool
    {
        // All activity links require a signed-in portal user.
        return Auth::guard('web')->check()
            || Auth::guard('parent')->check()
            || Auth::guard('tutor')->check()
            || Auth::guard('admin')->check();
    }

    private function viewerId(): ?int
    {
        return Auth::guard('web')->id()
            ?? Auth::guard('parent')->id()
            ?? Auth::guard('tutor')->id()
            ?? Auth::guard('admin')->id();
    }
}
