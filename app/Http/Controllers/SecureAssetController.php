<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SecureAssetController extends Controller
{
    public function show(Request $request, $path)
    {
        $isPublicAsset = str_starts_with($path, 'courses/thumbnails/') ||
                         str_starts_with($path, 'courses/hls/') ||
                         str_starts_with($path, 'courses/videos/') ||
                         str_starts_with($path, 'courses/welcome_videos/') ||
                         str_starts_with($path, 'courses/3d_assets/') ||
                         str_starts_with($path, 'categories/');

        // Web session guards + mobile Sanctum Bearer (Flutter authMediaHeaders).
        $isAuthenticated = auth('web')->check()
            || auth('tutor')->check()
            || auth('admin')->check()
            || $request->user('sanctum') !== null;

        if (! $isPublicAsset && ! $isAuthenticated) {
            abort(403, 'Unauthorized access to secure asset.');
        }

        $path = str_replace('..', '', $path);

        if (Storage::disk('local')->exists($path)) {
            $fullPath = Storage::disk('local')->path($path);
        } elseif (Storage::disk('public')->exists($path)) {
            $fullPath = Storage::disk('public')->path($path);
        } else {
            abort(404, 'Asset not found.');
        }

        $mimeType = mime_content_type($fullPath) ?: 'application/octet-stream';
        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        if ($extension === 'm3u8') {
            $mimeType = 'application/vnd.apple.mpegurl';
        } elseif ($extension === 'ts') {
            $mimeType = 'video/mp2t';
        }

        $cacheControl = $isPublicAsset
            ? 'public, max-age=86400'
            : 'no-cache, must-revalidate';

        // Content is view-only for tutors and students. Only admins (content owners)
        // may force a download; everyone else always receives the file inline.
        $download = $request->boolean('download') && auth('admin')->check();
        $filename = basename($fullPath);
        $disposition = $download ? 'attachment' : 'inline';

        return response()->file($fullPath, [
            'Cache-Control' => $cacheControl,
            'Content-Type' => $mimeType,
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
            // Allow video players / PDF viewers to read content with CORS when needed.
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Headers' => 'Authorization, Accept, Range',
            'Access-Control-Expose-Headers' => 'Content-Length, Content-Range, Accept-Ranges',
        ]);
    }
}
