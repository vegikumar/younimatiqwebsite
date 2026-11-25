<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Support\SeoHelper;

class BlogController extends Controller
{
    public function index()
    {
        $blogs = Blog::published()->orderBy('published_at', 'desc')->paginate(12)->through(
            fn (Blog $blog) => \App\Support\PublicInertiaProps::blogCard($blog)
        );

        return \App\Support\InertiaPage::react('Public/Blog/Index', [
            'blogs' => $blogs,
        ]);
    }

    public function show(string $slug)
    {
        $blog = Blog::published()->where('slug', $slug)->firstOrFail();
        $meta = $blog->seo_metadata ?? [];
        $seoTitle = $meta['title'] ?? $meta['meta_title'] ?? $blog->title . ' | Younimatiq';
        $seoDescription = $meta['description'] ?? $meta['meta_description'] ?? $blog->excerpt ?? '';
        $seoDescription = trim(strip_tags((string) $seoDescription));
        $seoImage = SeoHelper::absoluteImageUrl($blog->featured_image);

        return \App\Support\InertiaPage::react('Public/Blog/Show', [
            'blog' => [
                'title' => $blog->title,
                'slug' => $blog->slug,
                'excerpt' => $blog->excerpt,
                'content' => $blog->content,
                'featured_image' => \App\Support\PublicInertiaProps::publicMediaUrl($blog->featured_image),
                'author' => $blog->author,
                'published_at_label' => $blog->published_at?->format('F d, Y'),
            ],
            'seoTitle' => $seoTitle,
            'seoDescription' => $seoDescription,
            'seoImage' => $seoImage,
        ]);
    }
}
