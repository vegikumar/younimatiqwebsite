<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

/**
 * Store FCM / push device tokens for future mobile push delivery.
 * Registration only — send path ships with Firebase credentials.
 */
class DeviceTokenController extends Controller
{
    use RespondsWithJson;

    public function store(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|string|max:512',
            'platform' => 'nullable|string|in:ios,android',
            'app_version' => 'nullable|string|max:40',
        ]);

        $row = DeviceToken::updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id' => $request->user()->id,
                'platform' => $data['platform'] ?? null,
                'app_version' => $data['app_version'] ?? null,
                'last_seen_at' => now(),
            ]
        );

        return $this->success([
            'id' => $row->id,
            'platform' => $row->platform,
        ], 'Device token registered.');
    }

    public function destroy(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|string|max:512',
        ]);

        DeviceToken::where('user_id', $request->user()->id)
            ->where('token', $data['token'])
            ->delete();

        return $this->success(null, 'Device token removed.');
    }
}
