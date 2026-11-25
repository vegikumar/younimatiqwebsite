<?php

namespace App\View\Composers;

use App\Models\AgeGroup;
use App\Models\Category;
use App\Support\NavigationData;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class NavigationComposer
{
    public function compose(View $view): void
    {
        $navCategories = collect();
        $campCategories = collect();
        $ageGroups = collect();
        $gradeBands = [];

        try {
            $payload = NavigationData::payload();

            $navCategories = collect($payload['categories'] ?? [])->map(function (array $c) {
                return (object) $c;
            });

            // Blade header expects key => band map; keep value-ordered.
            foreach ($payload['gradeBands'] ?? [] as $band) {
                $key = (string) ($band['key'] ?? $band['value'] ?? '');
                $gradeBands[$key] = [
                    'label' => $band['label'] ?? '',
                    'value' => (int) ($band['value'] ?? 0),
                    'courses_count' => (int) ($band['courses_count'] ?? 0),
                ];
            }

            if (Schema::hasTable('categories')) {
                $campCategories = Category::query()
                    ->where('is_active', true)
                    ->where('slug', 'like', '%-camp')
                    ->whereHas('courses', fn ($c) => $c->visibleOnSite()->where('is_camp', true))
                    ->withCount(['courses' => fn ($q) => $q->visibleOnSite()->where('is_camp', true)])
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get(['id', 'name', 'slug']);
            }

            if (Schema::hasTable('age_groups')) {
                $ageGroups = AgeGroup::active()
                    ->withCount(['courses' => fn ($q) => $q->where('status', 'published')->where('is_active', true)])
                    ->get()
                    ->sortBy(function ($ageGroup) {
                        preg_match_all('/\d+/', $ageGroup->name, $matches);
                        $numbers = $matches[0] ?? [];
                        $min = isset($numbers[0]) ? (int) $numbers[0] : 0;
                        $max = isset($numbers[1]) ? (int) $numbers[1] : 0;

                        return $min * 1000 + $max;
                    })
                    ->values();
            }
        } catch (\Throwable) {
            //
        }

        $firstAgeSlug = $ageGroups->first()?->slug ?? 'explorers';

        $view->with(compact('navCategories', 'campCategories', 'ageGroups', 'firstAgeSlug', 'gradeBands'));
    }
}
