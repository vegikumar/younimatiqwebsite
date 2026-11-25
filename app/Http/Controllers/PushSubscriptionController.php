<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function subscribe(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'endpoint' => 'required|url',
            'keys.p256dh' => 'nullable|string',
            'keys.auth' => 'nullable|string',
        ]);

        $request->user()->pushSubscriptions()->updateOrCreate(
            ['endpoint' => $request->endpoint],
            [
                'public_key' => $request->keys['p256dh'] ?? null,
                'auth_token' => $request->keys['auth'] ?? null,
                'content_encoding' => $request->contentEncoding ?? null,
            ]
        );

        return response()->json(['success' => true]);
    }

    public function unsubscribe(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'endpoint' => 'required|url',
        ]);

        $request->user()->pushSubscriptions()->where('endpoint', $request->endpoint)->delete();

        return response()->json(['success' => true]);
    }
}
