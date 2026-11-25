<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TipFolder;
use App\Models\Tip;
use App\Models\TipAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TipController extends Controller
{
    public function index(Request $request)
    {
        $currentFolderId = $request->query('folder_id');
        $currentFolder = null;
        $breadcrumbs = [];

        if ($currentFolderId) {
            $currentFolder = TipFolder::findOrFail($currentFolderId);
            
            // Generate breadcrumbs
            $tempFolder = $currentFolder;
            while ($tempFolder) {
                array_unshift($breadcrumbs, $tempFolder);
                $tempFolder = $tempFolder->parent;
            }
        }

        // Get folders and tips for the current level
        $foldersQuery = TipFolder::withCount(['children', 'tips']);
        if ($currentFolderId) {
            $foldersQuery->where('parent_id', $currentFolderId);
        } else {
            $foldersQuery->whereNull('parent_id');
        }
        $folders = $foldersQuery->get();

        $tipsQuery = Tip::query();
        if ($currentFolderId) {
            $tipsQuery->where('tip_folder_id', $currentFolderId);
        } else {
            $tipsQuery->whereNull('tip_folder_id');
        }
        $looseTips = $tipsQuery->get();

        // Get global statistics
        $totalFolders = TipFolder::count();
        $totalTips = Tip::count();
        $rootFoldersCount = TipFolder::whereNull('parent_id')->count();
        $rootTipsCount = Tip::whereNull('tip_folder_id')->count();

        // Need all folders for the move modal
        $allFolders = TipFolder::orderBy('name')->get();

        // Get full tree for assignment modal
        $allFoldersTree = TipFolder::with(['children.tips', 'children.children', 'tips'])->whereNull('parent_id')->get();
        $rootTips = Tip::whereNull('tip_folder_id')->get();

        // Get students and assignments for the right column
        $students = User::where('role', 'student')->get();
        $assignments = TipAssignment::with('student', 'folder', 'tip')->get();
        
        return \App\Support\InertiaPage::react('Admin/Tips/Index', [
            'currentFolderId' => $currentFolderId,
            'breadcrumbs' => collect($breadcrumbs)->map(fn ($bc) => ['id' => $bc->id, 'name' => $bc->name])->values()->all(),
            'totalFolders' => $totalFolders,
            'totalTips' => $totalTips,
            'rootFoldersCount' => $rootFoldersCount,
            'rootTipsCount' => $rootTipsCount,
            'folders' => $folders->map(fn ($f) => [
                'id' => $f->id,
                'name' => $f->name,
                'tipsCount' => $f->tips_count,
                'childrenCount' => $f->children_count,
                'url' => route('admin.tips', ['folder_id' => $f->id]),
            ])->values()->all(),
            'tips' => $looseTips->map(fn ($tip) => [
                'id' => $tip->id,
                'title' => $tip->title,
                'type' => $tip->type,
                'streamUrl' => route('tips.stream', $tip->id),
            ])->values()->all(),
            'allFolders' => $allFolders->map(fn ($f) => ['id' => $f->id, 'name' => $f->name])->values()->all(),
            'students' => $students->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values()->all(),
            'assignments' => $assignments->map(fn ($a) => [
                'studentName' => $a->student?->name,
                'target' => $a->folder?->name ?? $a->tip?->title,
            ])->values()->all(),
        ]);
    }

    public function storeFolder(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:tip_folders,id',
        ]);

        TipFolder::create($data);

        return back()->with('success', 'Folder created successfully.');
    }

    public function destroyFolder(TipFolder $folder)
    {
        $folder->delete();
        return back()->with('success', 'Folder deleted successfully.');
    }

    public function storeItem(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'tip_folder_id' => 'nullable|exists:tip_folders,id',
            'type' => 'required|in:video,file',
            'content_files' => 'nullable|array',
            'content_files.*' => 'file|max:102400',
            'content_url' => 'nullable|string'
        ]);

        if ($request->hasFile('content_files')) {
            foreach ($request->file('content_files') as $file) {
                // Store in a private directory, NOT in the public symlinked folder
                $path = $file->store('tips_secure', 'local');
                
                Tip::create([
                    'title' => count($request->file('content_files')) > 1 ? $file->getClientOriginalName() : $data['title'],
                    'tip_folder_id' => $data['tip_folder_id'] ?? null,
                    'type' => $data['type'],
                    'content_url' => $path // Store the raw path instead of a /storage URL
                ]);
            }
        } elseif ($request->filled('content_url')) {
            Tip::create([
                'title' => $data['title'],
                'tip_folder_id' => $data['tip_folder_id'] ?? null,
                'type' => $data['type'],
                'content_url' => $request->content_url
            ]);
        } else {
            return back()->withErrors(['content_url' => 'Please provide a file or a URL.']);
        }

        return back()->with('success', 'Tip(s) created successfully.');
    }

    public function destroyItem(Tip $tip)
    {
        $tip->delete();
        return back()->with('success', 'Tip deleted successfully.');
    }

    public function assign(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:users,id',
            'tip_folder_ids' => 'nullable|array',
            'tip_folder_ids.*' => 'exists:tip_folders,id',
            'tip_ids' => 'nullable|array',
            'tip_ids.*' => 'exists:tips,id',
        ]);

        if (empty($request->tip_folder_ids) && empty($request->tip_ids)) {
            return back()->withErrors(['error' => 'Please select at least one folder or item to assign.']);
        }

        foreach ($request->student_ids as $studentId) {
            if (!empty($request->tip_folder_ids)) {
                foreach ($request->tip_folder_ids as $folderId) {
                    TipAssignment::updateOrCreate(
                        [
                            'student_id' => $studentId,
                            'tip_folder_id' => $folderId,
                        ],
                        [
                            'expires_at' => now()->addDays(7),
                        ]
                    );
                }
            }
            if (!empty($request->tip_ids)) {
                foreach ($request->tip_ids as $tipId) {
                    TipAssignment::updateOrCreate(
                        [
                            'student_id' => $studentId,
                            'tip_id' => $tipId,
                        ],
                        [
                            'expires_at' => now()->addDays(7),
                        ]
                    );
                }
            }
        }

        return back()->with('success', 'Tips assigned successfully to selected students.');
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'tip_folder_ids' => 'nullable|array',
            'tip_folder_ids.*' => 'exists:tip_folders,id',
            'tip_ids' => 'nullable|array',
            'tip_ids.*' => 'exists:tips,id',
        ]);

        if (empty($request->tip_folder_ids) && empty($request->tip_ids)) {
            return back()->withErrors(['error' => 'Please select at least one folder or item to delete.']);
        }

        if (!empty($request->tip_folder_ids)) {
            TipFolder::whereIn('id', $request->tip_folder_ids)->delete();
        }

        if (!empty($request->tip_ids)) {
            Tip::whereIn('id', $request->tip_ids)->delete();
        }

        return back()->with('success', 'Selected items deleted successfully.');
    }

    public function bulkMove(Request $request)
    {
        $request->validate([
            'target_folder_id' => 'nullable|exists:tip_folders,id',
            'tip_folder_ids' => 'nullable|array',
            'tip_folder_ids.*' => 'exists:tip_folders,id',
            'tip_ids' => 'nullable|array',
            'tip_ids.*' => 'exists:tips,id',
        ]);

        if (empty($request->tip_folder_ids) && empty($request->tip_ids)) {
            return back()->withErrors(['error' => 'Please select at least one folder or item to move.']);
        }

        $targetFolderId = $request->target_folder_id;

        if (!empty($request->tip_folder_ids)) {
            // Prevent a folder from being moved into itself
            if (in_array($targetFolderId, $request->tip_folder_ids)) {
                return back()->withErrors(['error' => 'Cannot move a folder into itself.']);
            }
            TipFolder::whereIn('id', $request->tip_folder_ids)->update(['parent_id' => $targetFolderId]);
        }

        if (!empty($request->tip_ids)) {
            Tip::whereIn('id', $request->tip_ids)->update(['tip_folder_id' => $targetFolderId]);
        }

        return back()->with('success', 'Selected items moved successfully.');
    }

    public function stream(Request $request, Tip $tip)
    {
        $user = $request->user();

        // Basic authorization: Must be admin or a student who has it assigned
        $isAuthorized = false;
        
        if ($user->role === 'admin') {
            $isAuthorized = true;
        } elseif ($user->role === 'student') {
            // Check if explicitly assigned to this tip
            $hasDirectAssignment = TipAssignment::where('student_id', $user->id)
                ->where('tip_id', $tip->id)
                ->where(function($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->exists();

            // Check if assigned to the folder this tip is in (or any parent folder)
            $hasFolderAssignment = false;
            $currentFolderId = $tip->tip_folder_id;
            
            while ($currentFolderId && !$hasFolderAssignment) {
                $hasFolderAssignment = TipAssignment::where('student_id', $user->id)
                    ->where('tip_folder_id', $currentFolderId)
                    ->where(function($q) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    })
                    ->exists();
                
                if (!$hasFolderAssignment) {
                    $folder = \App\Models\TipFolder::find($currentFolderId);
                    $currentFolderId = $folder ? $folder->parent_id : null;
                }
            }

            $isAuthorized = $hasDirectAssignment || $hasFolderAssignment;
        }

        if (!$isAuthorized) {
            abort(403, 'Unauthorized access to this file.');
        }

        // Referer anti-leeching removed as it breaks iframe/video tag streaming in some browsers/policies

        if (filter_var($tip->content_url, FILTER_VALIDATE_URL)) {
            return redirect($tip->content_url);
        }

        $path = storage_path('app/private/' . $tip->content_url);
        
        if (!file_exists($path)) {
            // Fallback for older structure where it might just be in app/
            $oldPath = storage_path('app/' . $tip->content_url);
            if (file_exists($oldPath)) {
                $path = $oldPath;
            } else {
                // Fallback for old public uploads
                $publicPath = storage_path('app/public/' . str_replace('/storage/', '', $tip->content_url));
                if (file_exists($publicPath)) {
                    $path = $publicPath;
                } else {
                    abort(404, 'File not found.');
                }
            }
        }

        $mimeType = mime_content_type($path);
        
        $headers = [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'no-cache, must-revalidate',
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"'
        ];

        return response()->file($path, $headers);
    }
}
