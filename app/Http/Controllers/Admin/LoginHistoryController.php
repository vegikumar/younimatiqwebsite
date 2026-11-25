<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginActivity;
use Illuminate\Http\Request;

class LoginHistoryController extends Controller
{
    public function index(Request $request)
    {
        $query = LoginActivity::with('user')->latest();

        if ($request->filled('role')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('role', $request->role);
            });
        }

        $activities = $query->paginate(20)->through(fn (LoginActivity $activity) => [
            'id' => $activity->id,
            'ip_address' => $activity->ip_address,
            'location' => $activity->location,
            'created_at' => $activity->created_at?->toIso8601String(),
            'user' => $activity->user ? [
                'name' => $activity->user->name,
                'email' => $activity->user->email,
                'role' => $activity->user->role,
            ] : null,
        ]);

        return \App\Support\InertiaPage::react('Admin/LoginHistory/Index', [
            'activities' => $activities,
            'filters' => [
                'role' => $request->input('role'),
            ],
        ]);
    }
}
