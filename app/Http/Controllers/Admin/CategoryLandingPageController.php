<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;

class CategoryLandingPageController extends Controller
{
    public function edit(Category $category)
    {
        $lpData = is_array($category->landing_page_data) ? $category->landing_page_data : [];

        // Ensure defaults exist
        $lpData = array_merge([
            'hero_title' => '',
            'hero_subtitle' => '',
            'theme_color' => 'green',
            'courses_title' => '',
            'videos_title' => '',
            'videos_subtitle' => '',
            'videos_badge' => '',
            'features_title' => '',
            'features_badge' => '',
            'reviews_title' => '',
            'reviews_subtitle' => '',
            'curriculum_title' => '',
            'curriculum_subtitle' => '',
            'curriculum_badge' => '',
            'bottom_banner_title' => '',
            'bottom_banner_subtitle' => '',
            'faq_title' => '',
            'videos' => [],
            'features' => [],
            'reviews' => [],
            'curriculum' => [],
            'faqs' => [],
        ], $lpData);

        return \App\Support\InertiaPage::react('Admin/Categories/LandingPage', [
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
            ],
            'lpData' => $lpData,
            'routes' => [
                'categoriesIndex' => route('admin.categories.index'),
                'update' => route('admin.categories.landing-page.update', $category),
            ],
        ]);
    }

    public function update(Request $request, Category $category, ImageUploadService $images)
    {
        $request->validate([
            'hero_title' => 'nullable|string|max:255',
            'hero_subtitle' => 'nullable|string',
            'theme_color' => 'nullable|string|max:50',
            'courses_title' => 'nullable|string|max:255',
            'videos_title' => 'nullable|string|max:255',
            'videos_subtitle' => 'nullable|string',
            'videos_badge' => 'nullable|string|max:255',
            'features_title' => 'nullable|string|max:255',
            'features_badge' => 'nullable|string|max:255',
            'reviews_title' => 'nullable|string|max:255',
            'reviews_subtitle' => 'nullable|string',
            'curriculum_title' => 'nullable|string|max:255',
            'curriculum_subtitle' => 'nullable|string',
            'curriculum_badge' => 'nullable|string|max:255',
            'bottom_banner_title' => 'nullable|string|max:255',
            'bottom_banner_subtitle' => 'nullable|string',
            'faq_title' => 'nullable|string|max:255',

            'videos' => 'nullable|array',
            'videos.*.file' => 'nullable|file|mimes:mp4,mov,avi,jpeg,png,jpg,webp|max:51200', // max 50MB
            'features' => 'nullable|array',
            'reviews' => 'nullable|array',
            'curriculum' => 'nullable|array',
            'faqs' => 'nullable|array',
        ]);

        $videosData = $request->input('videos', []);

        // Handle Video Uploads
        if ($request->hasFile('videos')) {
            foreach ($request->file('videos') as $index => $videoFile) {
                if (isset($videoFile['file'])) {
                    $path = $images->storePath($videoFile['file'], 'landing_pages/videos', 'public');
                    $videosData[$index]['url'] = $path; // Overwrite the url with the new path
                }
            }
        }

        $lpData = [
            'hero_title' => $request->input('hero_title'),
            'hero_subtitle' => $request->input('hero_subtitle'),
            'theme_color' => $request->input('theme_color', 'green'),
            'courses_title' => $request->input('courses_title'),
            'videos_title' => $request->input('videos_title'),
            'videos_subtitle' => $request->input('videos_subtitle'),
            'videos_badge' => $request->input('videos_badge'),
            'features_title' => $request->input('features_title'),
            'features_badge' => $request->input('features_badge'),
            'reviews_title' => $request->input('reviews_title'),
            'reviews_subtitle' => $request->input('reviews_subtitle'),
            'curriculum_title' => $request->input('curriculum_title'),
            'curriculum_subtitle' => $request->input('curriculum_subtitle'),
            'curriculum_badge' => $request->input('curriculum_badge'),
            'bottom_banner_title' => $request->input('bottom_banner_title'),
            'bottom_banner_subtitle' => $request->input('bottom_banner_subtitle'),
            'faq_title' => $request->input('faq_title'),

            // array_values to reset keys for alpinejs looping
            'videos' => array_values($videosData),
            'features' => array_values($request->input('features', [])),
            'reviews' => array_values($request->input('reviews', [])),
            'curriculum' => array_values($request->input('curriculum', [])),
            'faqs' => array_values($request->input('faqs', [])),
        ];

        // Filter out empty rows if any
        $lpData['videos'] = array_filter($lpData['videos'], fn ($v) => ! empty($v['url']));
        $lpData['features'] = array_filter($lpData['features'], fn ($f) => ! empty($f['title']));
        $lpData['reviews'] = array_filter($lpData['reviews'], fn ($r) => ! empty($r['text']));
        $lpData['curriculum'] = array_filter($lpData['curriculum'], fn ($c) => ! empty($c['title']));
        $lpData['faqs'] = array_filter($lpData['faqs'], fn ($f) => ! empty($f['question']));

        // Ensure they are re-indexed cleanly for json storage
        $lpData['videos'] = array_values($lpData['videos']);
        $lpData['features'] = array_values($lpData['features']);
        $lpData['reviews'] = array_values($lpData['reviews']);
        $lpData['curriculum'] = array_values($lpData['curriculum']);
        $lpData['faqs'] = array_values($lpData['faqs']);

        $category->update(['landing_page_data' => $lpData]);

        return back()->with('success', 'Landing Page updated successfully.');
    }
}
