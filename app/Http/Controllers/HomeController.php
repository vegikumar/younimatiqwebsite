<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Category;
use App\Models\Course;
use App\Models\Faq;
use App\Models\FeaturedProject;
use App\Models\FeaturedProjectLike;
use App\Models\Setting;
use App\Support\PublicInertiaProps;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $payload = Cache::remember('public_home_payload_v2', now()->addMinutes(5), function () {
            $courses = Course::with(['tutor', 'categories'])
                ->visibleOnSite()
                ->take(6)->get();

            $topCourses = Course::with(['tutor', 'categories'])
                ->visibleOnSite()
                ->where('is_top_course', true)
                ->take(3)->get();

            $blogs = Blog::published()->latest('published_at')->take(3)->get();
            $faqs = Faq::where('is_active', true)->orderBy('sort_order')->get();
            $featuredProjects = FeaturedProject::where('is_active', true)->latest()->take(6)->get();
            $categories = Category::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
            $demoClassPrice = (float) (Setting::where('key', 'demo_class_price')->value('value') ?? 0);

            $gradeBands = collect(config('course-catalog.grades', []))
                ->map(fn ($g) => [
                    'label' => $g['label'] ?? '',
                    'value' => $g['value'] ?? 0,
                ])
                ->values()
                ->all();

            return [
                'courses' => PublicInertiaProps::coursesCollection($courses),
                'topCourses' => PublicInertiaProps::coursesCollection($topCourses),
                'blogs' => $blogs->map(fn (Blog $b) => PublicInertiaProps::blogCard($b))->values()->all(),
                'faqs' => $faqs->map(fn (Faq $f) => PublicInertiaProps::faq($f))->values()->all(),
                'testimonials' => config('homepage.testimonials', []),
                'featuredProjects' => $featuredProjects->map(fn ($p) => PublicInertiaProps::featuredProject($p))->values()->all(),
                'featuredProjectIds' => $featuredProjects->pluck('id')->map(fn ($id) => (int) $id)->all(),
                'categories' => PublicInertiaProps::categories($categories),
                'gradeBands' => $gradeBands,
                'demoClassPrice' => $demoClassPrice,
            ];
        });

        $likedProjectIds = [];
        $featuredProjectIds = $payload['featuredProjectIds'] ?? [];
        if ($featuredProjectIds !== []) {
            if (Auth::check()) {
                $visitorKey = 'user:'.Auth::id();
                $likedProjectIds = FeaturedProjectLike::whereIn('featured_project_id', $featuredProjectIds)
                    ->where('visitor_key', $visitorKey)
                    ->pluck('featured_project_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
            } elseif ($request->hasSession() && $request->session()->has('community_visitor_key')) {
                // Only read an existing key — never create a session on first home visit
                // (session cookies block public HTML edge caching).
                $visitorKey = (string) $request->session()->get('community_visitor_key');
                if ($visitorKey !== '') {
                    $likedProjectIds = FeaturedProjectLike::whereIn('featured_project_id', $featuredProjectIds)
                        ->where('visitor_key', $visitorKey)
                        ->pluck('featured_project_id')
                        ->map(fn ($id) => (int) $id)
                        ->all();
                }
            }
        }

        $gradeBands = $payload['gradeBands'];
        $categories = $payload['categories'];

        $heroGradeSelected = (string) $request->query('grade', '');
        $heroGradeLabel = 'All Grades (K-12)';
        foreach ($gradeBands as $band) {
            if ((string) ($band['value'] ?? '') === $heroGradeSelected) {
                $heroGradeLabel = $band['label'] ?? ('Grade '.$heroGradeSelected);
                break;
            }
        }
        $heroInterestSelected = (string) collect($request->query('categories', []))->first(fn ($c) => filled($c), '');
        $heroInterestLabel = 'Any Interest';
        foreach ($categories as $category) {
            if (($category['slug'] ?? null) === $heroInterestSelected) {
                $heroInterestLabel = $category['name'] ?? 'Any Interest';
                break;
            }
        }
        $heroLevelSelected = (string) $request->query('search', '');
        $heroLevelLabels = [
            '' => 'Any Level',
            'beginner' => 'Beginner',
            'intermediate' => 'Intermediate',
            'advanced' => 'Advanced',
        ];
        $heroLevelLabel = $heroLevelLabels[$heroLevelSelected] ?? 'Any Level';

        unset($payload['featuredProjectIds']);

        return \App\Support\InertiaPage::react('Public/Home', [
            ...$payload,
            'likedProjectIds' => $likedProjectIds,
            'heroFilters' => [
                'grade' => $heroGradeSelected,
                'gradeLabel' => $heroGradeLabel,
                'interest' => $heroInterestSelected,
                'interestLabel' => $heroInterestLabel,
                'level' => $heroLevelSelected,
                'levelLabel' => $heroLevelLabel,
            ],
        ]);
    }
}
