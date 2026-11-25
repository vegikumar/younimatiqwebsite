<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Course;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class NavigationData
{
    public const CACHE_KEY = 'inertia_navigation_v4';

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget('inertia_navigation_v3');
        Cache::forget('public_home_payload_v2');
    }

    /**
     * Public header dropdown payload.
     *
     * @return array{categories: list<array<string, mixed>>, gradeBands: list<array<string, mixed>>}
     */
    public static function payload(): array
    {
        try {
            return Cache::remember(self::CACHE_KEY, now()->addMinutes(10), function () {
                return [
                    'categories' => self::categories(),
                    'gradeBands' => self::gradeBands(),
                ];
            });
        } catch (\Throwable) {
            return ['categories' => [], 'gradeBands' => []];
        }
    }

    /**
     * @return list<array{id: int, name: string, slug: string, icon: mixed, courses_count: int}>
     */
    public static function categories(): array
    {
        if (! Schema::hasTable('categories')) {
            return [];
        }

        return Category::query()
            ->where('is_active', true)
            ->whereHas('courses', fn ($q) => $q->visibleOnSite())
            ->withCount(['courses as courses_count' => fn ($q) => $q->visibleOnSite()])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'icon'])
            ->map(fn (Category $c) => [
                'id' => (int) $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'icon' => $c->icon,
                // Same visibility rules as /courses/category/{slug}
                'courses_count' => (int) $c->courses_count,
            ])
            ->values()
            ->all();
    }

    /**
     * Ordered K–12 rows. Counts match /courses/grades/{key} overlap filter.
     *
     * @return list<array{key: string, label: string, value: int, courses_count: int}>
     */
    public static function gradeBands(): array
    {
        $grades = config('course-catalog.grades', []);
        if ($grades === []) {
            return [];
        }

        $ranges = collect();
        if (Schema::hasTable('courses')) {
            $ranges = Course::visibleOnSite()
                ->whereNotNull('grade_min')
                ->whereNotNull('grade_max')
                ->get(['grade_min', 'grade_max']);
        }

        $rows = [];
        foreach ($grades as $key => $grade) {
            $n = (int) ($grade['value'] ?? 0);
            $count = $ranges
                ->filter(fn ($c) => (int) $c->grade_min <= $n && (int) $c->grade_max >= $n)
                ->count();

            $rows[] = [
                'key' => (string) $key,
                'label' => $grade['label'] ?? ('Grade '.$key),
                'value' => $n,
                'courses_count' => (int) $count,
            ];
        }

        // Keep Kindergarten first even if PHP/JS object key ordering changes.
        usort($rows, function (array $a, array $b) {
            return $a['value'] <=> $b['value'];
        });

        return $rows;
    }
}
