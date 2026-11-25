<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\Tip;
use App\Models\TipAssignment;
use App\Models\TipFolder;
use App\Services\StudentApiService;
use Illuminate\Http\Request;

class TipController extends Controller
{
    use RespondsWithJson;

    public function index(Request $request, StudentApiService $studentApi)
    {
        return $this->success($studentApi->formatAssignedTips($request->user()->id));
    }

    public function stream(Request $request, Tip $tip)
    {
        $user = $request->user();

        $hasDirectAssignment = TipAssignment::where('student_id', $user->id)
            ->where('tip_id', $tip->id)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();

        $hasFolderAssignment = false;
        $currentFolderId = $tip->tip_folder_id;

        while ($currentFolderId && ! $hasFolderAssignment) {
            $hasFolderAssignment = TipAssignment::where('student_id', $user->id)
                ->where('tip_folder_id', $currentFolderId)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->exists();

            if (! $hasFolderAssignment) {
                $folder = TipFolder::find($currentFolderId);
                $currentFolderId = $folder ? $folder->parent_id : null;
            }
        }

        if (! $hasDirectAssignment && ! $hasFolderAssignment) {
            return $this->error('Unauthorized access to this file.', 403);
        }

        if (filter_var($tip->content_url, FILTER_VALIDATE_URL)) {
            return redirect($tip->content_url);
        }

        $path = storage_path('app/private/' . $tip->content_url);

        if (! file_exists($path)) {
            $oldPath = storage_path('app/' . $tip->content_url);
            if (file_exists($oldPath)) {
                $path = $oldPath;
            } else {
                $publicPath = storage_path('app/public/' . str_replace('/storage/', '', $tip->content_url));
                if (file_exists($publicPath)) {
                    $path = $publicPath;
                } else {
                    return $this->error('File not found.', 404);
                }
            }
        }

        $mimeType = mime_content_type($path) ?: 'application/octet-stream';

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'no-cache, must-revalidate',
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        ]);
    }
}
