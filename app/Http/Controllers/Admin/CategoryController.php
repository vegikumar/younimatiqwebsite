<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Course;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function __construct(private ImageUploadService $images) {}

    public function index()
    {
        $categories = Category::orderBy('sort_order')->get();

        return \App\Support\InertiaPage::react('Admin/Categories/Index', [
            'categories' => $categories->map(fn ($c) => \App\Support\AdminInertiaProps::category($c))->values()->all(),
        ]);
    }

    public function json()
    {
        $categories = Category::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $exists = Category::whereRaw('LOWER(name) = ?', [strtolower(trim((string) $value))])->exists();
                    if ($exists) {
                        $fail('A category with this name already exists. Use the existing one instead of creating a duplicate.');
                    }
                },
            ],
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'slug' => 'nullable|string|unique:categories,slug',
        ]);

        $categoryData = $request->only(['name', 'description', 'sort_order']);
        $slug = $request->input('slug') ? Str::slug($request->input('slug')) : Str::slug($categoryData['name']);
        if (Category::where('slug', $slug)->exists()) {
            return back()
                ->withInput()
                ->withErrors(['slug' => 'That slug is already used by another category. Choose a different name or slug.']);
        }
        $categoryData['slug'] = $slug;

        if ($request->hasFile('image')) {
            $categoryData['image'] = $this->images->storePath($request->file('image'), 'categories', 'public');
        }

        $categoryData['seo_metadata'] = [
            'meta_title' => $request->input('seo_title'),
            'meta_description' => $request->input('seo_description'),
        ];

        $categoryData['landing_page_data'] = [
            'hero_title' => 'Build, Code, and Create in '.$categoryData['name'].'.',
            'hero_subtitle' => 'Join our live, 1:1 online studio where students learn logic and engineering. Empower your child to move beyond screen time and build real-world skills.',
            'theme_color' => 'green',

            'courses_title' => 'Pick Your Path in '.$categoryData['name'],

            'videos_badge' => 'Maker Moments',
            'videos_title' => 'See Younimatiq Creators in Action.',
            'videos_subtitle' => 'Watch our students bring their imaginations to life.',
            'videos' => [
                ['url' => 'https://www.w3schools.com/html/mov_bbb.mp4', 'title' => 'Student Showcase 1'],
                ['url' => 'https://www.w3schools.com/html/mov_bbb.mp4', 'title' => 'Student Showcase 2'],
            ],

            'features_badge' => 'Why Choose Younimatiq',
            'features_title' => 'Built Different. On Purpose.',
            'features' => [
                ['icon' => '🎯', 'title' => '1:1 Personalized Coaching', 'description' => 'Certified expert tutors dedicated to your child, pacing exactly to their speed and learning style.'],
                ['icon' => '🎓', 'title' => 'Expert Curriculum', 'description' => 'Rigorous programs built by world-class educators, ensuring real-world skills.'],
                ['icon' => '🗂️', 'title' => 'Real Portfolio', 'description' => 'Every course ends in a shareable project they can be proud of.'],
                ['icon' => '🔄', 'title' => 'Learning Guarantee', 'description' => 'Free class replay if a concept doesn\'t click. We ensure mastery before moving on.'],
            ],

            'reviews_title' => 'Parents Love Younimatiq',
            'reviews_subtitle' => 'Real stories from real families around the world.',
            'reviews' => [
                ['initial' => 'S', 'name' => 'Sarah M.', 'location' => 'Parent, Texas', 'text' => 'The 1:1 coaching made all the difference! My child built their first project in just a few weeks and is completely obsessed with learning now.'],
                ['initial' => 'J', 'name' => 'James K.', 'location' => 'Parent, Sydney', 'text' => 'Worth every dollar. Incredible learning experience. The tutors are top-notch and actually know how to talk to kids.'],
                ['initial' => 'A', 'name' => 'Amanda R.', 'location' => 'Parent, London', 'text' => 'I was skeptical at first, but seeing the final project completely blew me away. Highly recommend to any parent.'],
            ],

            'curriculum_badge' => 'The Curriculum Path',
            'curriculum_title' => 'The Creative Path to Mastery',
            'curriculum_subtitle' => 'Discover how our students progress from basic logic to deploying complex projects.',
            'curriculum' => [
                ['title' => 'Learn Basics', 'description' => 'Master the fundamental concepts through interactive, hands-on modules.'],
                ['title' => 'Build Logic', 'description' => 'Translate abstract concepts into working models and understand the "why" behind the code.'],
                ['title' => 'Write Code', 'description' => 'Dive into actual syntax, writing real code to solve problems and create functionality.'],
                ['title' => 'Deploy Project', 'description' => 'Compile, test, and deploy a final portfolio-ready project to share with the world.'],
            ],

            'bottom_banner_title' => 'Ready to Turn Screen Time Into Skill Time?',
            'bottom_banner_subtitle' => 'Book a free consultation and get a personalized plan for your child — no commitment required.',

            'faq_title' => 'Frequently asked questions',
            'faqs' => [
                ['question' => 'Why 1:1 instead of group classes?', 'answer' => 'Every Younimatiq class is one-on-one with a dedicated instructor, so the pace, project difficulty, and feedback are built entirely around your child — no waiting on slower classmates and no feeling rushed by faster ones.'],
                ['question' => 'Do I need to buy hardware separately?', 'answer' => 'Most tracks use software simulators, so no hardware purchase is required to start. Physical kits are available to purchase or rent separately if desired.'],
                ['question' => 'Can I switch tracks later?', 'answer' => 'Yes — you can move up (or down) a track as your child progresses, at the start of your next plan. Your instructor and personalized pacing stay the same.'],
                ['question' => 'What happens if we miss a class?', 'answer' => 'If you miss a class, a recording of the session will be sent to you so your child doesn\'t fall behind. If anything in it isn\'t clear, one of our Master Teachers will step in to walk your child through it.'],
            ],
        ];

        Category::create($categoryData);

        return back()->with('success', 'Category created.');
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($category) {
                    $exists = Category::whereRaw('LOWER(name) = ?', [strtolower(trim((string) $value))])
                        ->where('id', '!=', $category->id)
                        ->exists();
                    if ($exists) {
                        $fail('Another category already uses this name.');
                    }
                },
            ],
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'slug' => 'nullable|string|unique:categories,slug,'.$category->id,
        ]);

        $categoryData = [
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'sort_order' => $request->input('sort_order', $category->sort_order),
        ];

        if ($request->has('is_active')) {
            $categoryData['is_active'] = $request->boolean('is_active');
        }

        if ($request->filled('slug')) {
            $slug = Str::slug($request->input('slug'));
        } else {
            $slug = Str::slug($categoryData['name']);
        }

        if (Category::where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
            return back()
                ->withInput()
                ->withErrors(['slug' => 'That slug is already used by another category.']);
        }
        $categoryData['slug'] = $slug;

        if ($request->hasFile('image')) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            $categoryData['image'] = $this->images->storePath($request->file('image'), 'categories', 'public');
        }

        // Only rewrite SEO when the edit form sent those fields
        if ($request->exists('seo_title') || $request->exists('seo_description')) {
            $seoData = is_array($category->seo_metadata) ? $category->seo_metadata : [];
            if ($request->exists('seo_title')) {
                $seoData['meta_title'] = $request->input('seo_title');
            }
            if ($request->exists('seo_description')) {
                $seoData['meta_description'] = $request->input('seo_description');
            }
            $categoryData['seo_metadata'] = $seoData;
        }

        $wasActive = (bool) $category->is_active;
        $category->update($categoryData);

        if (array_key_exists('is_active', $categoryData) && $wasActive && ! $category->is_active) {
            $deactivated = $this->deactivateCoursesForCategory($category);
            $extra = $deactivated > 0 ? " {$deactivated} course(s) set inactive." : '';

            return back()->with('success', 'Category updated.'.$extra);
        }

        return back()->with('success', 'Category updated.');
    }

    public function toggleActive(Category $category)
    {
        $category->update(['is_active' => ! $category->is_active]);

        if (! $category->is_active) {
            $deactivated = $this->deactivateCoursesForCategory($category);
            $extra = $deactivated > 0 ? " {$deactivated} course(s) set inactive." : '';

            return back()->with('success', 'Category deactivated.'.$extra);
        }

        return back()->with('success', 'Category activated.');
    }

    /**
     * When a category is turned off, mark all courses in that category inactive
     * in the admin courses list (and therefore off the public site).
     */
    protected function deactivateCoursesForCategory(Category $category): int
    {
        return Course::query()
            ->whereHas('categories', fn ($q) => $q->where('categories.id', $category->id))
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }

    public function destroy(Category $category)
    {
        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }
        $category->delete();

        return back()->with('success', 'Category deleted.');
    }
}
