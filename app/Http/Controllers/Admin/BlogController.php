<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function __construct(private ImageUploadService $images) {}

    public function index()
    {
        $blogs = Blog::orderBy('created_at', 'desc')->get()->map(fn (Blog $blog) => [
            'id' => $blog->id,
            'title' => $blog->title,
            'author' => $blog->author,
            'status' => $blog->status,
            'published_at_label' => $blog->published_at?->format('M d, Y'),
        ])->values()->all();

        return \App\Support\InertiaPage::react('Admin/Blogs/Index', [
            'blogs' => $blogs,
        ]);
    }

    public function create()
    {
        return \App\Support\InertiaPage::react('Admin/Blogs/Form', [
            'blog' => $this->serializeBlog(new Blog),
            'isEdit' => false,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $data['slug'] = $request->input('slug') ? Str::slug($request->input('slug')) : Str::slug($data['title']);

        if ($data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        if ($request->hasFile('featured_image')) {
            $path = $this->images->store($request->file('featured_image'), 'blogs', 'public');
            $data['featured_image'] = $path['url'];
        }

        $data['seo_metadata'] = [
            'title' => $request->input('meta_title'),
            'description' => $request->input('meta_description'),
        ];

        Blog::create($data);

        return redirect()->route('admin.blogs.index')->with('success', 'Blog post created.');
    }

    public function edit(Blog $blog)
    {
        return \App\Support\InertiaPage::react('Admin/Blogs/Form', [
            'blog' => $this->serializeBlog($blog),
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, Blog $blog)
    {
        $data = $this->validated($request);

        $data['slug'] = $request->input('slug') ? Str::slug($request->input('slug')) : Str::slug($data['title']);

        if ($data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        if ($request->hasFile('featured_image')) {
            if ($blog->featured_image && str_starts_with($blog->featured_image, '/storage/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $blog->featured_image));
            }
            $path = $this->images->store($request->file('featured_image'), 'blogs', 'public');
            $data['featured_image'] = $path['url'];
        } else {
            // Keep existing image if no new file uploaded
            unset($data['featured_image']);
        }

        $data['seo_metadata'] = [
            'title' => $request->input('meta_title'),
            'description' => $request->input('meta_description'),
        ];

        $blog->update($data);

        return redirect()->route('admin.blogs.index')->with('success', 'Blog post updated.');
    }

    public function destroy(Blog $blog)
    {
        $blog->delete();

        return back()->with('success', 'Blog deleted.');
    }

    private function serializeBlog(Blog $blog): array
    {
        return [
            'id' => $blog->id,
            'title' => $blog->title,
            'slug' => $blog->slug,
            'excerpt' => $blog->excerpt,
            'content' => $blog->content,
            'featured_image' => $blog->featured_image,
            'author' => $blog->author,
            'status' => $blog->status ?? 'draft',
            'published_at_date' => $blog->published_at?->format('Y-m-d'),
            'seo_metadata' => $blog->seo_metadata ?? [],
        ];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'featured_image' => 'nullable|file|image|max:5120',
            'author' => 'nullable|string|max:255',
            'status' => 'required|in:draft,published',
            'published_at' => 'nullable|date',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
        ]);
    }
}
