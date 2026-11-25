<?php

namespace App\Http\Controllers;

use App\Mail\SupportInboundNoteMail;
use App\Models\Booking;
use App\Models\CampBooking;
use App\Services\TimezoneService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class SessionNoteController extends Controller
{
    public const SUPPORT_EMAIL = 'support@younimatiq.com';

    public function show(Request $request)
    {
        $context = $this->resolveContext($request);

        return \App\Support\InertiaPage::react('Public/SessionNote', [
            'context' => $context,
            'old' => [
                'name' => old('name', $context['name'] ?? ''),
                'email' => old('email', $context['email'] ?? ''),
                'note' => old('note', ''),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'note' => 'required|string|min:10|max:5000',
            'kind' => 'nullable|string|max:40',
            'ref_type' => 'nullable|string|in:camp,booking',
            'ref_id' => 'nullable|integer|min:1',
            'session_name' => 'nullable|string|max:255',
            'when_label' => 'nullable|string|max:255',
        ]);

        $context = $this->resolveContext($request, $data);
        $sessionName = $data['session_name'] ?: ($context['session_name'] ?? 'Session');
        $whenLabel = $data['when_label'] ?: ($context['when_label'] ?? 'Not specified');
        $kind = $data['kind'] ?: ($context['kind'] ?? 'class');
        $kindLabel = $kind === 'trial' ? 'Trial / demo' : 'Class';

        $subject = "Can't attend — {$kindLabel}: {$sessionName}";
        $body = '<div style="font-family:Arial,sans-serif;color:#333;max-width:640px;">'
            .'<h2 style="color:#003d86;margin:0 0 12px;">Parent can\'t join — custom note</h2>'
            .'<p style="margin:0 0 16px;">A parent submitted a note from the email “Can\'t join?” button.</p>'
            .'<table style="width:100%;border-collapse:collapse;font-size:14px;">'
            .'<tr><td style="padding:6px 0;font-weight:bold;width:140px;">Name</td><td>'.e($data['name']).'</td></tr>'
            .'<tr><td style="padding:6px 0;font-weight:bold;">Email</td><td>'.e($data['email']).'</td></tr>'
            .'<tr><td style="padding:6px 0;font-weight:bold;">Type</td><td>'.e($kindLabel).'</td></tr>'
            .'<tr><td style="padding:6px 0;font-weight:bold;">Session</td><td>'.e($sessionName).'</td></tr>'
            .'<tr><td style="padding:6px 0;font-weight:bold;">When</td><td>'.e($whenLabel).'</td></tr>'
            .(($context['ref_label'] ?? null)
                ? '<tr><td style="padding:6px 0;font-weight:bold;">Reference</td><td>'.e($context['ref_label']).'</td></tr>'
                : '')
            .'</table>'
            .'<p style="margin:18px 0 6px;font-weight:bold;">Note from parent</p>'
            .'<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px;white-space:pre-wrap;">'
            .e($data['note'])
            .'</div>'
            .'<p style="margin-top:18px;font-size:13px;color:#64748b;">Reply directly to '.e($data['email']).' to follow up.</p>'
            .'</div>';

        try {
            Mail::to(self::SUPPORT_EMAIL)->send(new SupportInboundNoteMail(
                $subject,
                $body,
                $data['email'],
                $data['name'],
            ));
        } catch (\Throwable $e) {
            Log::error('Session note to support failed: '.$e->getMessage());
            throw ValidationException::withMessages([
                'note' => 'We could not send your note right now. Please email '.self::SUPPORT_EMAIL.' directly.',
            ]);
        }

        return redirect()
            ->route('session-note.show', array_filter([
                'kind' => $kind,
                'ref' => $context['ref'] ?? null,
                'session' => $sessionName,
                'when' => $whenLabel,
                'name' => $data['name'],
                'email' => $data['email'],
            ]))
            ->with('success', 'Thanks — your note was sent to support@younimatiq.com. Our team will follow up shortly.');
    }

    /**
     * Public URL for “Can't join?” buttons inside class / trial emails.
     */
    public static function emailButtonUrl(array $params = []): string
    {
        return route('session-note.show', array_filter([
            'kind' => $params['kind'] ?? null,
            'ref' => $params['ref'] ?? null,
            'session' => $params['session'] ?? null,
            'when' => $params['when'] ?? null,
            'name' => $params['name'] ?? null,
            'email' => $params['email'] ?? null,
        ], fn ($v) => $v !== null && $v !== ''));
    }

    /**
     * HTML for emails that include a Meet/Zoom join link.
     * Returns empty string when there is no meeting link.
     */
    public static function emailButtonHtml(?string $joinUrl, array $params = []): string
    {
        if (! filled($joinUrl)) {
            return '';
        }

        $url = self::emailButtonUrl($params);

        return '<p style="margin:24px 0 8px;text-align:center;">'
            .'<a href="'.e($url).'" style="background:#ffffff;color:#0057bf;padding:12px 22px;border-radius:8px;text-decoration:none;font-weight:bold;display:inline-block;border:2px solid #0057bf;">Can\'t join? Leave a note for support</a>'
            .'</p>'
            .'<p style="font-size:13px;color:#666;text-align:center;margin:0 0 12px;">Your note goes to support@younimatiq.com.</p>';
    }

    private function resolveContext(Request $request, array $overrides = []): array
    {
        $ref = (string) ($overrides['ref'] ?? $request->query('ref', ''));
        $refType = $overrides['ref_type'] ?? null;
        $refId = $overrides['ref_id'] ?? null;

        if (! $refType && str_contains($ref, ':')) {
            [$refType, $refId] = array_pad(explode(':', $ref, 2), 2, null);
            $refId = is_numeric($refId) ? (int) $refId : null;
        }

        $context = [
            'kind' => $overrides['kind'] ?? $request->query('kind', 'class'),
            'session_name' => $overrides['session_name'] ?? $request->query('session', ''),
            'when_label' => $overrides['when_label'] ?? $request->query('when', ''),
            'name' => $overrides['name'] ?? $request->query('name', ''),
            'email' => $overrides['email'] ?? $request->query('email', ''),
            'ref' => $ref !== '' ? $ref : (($refType && $refId) ? "{$refType}:{$refId}" : ''),
            'ref_type' => $refType,
            'ref_id' => $refId,
            'ref_label' => null,
        ];

        if ($refType === 'camp' && $refId) {
            $camp = CampBooking::find($refId);
            if ($camp) {
                $context['session_name'] = $context['session_name'] ?: ($camp->camp_name ?: 'Trial class');
                $context['name'] = $context['name'] ?: ($camp->parent_name ?: $camp->child_name);
                $context['email'] = $context['email'] ?: $camp->email;
                $context['when_label'] = $context['when_label'] ?: (
                    $camp->scheduled_at
                        ? TimezoneService::formatForEmail($camp->scheduled_at, $camp->parent_timezone, $camp->timezone)
                        : ''
                );
                $context['kind'] = $context['kind'] ?: 'trial';
                $context['ref_label'] = 'Camp booking #'.$camp->id.' · '.($camp->child_name ?: 'Student');
            }
        }

        if ($refType === 'booking' && $refId) {
            $booking = Booking::with(['student', 'course'])->find($refId);
            if ($booking) {
                $context['session_name'] = $context['session_name'] ?: ($booking->course?->title ?: 'Class');
                $context['name'] = $context['name'] ?: ($booking->student?->name ?: '');
                $context['email'] = $context['email'] ?: ($booking->student?->email ?: '');
                $context['when_label'] = $context['when_label'] ?: (
                    $booking->scheduled_at
                        ? TimezoneService::formatForEmail(
                            $booking->scheduled_at,
                            $booking->student?->timezone,
                            $booking->parent_timezone,
                            $booking->timezone,
                        )
                        : ''
                );
                $context['kind'] = $context['kind'] ?: 'class';
                $context['ref_label'] = 'Booking #'.$booking->id.' · '.($booking->student?->name ?: 'Student');
            }
        }

        return $context;
    }
}
