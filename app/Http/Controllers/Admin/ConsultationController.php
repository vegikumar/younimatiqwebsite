<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CampBooking;
use App\Services\TimezoneService;
use Carbon\Carbon;

class ConsultationController extends Controller
{
    public function index()
    {
        $consultations = CampBooking::where('camp_name', 'like', 'Consultation%')
            ->latest()
            ->paginate(20);

        return \App\Support\InertiaPage::react('Admin/Consultations/Index', [
            'consultations' => [
                'data' => $consultations->getCollection()->map(fn ($c) => $this->toConsultationRow($c))->values()->all(),
                'links' => $consultations->linkCollection()->toArray(),
                'total' => $consultations->total(),
            ],
        ]);
    }

    private function toConsultationRow(CampBooking $c): array
    {
        $tz = TimezoneService::sanitizeTimezone($c->parent_timezone ?: $c->timezone ?: 'UTC');
        $scheduledParent = null;
        $scheduledIst = null;

        if ($c->scheduled_at) {
            $at = Carbon::parse($c->scheduled_at);
            $scheduledParent = $at->copy()->timezone($tz)->format('M d, Y g:i A T');
            $scheduledIst = $at->copy()->timezone('Asia/Kolkata')->format('M d, Y g:i A T');
        }

        return [
            'id' => $c->id,
            'parentName' => $c->parent_name,
            'email' => $c->email,
            'phone' => $c->phone ?: '',
            'whatsappNumber' => $c->whatsapp_number ?: '',
            'childName' => $c->child_name,
            'age' => $c->age,
            'programLabel' => str_replace(['Consultation - ', 'Free Trial - '], '', $c->camp_name ?? ''),
            'campName' => $c->camp_name,
            'country' => $c->country ?: '',
            'state' => $c->state ?: '',
            'timezone' => $tz,
            'timezoneLabel' => TimezoneService::label($tz),
            'scheduledAt' => optional($c->scheduled_at)?->toIso8601String(),
            'scheduledParentLabel' => $scheduledParent,
            'scheduledIstLabel' => $scheduledIst,
            'zoomJoinUrl' => $c->zoom_join_url,
            'zoomStartUrl' => $c->zoom_start_url,
            'zoomPasscode' => $c->zoom_passcode,
            'createdAt' => optional($c->created_at)?->toIso8601String(),
        ];
    }
}
