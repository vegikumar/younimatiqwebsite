<?php

namespace App\Http\Controllers;

use App\Models\MeetContactAlert;
use App\Models\User;
use App\Services\AntiBypassSecurityService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MeetContactAlertWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        AntiBypassSecurityService $security,
        NotificationService $notifications
    ) {
        $secret = (string) config('services.meet.api_secret');
        $provided = (string) $request->header('X-Meet-Secret', '');
        if ($secret === '' || ! hash_equals($secret, $provided)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'room_id' => 'required|string|max:64',
            'source' => 'required|string|in:chat,speech,captions',
            'actor_name' => 'nullable|string|max:255',
            'actor_role' => 'nullable|string|in:tutor,student,unknown',
            'shared_text' => 'required|string|max:5000',
            'matches' => 'nullable|array',
            'matches.*.type' => 'nullable|string|max:32',
            'matches.*.value' => 'nullable|string|max:500',
            'occurred_at' => 'nullable|date',
        ]);

        $sharedText = $data['shared_text'];
        $matches = $data['matches'] ?? [];
        if (empty($matches)) {
            $matches = $security->extractContactMatches($sharedText);
        }
        if (empty($matches) && ! $security->containsSensitiveContactInfo($sharedText)) {
            return response()->json(['ok' => true, 'ignored' => true]);
        }

        $primary = $matches[0]['type'] ?? $security->primaryContactType($sharedText) ?? 'contact';
        $occurredAt = isset($data['occurred_at'])
            ? Carbon::parse($data['occurred_at'])
            : now();

        // Deduplicate identical alerts within 2 minutes
        $fingerprintValues = collect($matches)->pluck('value')->filter()->sort()->implode('|');
        $recent = MeetContactAlert::query()
            ->where('room_id', $data['room_id'])
            ->where('source', $data['source'])
            ->where('actor_name', $data['actor_name'] ?? null)
            ->where('occurred_at', '>=', $occurredAt->copy()->subMinutes(2))
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->first(function (MeetContactAlert $alert) use ($fingerprintValues) {
                $existing = collect($alert->matches ?? [])->pluck('value')->filter()->sort()->implode('|');

                return $existing === $fingerprintValues;
            });

        if ($recent) {
            return response()->json(['ok' => true, 'deduped' => true, 'id' => $recent->id]);
        }

        $alert = MeetContactAlert::create([
            'room_id' => $data['room_id'],
            'source' => $data['source'],
            'actor_name' => $data['actor_name'] ?? null,
            'actor_role' => $data['actor_role'] ?? 'unknown',
            'shared_text' => $sharedText,
            'matches' => $matches,
            'primary_type' => $primary,
            'occurred_at' => $occurredAt,
        ]);

        $matchSummary = collect($matches)
            ->map(fn ($m) => strtoupper((string) ($m['type'] ?? 'contact')).': '.($m['value'] ?? ''))
            ->filter()
            ->implode(', ');

        $title = 'Contact shared in Meet ('.$alert->typeLabel().')';
        $body = sprintf(
            '%s (%s) shared %s via %s in room %s at %s.%s',
            $alert->actor_name ?: 'Someone',
            $alert->actor_role ?: 'unknown',
            $matchSummary !== '' ? $matchSummary : 'contact info',
            $alert->sourceLabel(),
            $alert->room_id,
            $alert->occurred_at->timezone(config('app.timezone'))->format('M j, Y g:i:s A'),
            $sharedText ? ' Text: "'.\Illuminate\Support\Str::limit($sharedText, 180).'"' : ''
        );

        $link = route('admin.meet-alerts.index', ['highlight' => $alert->id]);

        User::where('role', 'admin')->where('status', 'active')->pluck('id')->each(
            function (int $adminId) use ($notifications, $title, $body, $link) {
                $notifications->notify($adminId, $title, $body, $link, 'warning');
            }
        );

        return response()->json(['ok' => true, 'id' => $alert->id]);
    }
}
