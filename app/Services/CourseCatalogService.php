<?php

namespace App\Services;

use App\Models\Course;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CourseCatalogService
{
    public function filter(Request $request, int $perPage = 12): LengthAwarePaginator
    {
        $query = Course::with(['modules', 'tutor', 'categories'])
            ->visibleOnSite();

        $this->applySearch($query, $request->input('search'));
        $this->applyCategories($query, (array) $request->input('categories', []));
        $this->applyGrade($query, $request->input('grade'));
        $this->applyTags($query, (array) $request->input('tags', []));
        $this->applyPriceTypes($query, (array) $request->input('price_types', []));
        $this->applyClassTypes($query, (array) $request->input('class_types', []));

        $this->applySort($query, $request->input('sort', 'default'));

        return $query->paginate($perPage)->withQueryString();
    }

    public function suggest(string $query, int $limit = 8): Collection
    {
        $query = trim($query);
        if (strlen($query) < 2) {
            return collect();
        }

        return Course::with('categories')
            ->visibleOnSite()
            ->where(function (Builder $q) use ($query) {
                $q->where('title', 'like', '%'.$query.'%')
                    ->orWhere('description', 'like', '%'.$query.'%')
                    ->orWhereHas('categories', function (Builder $c) use ($query) {
                        $c->where('categories.is_active', true)
                            ->where('name', 'like', '%'.$query.'%');
                    });
            })
            ->orderBy('title')
            ->limit($limit)
            ->get(['id', 'title', 'slug', 'thumbnail_url', 'age_group', 'is_camp', 'course_type']);
    }

    protected function applySearch(Builder $query, ?string $search): void
    {
        if (! $search) {
            return;
        }

        $query->where(function (Builder $q) use ($search) {
            $q->where('title', 'like', '%'.$search.'%')
                ->orWhere('description', 'like', '%'.$search.'%');
        });
    }

    protected function applyCategories(Builder $query, array $slugs): void
    {
        $slugs = array_filter($slugs);
        if ($slugs === []) {
            return;
        }

        $keywords = config('course-catalog.camp_slug_keywords', []);

        $query->where(function (Builder $q) use ($slugs, $keywords) {
            foreach ($slugs as $slug) {
                $q->orWhere(function (Builder $inner) use ($slug, $keywords) {
                    $inner->whereHas(
                        'categories',
                        fn (Builder $cat) => $cat->where('categories.is_active', true)->where('slug', $slug)
                    );

                    if (isset($keywords[$slug])) {
                        foreach ($keywords[$slug] as $keyword) {
                            $inner->orWhere('title', 'like', '%'.$keyword.'%')
                                ->orWhere('description', 'like', '%'.$keyword.'%');
                        }
                    }
                });
            }
        });
    }

    protected function applyGrade(Builder $query, ?string $grade): void
    {
        $grade = trim((string) $grade);
        if ($grade === '') {
            return;
        }

        $grades = config('course-catalog.grades', []);
        if (isset($grades[$grade])) {
            $n = (int) $grades[$grade]['value'];
            $query->where('grade_min', '<=', $n)->where('grade_max', '>=', $n);

            return;
        }

        if ($grade === 'k' || $grade === 'K') {
            $query->where('grade_min', '<=', 0)->where('grade_max', '>=', 0);

            return;
        }

        if (is_numeric($grade) || $grade === '0') {
            $n = (int) $grade;
            $query->where('grade_min', '<=', $n)->where('grade_max', '>=', $n);

            return;
        }

        $bands = config('course-catalog.grade_bands', []);
        if (! isset($bands[$grade])) {
            return;
        }

        $bandMin = $bands[$grade]['grade_min'];
        $bandMax = $bands[$grade]['grade_max'];

        $query->where(function (Builder $q) use ($bandMin, $bandMax) {
            $q->where('grade_min', '<=', $bandMax)
              ->where('grade_max', '>=', $bandMin);
        });
    }

    protected function applyTags(Builder $query, array $tags): void
    {
        $tags = array_filter($tags);
        if ($tags === []) {
            return;
        }

        $ageGroupTags = config('course-catalog.age_group_tags', []);

        // Prefer live DB age groups if available
        try {
            if (class_exists(\App\Models\AgeGroup::class) && \Illuminate\Support\Facades\Schema::hasTable('age_groups')) {
                $fromDb = \App\Models\AgeGroup::active()->get();
                if ($fromDb->isNotEmpty()) {
                    $ageGroupTags = $fromDb->mapWithKeys(fn ($g) => [
                        $g->slug => array_filter([$g->label(), $g->name, $g->age_range, $g->slug]),
                    ])->all();
                }
            }
        } catch (\Throwable) {
            // keep config fallback
        }

        // Also merge controller-passed request tags already encoding age_group_tags via config mutation
        $requestTags = config('course-catalog.age_group_tags', $ageGroupTags);
        if (is_array($requestTags) && $requestTags !== []) {
            $ageGroupTags = $requestTags;
        }

        $query->where(function (Builder $q) use ($tags, $ageGroupTags) {
            foreach ($tags as $tag) {
                $q->orWhere(function (Builder $inner) use ($tag, $ageGroupTags) {
                    $matchedGroup = false;
                    foreach ($ageGroupTags as $group => $groupTags) {
                        if ($tag === $group || in_array($tag, $groupTags, true)) {
                            $inner->orWhere('age_group', $group);
                            $matchedGroup = true;
                        }
                    }

                    if (! $matchedGroup) {
                        $inner->where('title', 'like', '%'.$tag.'%')
                            ->orWhere('description', 'like', '%'.$tag.'%');
                    }
                });
            }
        });
    }

    protected function applyPriceTypes(Builder $query, array $types): void
    {
        $types = array_filter($types);
        if ($types === []) {
            return;
        }

        $query->where(function (Builder $q) use ($types) {
            if (in_array('free', $types, true)) {
                $q->orWhere('price', '<=', 0);
            }
            if (in_array('paid', $types, true)) {
                $q->orWhere('price', '>', 0);
            }
        });
    }

    protected function applyClassTypes(Builder $query, array $types): void
    {
        $types = array_filter($types);
        if ($types === []) {
            return;
        }

        $query->whereIn('course_type', $types);
    }

    protected function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'title_asc' => $query->orderBy('title'),
            'title_desc' => $query->orderByDesc('title'),
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'newest' => $query->latest(),
            default => $query->orderByDesc('created_at'),
        };
    }
}
