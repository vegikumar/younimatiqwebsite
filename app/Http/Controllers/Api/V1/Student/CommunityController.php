<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\FeaturedProject;
use App\Models\FeaturedProjectLike;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CommunityController extends Controller
{
    use RespondsWithJson;

    public function index(Request $request)
    {
        $featuredProjects = FeaturedProject::with(['comments' => fn ($q) => $q->with('user')->orderBy('created_at'), 'comments.replies' => fn ($q) => $q->with('user')->orderBy('created_at')])->where('is_active', true)->orderByDesc('created_at')->get();
        $testimonials = Testimonial::where('is_active', true)->orderBy('sort_order')->orderByDesc('created_at')->get();
        $likedIds = $this->likedProjectIds($request, $featuredProjects->pluck('id')->all());

        return $this->success([
            'projects' => $featuredProjects->map(fn (FeaturedProject $p) => [
                'id' => $p->id,
                'title' => $p->title,
                'author_name' => $p->author_name,
                'category' => $p->category,
                'project_url' => $p->project_url,
                'media_url' => $this->absoluteMediaUrl($p->video_path),
                'media_type' => $this->detectMediaType($p->video_path),
                'views_count' => (int) $p->views_count,
                'likes_count' => (int) $p->likes_count,
                'liked' => in_array($p->id, $likedIds, true),
                'comments' => $p->comments->whereNull('parent_id')->map(fn ($c) => [
                    'id' => $c->id,
                    'parent_id' => $c->parent_id,
                    'author_name' => $c->visitor_name ?: ($c->user?->name ?? 'Anonymous'),
                    'author_avatar_url' => $c->user?->avatar_url,
                    'content' => $c->content,
                    'created_at' => $c->created_at?->toIso8601String(),
                    'replies' => $c->replies->map(fn ($r) => [
                        'id' => $r->id,
                        'parent_id' => $r->parent_id,
                        'author_name' => $r->visitor_name ?: ($r->user?->name ?? 'Anonymous'),
                        'author_avatar_url' => $r->user?->avatar_url,
                        'content' => $r->content,
                        'created_at' => $r->created_at?->toIso8601String(),
                    ])->values()->all(),
                ])->values()->all(),
            ]),
            'testimonials' => $testimonials->map(fn (Testimonial $t) => [
                'id' => $t->id,
                'name' => $t->author_name,
                'content' => $t->text,
                'rating' => $t->rating,
                'avatar_url' => $this->absoluteMediaUrl($t->avatar_path),
            ]),
        ]);
    }

    private function absoluteMediaUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $relative = ltrim(str_replace('/storage/', '', $path), '/');

        return asset('storage/' . $relative);
    }

    private function detectMediaType(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $pathOnly = parse_url($path, PHP_URL_PATH) ?: $path;
        $ext = strtolower(pathinfo($pathOnly, PATHINFO_EXTENSION));

        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true)) {
            return 'image';
        }

        if (in_array($ext, ['mp4', 'webm', 'mov', 'm4v', 'avi', 'mkv', 'ogg'], true)) {
            return 'video';
        }

        // Column is historically video_path — default to video for playable media.
        return 'video';
    }

    public function storeComment(Request $request, $id)
    {
        $project = FeaturedProject::find($id);
        if (! $project) {
            return $this->error('Not found', 404);
        }

        $data = $request->validate([
            'content' => 'required|string|max:1000',
            'parent_id' => 'nullable|integer|exists:featured_project_comments,id',
        ]);

        $comment = $project->comments()->create([
            'user_id' => $request->user()->id,
            'parent_id' => $data['parent_id'] ?? null,
            'visitor_name' => $request->user()->name,
            'content' => $data['content'],
        ]);

        return $this->success([
            'comment' => [
                'id' => $comment->id,
                'parent_id' => $comment->parent_id,
                'author_name' => $comment->visitor_name,
                'author_avatar_url' => $request->user()->avatar_url,
                'content' => $comment->content,
                'created_at' => $comment->created_at?->toIso8601String(),
            ],
        ], 'Comment added.', 201);
    }

    public function view(Request $request, $id)
    {
        $project = FeaturedProject::query()->select(['id', 'views_count'])->find($id);
        if (! $project) {
            return $this->error('Not found', 404);
        }

        FeaturedProject::whereKey($project->id)->increment('views_count');

        return $this->success([
            'views' => (int) $project->views_count + 1,
        ]);
    }

    public function like(Request $request, $id)
    {
        $project = FeaturedProject::query()->select(['id', 'likes_count'])->find($id);
        if (! $project) {
            return $this->error('Not found', 404);
        }

        $visitorKey = 'user:' . $request->user()->id;

        try {
            $like = FeaturedProjectLike::query()->firstOrCreate(
                [
                    'featured_project_id' => $project->id,
                    'visitor_key' => $visitorKey,
                ],
                [
                    'user_id' => $request->user()->id,
                ]
            );
        } catch (\Illuminate\Database\QueryException $e) {
            return $this->success([
                'likes' => (int) $project->likes_count,
                'liked' => true,
                'already_liked' => true,
            ]);
        }

        if (! $like->wasRecentlyCreated) {
            return $this->success([
                'likes' => (int) $project->likes_count,
                'liked' => true,
                'already_liked' => true,
            ]);
        }

        FeaturedProject::whereKey($project->id)->increment('likes_count');

        return $this->success([
            'likes' => (int) $project->likes_count + 1,
            'liked' => true,
            'already_liked' => false,
        ]);
    }

    private function likedProjectIds(Request $request, array $projectIds): array
    {
        if ($projectIds === [] || ! $request->user()) {
            return [];
        }

        return FeaturedProjectLike::whereIn('featured_project_id', $projectIds)
            ->where(function ($q) use ($request) {
                $q->where('user_id', $request->user()->id)
                    ->orWhere('visitor_key', 'user:' . $request->user()->id);
            })
            ->pluck('featured_project_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
