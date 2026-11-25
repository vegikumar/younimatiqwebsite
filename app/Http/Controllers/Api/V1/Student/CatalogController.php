<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\AgeGroup;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Services\StudentApiService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    use RespondsWithJson;

    public function __construct(private StudentApiService $studentApi)
    {
    }

    public function index(Request $request)
    {
        $query = Course::with(['tutor', 'categories', 'modules'])
            ->where('status', 'published')
            ->where('is_active', true);

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function (Builder $q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('category')) {
            $slug = $request->string('category')->toString();
            $query->whereHas('categories', fn (Builder $q) => $q->where('slug', $slug));
        }

        if ($request->filled('age_group')) {
            $query->where('age_group', $request->string('age_group')->toString());
        }

        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'title_asc' => $query->orderBy('title'),
            'title_desc' => $query->orderByDesc('title'),
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            default => $query->orderByDesc('created_at'),
        };

        $userId = $request->user()->id;
        $enrolledIds = Enrollment::where('user_id', $userId)
            ->whereIn('status', ['active', 'completed'])
            ->pluck('course_id')
            ->all();

        $courses = $query->paginate(min((int) $request->input('per_page', 20), 50));

        $items = collect($courses->items())->map(
            fn (Course $course) => $this->studentApi->formatCatalogCourse(
                $course,
                in_array($course->id, $enrolledIds, true)
            )
        );

        return $this->success([
            'courses' => $items,
            'meta' => [
                'current_page' => $courses->currentPage(),
                'last_page' => $courses->lastPage(),
                'per_page' => $courses->perPage(),
                'total' => $courses->total(),
            ],
        ]);
    }

    public function filters()
    {
        $categories = Category::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn (Category $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
            ]);

        $ageGroups = AgeGroup::active()->get()->map(fn (AgeGroup $g) => [
            'slug' => $g->slug,
            'name' => $g->name,
            'label' => $g->label(),
            'age_range' => $g->age_range,
        ]);

        return $this->success([
            'categories' => $categories,
            'age_groups' => $ageGroups,
            'sorts' => [
                ['value' => 'newest', 'label' => 'Newest'],
                ['value' => 'title_asc', 'label' => 'Title A–Z'],
                ['value' => 'title_desc', 'label' => 'Title Z–A'],
                ['value' => 'price_asc', 'label' => 'Price low–high'],
                ['value' => 'price_desc', 'label' => 'Price high–low'],
            ],
        ]);
    }

    public function show(Request $request, $course)
    {
        $model = $this->resolvePublishedCourse($course);
        if (! $model) {
            return $this->error('Course not found.', 404);
        }

        $model->load(['tutor', 'categories', 'modules', 'units.modules', 'reviews.user']);

        $isEnrolled = $model->isEnrolled($request->user()->id);

        return $this->success([
            'course' => $this->studentApi->formatCatalogCourse($model, $isEnrolled, true),
        ]);
    }

    private function resolvePublishedCourse(string|int $key): ?Course
    {
        return Course::query()
            ->where('status', 'published')
            ->where('is_active', true)
            ->where(function (Builder $q) use ($key) {
                $q->where('id', $key)->orWhere('slug', $key);
            })
            ->first();
    }
}
