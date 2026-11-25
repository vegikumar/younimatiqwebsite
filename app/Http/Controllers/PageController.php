<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Setting;
use App\Support\SeoHelper;

class PageController extends Controller
{
    public function show(string $slug)
    {
        $page = Page::where('slug', $slug)->where('is_published', true)->firstOrFail();

        $seoTitle = $page->seo_metadata['title'] ?? $page->title . ' | Younimatiq';
        $seoDescription = $page->seo_metadata['description'] ?? '';
        $seoDescription = trim(strip_tags((string) $seoDescription));
        $seoImage = SeoHelper::absoluteImageUrl($page->seo_metadata['image'] ?? null);

        $breadcrumbs = [
            ['name' => 'Home', 'url' => url('/')],
            ['name' => $page->title, 'url' => route('pages.show', $page->slug)],
        ];

        return \App\Support\InertiaPage::react('Public/PageShow', [
            'page' => [
                'title' => $page->title,
                'slug' => $page->slug,
                'content' => $page->content,
            ],
            'seoTitle' => $seoTitle,
            'seoDescription' => $seoDescription,
            'seoImage' => $seoImage,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}
