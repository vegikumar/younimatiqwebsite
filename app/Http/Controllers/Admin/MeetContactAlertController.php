<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MeetContactAlert;
use Illuminate\Http\Request;

class MeetContactAlertController extends Controller
{
    public function index(Request $request)
    {
        $query = MeetContactAlert::query()->orderByDesc('occurred_at');

        if ($request->filled('source')) {
            $query->where('source', $request->string('source'));
        }
        if ($request->filled('type')) {
            $query->where('primary_type', $request->string('type'));
        }
        if ($request->boolean('unreviewed')) {
            $query->unreviewed();
        }
        if ($request->filled('q')) {
            $q = '%'.$request->string('q').'%';
            $query->where(function ($builder) use ($q) {
                $builder->where('room_id', 'like', $q)
                    ->orWhere('actor_name', 'like', $q)
                    ->orWhere('shared_text', 'like', $q);
            });
        }

        $alerts = $query->paginate(40)->withQueryString();
        $unreviewedCount = MeetContactAlert::unreviewed()->count();

        return \App\Support\InertiaPage::react('Admin/MeetAlerts/Index', [
            'alerts' => \App\Support\AdminInertiaProps::paginate($alerts, fn ($a) => \App\Support\AdminInertiaProps::meetAlert($a)),
            'unreviewedCount' => $unreviewedCount,
            'highlightId' => $request->integer('highlight') ?: null,
            'filters' => [
                'q' => $request->query('q', ''),
                'source' => $request->query('source', ''),
                'type' => $request->query('type', ''),
                'unreviewed' => $request->boolean('unreviewed'),
            ],
        ]);
    }

    public function markReviewed(MeetContactAlert $alert)
    {
        $alert->markReviewed(auth('admin')->id() ?? auth()->id());

        return back()->with('success', 'Alert marked as reviewed.');
    }
}
