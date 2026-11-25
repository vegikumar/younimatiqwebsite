<?php

namespace App\Http\Controllers;

use App\Support\PublicInertiaProps;
use App\Support\SeoHelper;
use App\Models\Course;
use App\Models\Category;
use App\Models\Faq;
use App\Services\CourseCatalogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CourseCatalogController extends Controller
{
    public function index(Request $request, CourseCatalogService $catalog)
    {
        $courses = $catalog->filter($request);
        $categories = Category::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $ageGroups = \App\Models\AgeGroup::active()->get();
        $filterConfig = config('course-catalog') ?? require config_path('course-catalog.php');

        $filterConfig['db_categories'] = $categories->map(fn ($c) => [
            'label' => $c->name,
            'slug' => $c->slug,
        ])->values()->all();

        // Always use DB-only filter lists (no hardcoded config fallbacks in the UI)
        $filterConfig['age_tags'] = $ageGroups->map(fn ($g) => $g->label())->values()->all();
        $filterConfig['age_group_tags'] = $ageGroups->mapWithKeys(fn ($g) => [
            $g->slug => array_filter([$g->label(), $g->name, $g->age_range, $g->slug]),
        ])->all();

        return \App\Support\InertiaPage::react('Public/Courses/Index', [
            'courses' => PublicInertiaProps::paginatedCourses($courses),
            'categories' => PublicInertiaProps::categories($categories),
            'filterConfig' => $filterConfig,
        ]);
    }

    public function suggest(Request $request, CourseCatalogService $catalog)
    {
        $results = $catalog->suggest((string) $request->query('q', ''));

        return response()->json([
            'suggestions' => $results->map(fn (Course $course) => [
                'id' => $course->id,
                'title' => $course->title,
                'url' => route('courses.show', $course),
                'thumbnail' => $course->thumbnail_url,
                'age_group' => $course->age_group,
                'is_camp' => $course->is_camp,
                'course_type' => $course->course_type,
                'category_name' => $course->categories->first()?->name ?? 'Course',
            ]),
        ]);
    }

    public function show(Course $course)
    {
        $isAdmin = auth('admin')->check();
        if (($course->status !== 'published' || !$course->is_active) && !$isAdmin) {
            abort(404);
        }
        if (! $isAdmin && ! $course->categories()->where('categories.is_active', true)->exists()) {
            abort(404);
        }

        $course->load(['modules', 'units.modules', 'tutor', 'categories', 'reviews.user']);
        $seoTitle = ($course->seo_metadata['title'] ?? null) ?: $course->title . ' | Younimatiq';
        $seoDescription = is_array($course->seo_metadata)
            ? ($course->seo_metadata['description'] ?? $course->description)
            : $course->description;
        $seoDescription = trim(strip_tags((string) $seoDescription));
        $seoImage = SeoHelper::absoluteImageUrl($course->thumbnail_url);

        $isEnrolled = auth('web')->check() && $course->isEnrolled(auth('web')->id());
        $inWishlist = auth('web')->check() && \App\Models\Wishlist::where('user_id', auth('web')->id())->where('course_id', $course->id)->exists();
        $userReview = auth('web')->check()
            ? $course->reviews->firstWhere('user_id', auth('web')->id())
            : null;

        $reviewStats = [
            'count' => $course->reviews->count(),
            'average' => $course->reviews->count() > 0
                ? round($course->reviews->avg('rating'), 1)
                : 0,
            'breakdown' => collect([5, 4, 3, 2, 1])->mapWithKeys(
                fn ($star) => [$star => $course->reviews->where('rating', $star)->count()]
            ),
        ];

        $relatedCourses = Course::with('categories')
            ->visibleOnSite()
            ->where('id', '!=', $course->id)
            ->where(function ($q) use ($course) {
                $q->whereHas('categories', function ($q) use ($course) {
                    $q->where('categories.is_active', true)
                        ->whereIn('categories.id', $course->categories->pluck('id'));
                })
                ->orWhere('age_group', $course->age_group);
            })
            ->latest()
            ->limit(3)
            ->get();

        $faqs = Faq::where('is_active', true)
            ->whereIn('category', ['Classes', 'Enrollment', 'General', 'Safety'])
            ->orderBy('sort_order')
            ->limit(6)
            ->get();

        $schemaFaqs = SeoHelper::faqsToSchema($faqs);

        $courseSchema = [
            'name' => $course->title,
            'description' => $seoDescription,
            'url' => route('courses.show', $course),
            'image' => $seoImage,
            'price' => $course->price,
            'currency' => 'USD',
            'age_group' => $course->age_group,
        ];

        $breadcrumbs = [
            ['name' => 'Home', 'url' => url('/')],
            ['name' => 'Courses', 'url' => route('courses.index')],
            ['name' => $course->title, 'url' => route('courses.show', $course)],
        ];

        return \App\Support\InertiaPage::react('Public/Courses/Show', [
            'course' => PublicInertiaProps::courseShow($course),
            'seoTitle' => $seoTitle,
            'seoDescription' => $seoDescription,
            'seoImage' => $seoImage,
            'isEnrolled' => $isEnrolled,
            'inWishlist' => $inWishlist,
            'userReview' => $userReview,
            'reviewStats' => $reviewStats,
            'relatedCourses' => PublicInertiaProps::coursesCollection($relatedCourses),
            'faqs' => $faqs->map(fn (Faq $f) => PublicInertiaProps::faq($f))->values()->all(),
            'schemaFaqs' => $schemaFaqs,
            'breadcrumbs' => PublicInertiaProps::breadcrumbs($breadcrumbs),
        ]);
    }

    public function category(string $slug)
    {
        $category = Category::where('slug', $slug)->where('is_active', true)->first();

        if (!$category) {
            $aliases = [
                'robotics' => 'robotics-camp',
                'arts-and-animation' => 'arts-animation-camp',
                'arts-animation' => 'arts-animation-camp',
                'ai' => 'ai-camp',
            ];

            if (isset($aliases[$slug])) {
                $slug = $aliases[$slug];
                $category = Category::where('slug', $slug)->where('is_active', true)->first();
            }

            if (!$category) {
                $category = Category::where('slug', 'like', $slug . '%')->where('is_active', true)->firstOrFail();
            }
        }

        $courses = Course::with(['tutor', 'modules', 'categories'])
            ->visibleOnSite()
            ->whereHas('categories', function ($q) use ($category) {
                $q->where('categories.is_active', true)
                    ->where('categories.id', $category->id);
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $seoTitle = $category->seo_metadata['meta_title'] ?? ($category->name . ' Courses | Younimatiq');
        $seoDescription = $category->seo_metadata['meta_description'] ?? $category->description;
        $seoDescription = trim(strip_tags((string) $seoDescription));
        $seoImage = SeoHelper::absoluteImageUrl($category->image);

        $breadcrumbs = [
            ['name' => 'Home', 'url' => url('/')],
            ['name' => 'Courses', 'url' => route('courses.index')],
            ['name' => $category->name, 'url' => route('courses.category', $category->slug)],
        ];

        if ($courses->isEmpty()) {
            return \App\Support\InertiaPage::react('Public/Courses/LaunchingSoon', [
                'category' => PublicInertiaProps::category($category),
                'seoTitle' => $seoTitle,
            ]);
        }

        return \App\Support\InertiaPage::react('Public/Courses/Category', [
            'category' => PublicInertiaProps::category($category),
            'courses' => PublicInertiaProps::paginatedCourses($courses),
            'seoTitle' => $seoTitle,
        ]);
    }

    public function waitlist(Request $request, Category $category)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        \App\Models\CategoryWaitlist::firstOrCreate([
            'email' => $validated['email'],
            'category_id' => $category->id,
        ]);

        \Illuminate\Support\Facades\Mail::to($validated['email'])->send(new \App\Mail\CategoryWaitlistJoined($category));

        return back()->with('success', 'Thank you! We will notify you as we launch.');
    }

    public function ageGroup(string $group)
    {
        $map = [
            'explorers' => 'k-2',
            'voyagers' => '3-5',
            'pioneers' => '6-8',
        ];
        
        $grade = $map[$group] ?? 'k-2';
        return redirect()->route('grade-group', ['grade' => $grade]);
    }

    public function gradeGroup(string $grade)
    {
        $grades = config('course-catalog.grades', []);
        $bands = config('course-catalog.grade_bands', []);

        if (isset($grades[$grade])) {
            $n = (int) $grades[$grade]['value'];
            $bandMin = $n;
            $bandMax = $n;
            $labels = [
                $grade => [
                    'name' => $grades[$grade]['label'],
                    'ages' => '',
                ],
            ];
        } elseif (isset($bands[$grade])) {
            $bandMin = (int) ($bands[$grade]['grade_min'] ?? 0);
            $bandMax = (int) ($bands[$grade]['grade_max'] ?? 12);
            $labels = [
                $grade => [
                    'name' => $bands[$grade]['label'] ?? ('Grades '.$grade),
                    'ages' => $bands[$grade]['subtitle'] ?? '',
                ],
            ];
        } elseif (is_numeric($grade) || $grade === '0') {
            $n = (int) $grade;
            $bandMin = $n;
            $bandMax = $n;
            $labels = [
                $grade => [
                    'name' => $n === 0 ? 'Kindergarten' : ('Grade '.$n),
                    'ages' => '',
                ],
            ];
        } else {
            $ageGroup = \App\Models\AgeGroup::active()->where('slug', $grade)->first();
            if (! $ageGroup) {
                abort(404);
            }
            $gradeNumber = is_numeric($ageGroup->slug) ? (int) $ageGroup->slug : null;
            $bandMin = $gradeNumber ?? 0;
            $bandMax = $gradeNumber ?? 99;
            $labels = [
                $grade => [
                    'name' => $ageGroup->name,
                    'ages' => $ageGroup->age_range ? 'Ages '.$ageGroup->age_range : $ageGroup->name,
                ],
            ];
        }

        $courses = Course::with(['tutor', 'modules', 'categories'])
            ->visibleOnSite()
            ->where(function ($q) use ($bandMin, $bandMax) {
                $q->where('grade_min', '<=', $bandMax)
                  ->where('grade_max', '>=', $bandMin);
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return \App\Support\InertiaPage::react('Public/Courses/AgeGroup', [
            'group' => $grade,
            'label' => $labels[$grade]['name'] ?? 'Grades',
            'courses' => PublicInertiaProps::paginatedCourses($courses),
        ]);
    }

    public function backToSchool()
    {
        $courses = Course::with(['tutor', 'modules', 'categories'])
            ->visibleOnSite()
            ->where('is_camp', true)
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $campCategories = Category::where('is_active', true)
            ->where('slug', 'like', '%-camp')
            ->whereHas('courses', fn ($c) => $c->visibleOnSite()->where('is_camp', true))
            ->withCount(['courses' => fn ($q) => $q->visibleOnSite()->where('is_camp', true)])
            ->orderBy('sort_order')
            ->get();

        return \App\Support\InertiaPage::react('Public/Courses/BackToSchool', [
            'courses' => PublicInertiaProps::paginatedCourses($courses),
            'campCategories' => $campCategories->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'courses_count' => $c->courses_count,
            ])->values()->all(),
        ]);
    }

    public function faq()
    {
        $activeFaqs = Faq::where('is_active', true)->orderBy('sort_order')->get();
        $schemaFaqs = SeoHelper::faqsToSchema($activeFaqs);

        $faqsByCategory = $activeFaqs->groupBy('category')->map(function ($items, $category) {
            return [
                'category' => (string) $category,
                'items' => $items->map(fn (Faq $faq) => [
                    'id' => $faq->id,
                    'question' => $faq->question,
                    'answer' => $faq->answer,
                ])->values()->all(),
            ];
        })->values()->all();

        return \App\Support\InertiaPage::react('Public/Faq', [
            'faqsByCategory' => $faqsByCategory,
            'schemaFaqs' => $schemaFaqs,
        ]);
    }
}
