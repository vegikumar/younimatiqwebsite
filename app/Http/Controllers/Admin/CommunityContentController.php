<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeaturedProject;
use App\Models\Testimonial;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CommunityContentController extends Controller
{
    public function __construct(private ImageUploadService $images) {}

    public function projects()
    {
        $projects = FeaturedProject::orderBy('created_at', 'desc')->get();

        return \App\Support\InertiaPage::react('Admin/Community/Projects', [
            'projects' => $projects->map(fn ($p) => \App\Support\AdminInertiaProps::featuredProject($p))->values()->all(),
        ]);
    }

    public function storeProject(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author_name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'video_file' => 'required|file|mimes:mp4,webm,mov,jpg,jpeg,png,gif,webp|max:51200', // max 50MB
            'project_url' => 'nullable|url',
            'views_count' => 'nullable|integer|min:0',
            'likes_count' => 'nullable|integer|min:0',
        ]);

        $path = $this->images->storePath($request->file('video_file'), 'community/projects', 'public');

        FeaturedProject::create([
            'title' => $validated['title'],
            'author_name' => $validated['author_name'],
            'category' => $validated['category'],
            'video_path' => $path,
            'project_url' => $validated['project_url'],
            'views_count' => $validated['views_count'] ?? 0,
            'likes_count' => $validated['likes_count'] ?? 0,
        ]);

        return back()->with('success', 'Project added successfully.');
    }

    public function updateProject(Request $request, FeaturedProject $project)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author_name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'video_file' => 'nullable|file|mimes:mp4,webm,mov,jpg,jpeg,png,gif,webp|max:51200',
            'project_url' => 'nullable|url',
            'views_count' => 'nullable|integer|min:0',
            'likes_count' => 'nullable|integer|min:0',
        ]);

        if ($request->hasFile('video_file')) {
            if ($project->video_path) {
                Storage::disk('public')->delete($project->video_path);
            }
            $project->video_path = $this->images->storePath($request->file('video_file'), 'community/projects', 'public');
        }

        $project->update([
            'title' => $validated['title'],
            'author_name' => $validated['author_name'],
            'category' => $validated['category'],
            'project_url' => $validated['project_url'],
            'views_count' => $validated['views_count'] ?? $project->views_count,
            'likes_count' => $validated['likes_count'] ?? $project->likes_count,
        ]);

        return back()->with('success', 'Project updated successfully.');
    }

    public function destroyProject(FeaturedProject $project)
    {
        if ($project->video_path) {
            Storage::disk('public')->delete($project->video_path);
        }
        $project->delete();

        return back()->with('success', 'Project deleted successfully.');
    }

    public function testimonials()
    {
        $testimonials = Testimonial::orderBy('sort_order')->orderBy('created_at', 'desc')->get();

        return \App\Support\InertiaPage::react('Admin/Community/Testimonials', [
            'testimonials' => $testimonials->map(fn ($t) => \App\Support\AdminInertiaProps::testimonial($t))->values()->all(),
        ]);
    }

    public function storeTestimonial(Request $request)
    {
        $validated = $request->validate([
            'author_name' => 'required|string|max:255',
            'text' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
            'avatar_file' => 'nullable|image|max:5120',
            'video_file' => 'nullable|file|mimes:mp4,webm,mov|max:51200',
        ]);

        $avatarPath = null;
        if ($request->hasFile('avatar_file')) {
            $avatarPath = $this->images->storePath($request->file('avatar_file'), 'community/testimonials/avatars', 'public');
        }

        $videoPath = null;
        if ($request->hasFile('video_file')) {
            $videoPath = $request->file('video_file')->store('community/testimonials/videos', 'public');
        }

        Testimonial::create([
            'author_name' => $validated['author_name'],
            'text' => $validated['text'],
            'rating' => $validated['rating'],
            'avatar_path' => $avatarPath,
            'video_path' => $videoPath,
            'sort_order' => Testimonial::max('sort_order') + 1,
        ]);

        return back()->with('success', 'Testimonial added successfully.');
    }

    public function updateTestimonial(Request $request, Testimonial $testimonial)
    {
        $validated = $request->validate([
            'author_name' => 'required|string|max:255',
            'text' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
            'avatar_file' => 'nullable|image|max:5120',
            'video_file' => 'nullable|file|mimes:mp4,webm,mov|max:51200',
        ]);

        if ($request->hasFile('avatar_file')) {
            if ($testimonial->avatar_path) {
                Storage::disk('public')->delete($testimonial->avatar_path);
            }
            $testimonial->avatar_path = $this->images->storePath($request->file('avatar_file'), 'community/testimonials/avatars', 'public');
        }

        if ($request->hasFile('video_file')) {
            if ($testimonial->video_path) {
                Storage::disk('public')->delete($testimonial->video_path);
            }
            $testimonial->video_path = $request->file('video_file')->store('community/testimonials/videos', 'public');
        }

        $testimonial->update([
            'author_name' => $validated['author_name'],
            'text' => $validated['text'],
            'rating' => $validated['rating'],
        ]);

        return back()->with('success', 'Testimonial updated successfully.');
    }

    public function destroyTestimonial(Testimonial $testimonial)
    {
        if ($testimonial->avatar_path) {
            Storage::disk('public')->delete($testimonial->avatar_path);
        }
        $testimonial->delete();

        return back()->with('success', 'Testimonial deleted successfully.');
    }
}
