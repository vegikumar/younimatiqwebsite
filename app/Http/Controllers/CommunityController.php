<?php

namespace App\Http\Controllers;

use App\Models\FeaturedProject;
use App\Models\FeaturedProjectLike;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Support\PublicInertiaProps;
use Illuminate\Support\Str;

class CommunityController extends Controller
{
    public function index(Request $request)
    {
        $featuredProjects = FeaturedProject::with(['comments' => function($q) {
            $q->orderBy('created_at', 'desc');
        }])->where('is_active', true)->orderBy('created_at', 'desc')->get();
        $testimonials = Testimonial::where('is_active', true)->orderBy('sort_order')->orderBy('created_at', 'desc')->get();
        $likedProjectIds = $this->likedProjectIdsForVisitor($request, $featuredProjects->pluck('id')->all());

        return \App\Support\InertiaPage::react('Public/Community', [
            'featuredProjects' => $featuredProjects->map(fn ($p) => PublicInertiaProps::featuredProject($p))->values()->all(),
            'testimonials' => $testimonials->map(fn ($t) => [
                'id' => $t->id,
                'content' => $t->content ?? $t->quote ?? '',
                'author_name' => $t->author_name ?? $t->name,
            ])->values()->all(),
            'likedProjectIds' => $likedProjectIds,
        ]);
    }

    public function storeComment(Request $request, $id)
    {
        $project = FeaturedProject::find($id);
        if (!$project) {
            return back()->with('error', 'Project not found.');
        }

        $request->validate([
            'content' => 'required|string|max:1000',
            'visitor_name' => 'nullable|string|max:255',
        ]);

        $project->comments()->create([
            'user_id' => Auth::id(),
            'visitor_name' => Auth::check() ? Auth::user()->name : ($request->visitor_name ?: 'Anonymous'),
            'content' => $request->content,
        ]);

        return back()->with('success', 'Comment added successfully.');
    }

    public function incrementView(Request $request, $id)
    {
        $project = FeaturedProject::query()->select(['id', 'views_count'])->find($id);
        if (!$project) {
            return response()->json(['error' => 'Not found'], 404);
        }

        FeaturedProject::whereKey($project->id)->increment('views_count');

        return response()->json([
            'views' => (int) $project->views_count + 1,
        ]);
    }

    public function incrementLike(Request $request, $id)
    {
        $project = FeaturedProject::query()->select(['id', 'likes_count'])->find($id);
        if (!$project) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $visitorKey = $this->visitorKey($request);

        try {
            $like = FeaturedProjectLike::query()->firstOrCreate(
                [
                    'featured_project_id' => $project->id,
                    'visitor_key' => $visitorKey,
                ],
                [
                    'user_id' => Auth::id(),
                ]
            );
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'likes' => (int) $project->likes_count,
                'liked' => true,
                'already_liked' => true,
            ]);
        }

        if (!$like->wasRecentlyCreated) {
            return response()->json([
                'likes' => (int) $project->likes_count,
                'liked' => true,
                'already_liked' => true,
            ]);
        }

        FeaturedProject::whereKey($project->id)->increment('likes_count');

        return response()->json([
            'likes' => (int) $project->likes_count + 1,
            'liked' => true,
            'already_liked' => false,
        ]);
    }

    private function visitorKey(Request $request): string
    {
        if (Auth::check()) {
            return 'user:' . Auth::id();
        }

        $sessionKey = $request->session()->get('community_visitor_key');
        if (!$sessionKey) {
            $sessionKey = 'guest:' . Str::uuid()->toString();
            $request->session()->put('community_visitor_key', $sessionKey);
        }

        return $sessionKey;
    }

    private function likedProjectIdsForVisitor(Request $request, array $projectIds): array
    {
        if (empty($projectIds)) {
            return [];
        }

        return FeaturedProjectLike::whereIn('featured_project_id', $projectIds)
            ->where('visitor_key', $this->visitorKey($request))
            ->pluck('featured_project_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
