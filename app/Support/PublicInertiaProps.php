<?php

namespace App\Support;

use App\Models\Blog;
use App\Models\Category;
use App\Models\Course;
use App\Models\Faq;
use App\Models\FeaturedProject;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicInertiaProps
{
    public static function courseCard(Course $course, array $extra = []): array
    {
        $plainDescription = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($course->description ?? ''))));

        return array_merge([
            'id' => $course->id,
            'title' => $course->title,
            'slug' => $course->slug,
            'description' => $plainDescription,
            'thumbnail_url' => $course->thumbnail_url,
            'video_url' => $course->video_url,
            'price' => (float) ($course->price ?? 0),
            'price_usd' => $course->price_usd,
            'price_aed' => $course->price_aed,
            'price_inr' => $course->price_inr,
            'per_class_price' => $course->per_class_price,
            'age_group' => $course->age_group,
            'is_camp' => (bool) $course->is_camp,
            'course_type' => $course->course_type,
            'self_paced_enabled' => (bool) $course->self_paced_enabled,
            'tutor' => $course->relationLoaded('tutor') && $course->tutor ? [
                'id' => $course->tutor->id,
                'name' => $course->tutor->name,
            ] : null,
            'categories' => ($course->relationLoaded('categories') ? $course->categories : collect())->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
            ])->values()->all(),
        ], $extra);
    }

    public static function coursesCollection(Collection $courses): array
    {
        return $courses->map(fn (Course $c) => static::courseCard($c))->values()->all();
    }

    public static function paginatedCourses(LengthAwarePaginator $paginator): array
    {
        return [
            'data' => collect($paginator->items())->map(fn (Course $c) => static::courseCard($c))->all(),
            'links' => $paginator->linkCollection()->toArray(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
        ];
    }

    public static function category(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'image' => $category->image,
        ];
    }

    public static function categories(Collection $categories): array
    {
        return $categories->map(fn (Category $c) => static::category($c))->values()->all();
    }

    public static function blogCard(Blog $blog): array
    {
        return [
            'id' => $blog->id,
            'title' => $blog->title,
            'slug' => $blog->slug,
            'excerpt' => $blog->excerpt ?? Str::limit(strip_tags((string) $blog->content), 160),
            'excerpt_preview' => $blog->excerpt ?? Str::limit(strip_tags((string) $blog->content), 120),
            'featured_image' => static::publicMediaUrl($blog->featured_image),
            'published_at_label' => $blog->published_at?->format('M d, Y'),
        ];
    }

    /**
     * Prefer same-origin /storage/... paths so blog images load on the current host
     * even when DB still has absolute production URLs.
     */
    public static function publicMediaUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }
        if (preg_match('#(/storage/.+)$#', $url, $m)) {
            return $m[1];
        }
        if (Str::startsWith($url, ['http://', 'https://', '//', '/'])) {
            return $url;
        }

        return '/storage/'.ltrim($url, '/');
    }

    public static function faq(Faq $faq): array
    {
        return [
            'id' => $faq->id,
            'question' => $faq->question,
            'answer' => $faq->answer,
            'category' => $faq->category,
        ];
    }

    public static function featuredProject(FeaturedProject $project): array
    {
        $path = $project->video_path;
        $mediaUrl = null;
        $isImage = false;
        if ($path) {
            if (Str::startsWith($path, 'http')) {
                $mediaUrl = $path;
            } else {
                $mediaUrl = Storage::disk('public')->url($path);
            }
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true);
        }

        return [
            'id' => $project->id,
            'title' => $project->title,
            'category' => $project->category,
            'author_name' => $project->author_name,
            'project_url' => $project->project_url,
            'views_count' => (int) ($project->views_count ?? 0),
            'likes_count' => (int) ($project->likes_count ?? 0),
            'media_url' => $mediaUrl,
            'is_image' => $isImage,
        ];
    }

    public static function courseShow(Course $course, array $ctx = []): array
    {
        $seo = is_array($course->seo_metadata) ? $course->seo_metadata : [];

        return array_merge([
            'id' => $course->id,
            'title' => $course->title,
            'slug' => $course->slug,
            'description' => $course->description,
            'learning_outcomes' => $course->learning_outcomes ?? [],
            'highlights' => $course->highlights ?? [],
            'thumbnail_url' => $course->thumbnail_url,
            'video_url' => $course->video_url,
            'price' => (float) ($course->price ?? 0),
            'price_usd' => $course->price_usd,
            'price_aed' => $course->price_aed,
            'price_inr' => $course->price_inr,
            'per_class_price' => $course->per_class_price,
            'number_of_classes' => $course->number_of_classes,
            'course_type' => $course->course_type,
            'format' => $course->format,
            'age_group' => $course->age_group,
            'age_label' => $seo['age_label'] ?? null,
            'welcome_message' => $course->welcome_message,
            'welcome_video_url' => $course->welcome_video_url,
            'tutor' => $course->tutor ? [
                'id' => $course->tutor->id,
                'name' => $course->tutor->name,
                'avatar' => $course->tutor->avatar,
                'bio' => $course->tutor->bio ?? null,
            ] : null,
            'categories' => $course->categories->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
            ])->values()->all(),
            'modules' => ($course->modules ?? collect())->map(fn ($m) => [
                'id' => $m->id,
                'title' => $m->title,
                'order' => $m->order,
            ])->values()->all(),
            'units' => ($course->units ?? collect())->map(fn ($u) => [
                'id' => $u->id,
                'title' => $u->title,
                'description' => $u->description,
                'order' => $u->order,
                'modules' => ($u->modules ?? collect())->map(fn ($m) => [
                    'id' => $m->id,
                    'title' => $m->title,
                    'content_type' => $m->content_type ?? $m->module_type ?? null,
                ])->values()->all(),
            ])->values()->all(),
            'reviews' => ($course->reviews ?? collect())->map(fn ($r) => [
                'id' => $r->id,
                'rating' => (int) $r->rating,
                'comment' => $r->comment,
                'user_name' => $r->user?->name ?? 'Student',
                'created_at_label' => $r->created_at?->format('M j, Y'),
            ])->values()->all(),
        ], $ctx);
    }

    public static function breadcrumbs(array $items): array
    {
        return array_values($items);
    }
}
