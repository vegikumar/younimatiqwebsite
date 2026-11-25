<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Course;
use Illuminate\Support\Str;

class LandingController extends Controller
{
    /**
     * Featured courses for the Children's Day campaign landing (no pricing).
     *
     * @var list<array{slug: string, label: string}>
     */
    private const CHILDRENS_DAY_COURSES = [
        ['slug' => 'minecraft-redstone-mania-lights-doors-fireworks', 'label' => 'Minecraft Redstone'],
        ['slug' => 'minecraft-coding-odyssey-build-create-erupt', 'label' => 'Minecraft Coding'],
        ['slug' => 'minecraft-english', 'label' => 'Minecraft English'],
        ['slug' => 'minecraft-financial-literacy', 'label' => 'Minecraft Finance'],
        ['slug' => '3d-design-bring-ideas-to-life', 'label' => 'Tinkercad 3D'],
        ['slug' => 'ai-powered-robotics-new', 'label' => 'AI Powered Robotics'],
        ['slug' => 'ai-coding-for-kids-learn-to-code-ai-apps-with-scratch-ages-6-10', 'label' => 'AI Coding for Kids'],
        ['slug' => 'minecraft-python-quest-build-code-explore', 'label' => 'Minecraft Python'],
        ['slug' => 'obby-master-camp', 'label' => 'Obby Roblox'],
        ['slug' => 'roblox-grow-a-garden-club', 'label' => 'Roblox Grow a Garden'],
    ];

    public function robotics()
    {
        return $this->topicPage('robotic', 'robotics');
    }

    public function minecraft()
    {
        return $this->topicPage('minecraft', 'minecraft');
    }

    public function math()
    {
        return $this->topicPage('math', 'math');
    }

    public function childrensDay()
    {
        $slugs = collect(self::CHILDRENS_DAY_COURSES)->pluck('slug')->all();
        $labels = collect(self::CHILDRENS_DAY_COURSES)->pluck('label', 'slug');

        $bySlug = Course::query()
            ->with(['categories:id,name,slug'])
            ->whereIn('slug', $slugs)
            ->where('status', 'published')
            ->where('is_active', true)
            ->get()
            ->keyBy('slug');

        $courses = collect(self::CHILDRENS_DAY_COURSES)
            ->map(function (array $item) use ($bySlug, $labels) {
                $course = $bySlug->get($item['slug']);
                if (! $course) {
                    return null;
                }

                return $this->coursePayload($course, $labels[$item['slug']] ?? null);
            })
            ->filter()
            ->values()
            ->all();

        return \App\Support\InertiaPage::react('Public/Landing/ChildrensDay', [
            'courses' => $courses,
        ]);
    }

    private function topicPage(string $categoryLike, string $topic)
    {
        $categoryIds = Category::where('slug', 'like', "%{$categoryLike}%")->where('is_active', true)->pluck('id');

        $courses = $categoryIds->count() > 0
            ? Course::query()
                ->whereHas('categories', function ($q) use ($categoryIds) {
                    $q->whereIn('categories.id', $categoryIds);
                })
                ->where('status', 'published')
                ->where('is_active', true)
                ->latest()
                ->take(8)
                ->get()
                ->map(fn (Course $course) => $this->coursePayload($course))
                ->values()
                ->all()
            : [];

        return \App\Support\InertiaPage::react('Public/Landing/MarketingTopic', [
            'topic' => $topic,
            'courses' => $courses,
            'showPricing' => true,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function coursePayload(Course $course, ?string $landingTitle = null): array
    {
        $plain = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($course->description ?? ''))));

        $category = $course->relationLoaded('categories')
            ? $course->categories->first()
            : null;

        return [
            'id' => $course->id,
            'title' => $course->title,
            'landing_title' => $landingTitle ?: $course->title,
            'slug' => $course->slug,
            'href' => '/courses/'.$course->slug,
            'description' => Str::limit($plain, 120),
            'thumbnail_url' => $course->thumbnail_url,
            'age_group' => $course->age_group,
            'grade_min' => $course->grade_min,
            'grade_max' => $course->grade_max,
            'category_id' => $category?->id,
        ];
    }
}
