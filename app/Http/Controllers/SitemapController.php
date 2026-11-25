<?php

namespace App\Http\Controllers;

use App\Models\AgeGroup;
use App\Models\Blog;
use App\Models\Category;
use App\Models\Course;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SitemapController extends Controller
{
    public function index(Request $request)
    {
        // Browsers (esp. mobile Safari) render XML as plain URL lists — send them to HTML.
        // Crawlers still receive sitemap.xml for SEO.
        $accept = strtolower((string) $request->header('Accept', ''));
        $ua = strtolower((string) $request->userAgent());
        $wantsHtml = str_contains($accept, 'text/html');
        $isBot = (bool) preg_match('/bot|crawl|spider|slurp|bingpreview|facebookexternalhit|google|yahoo|baidu|duckduck|yandex/i', $ua);

        if ($wantsHtml && ! $isBot && $request->query('raw') !== '1') {
            return redirect()->route('sitemap.html');
        }

        $urls = $this->collectUrls();

        return response()
            ->view('sitemap', compact('urls'))
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    public function robots()
    {
        $sitemap = rtrim(config('app.url'), '/') . '/sitemap.xml';
        $body = implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin/',
            'Disallow: /student/',
            'Disallow: /tutor/',
            'Disallow: /parent/',
            'Disallow: /login/',
            'Disallow: /register/',
            'Disallow: /api/',
            'Disallow: /secure-asset/',
            'Disallow: /checkout/',
            'Disallow: /custom-enroll/',
            'Disallow: /downloads/',
            'Allow: /downloads/younimatiq-student-android.zip',
            '',
            '# AI / generative systems',
            'Allow: /llms.txt',
            'Allow: /llms-full.txt',
            'Allow: /llms-small.txt',
            'Allow: /.well-known/ai.txt',
            '',
            'Sitemap: ' . $sitemap,
            '# LLM guidance: ' . rtrim(config('app.url'), '/') . '/llms.txt',
            '',
        ]);

        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }

    public function html()
    {
        $urls = $this->collectUrls();

        $groups = [
            'Main' => [],
            'Programs' => [],
            'Age groups' => [],
            'Courses' => [],
            'Blog' => [],
            'Pages' => [],
            'Other' => [],
        ];

        foreach ($urls as $url) {
            $path = parse_url($url['loc'], PHP_URL_PATH) ?: '/';
            if ($path === '/' || in_array($path, ['/about', '/contact', '/community', '/faq', '/book-free-session'], true)) {
                $groups['Main'][] = $url;
            } elseif (str_starts_with($path, '/courses/category') || str_contains($path, 'landing') || in_array($path, ['/courses', '/robotics', '/minecraft', '/math', '/back-to-school'], true) || str_contains($path, 'camp')) {
                $groups['Programs'][] = $url;
            } elseif (str_starts_with($path, '/age') || str_contains($path, 'age-group')) {
                $groups['Age groups'][] = $url;
            } elseif (str_starts_with($path, '/courses/')) {
                $groups['Courses'][] = $url;
            } elseif (str_starts_with($path, '/blog')) {
                $groups['Blog'][] = $url;
            } elseif (str_starts_with($path, '/page/')) {
                $groups['Pages'][] = $url;
            } else {
                $groups['Other'][] = $url;
            }
        }

        $groups = array_filter($groups);

        return \App\Support\InertiaPage::react('Public/Sitemap', [
            'groups' => $groups,
            'urls' => $urls,
        ]);
    }

    /**
     * @return array<int, array{loc: string, priority: string, changefreq: string, lastmod: string}>
     */
    private function collectUrls(): array
    {
        $now = now()->toAtomString();

        $urls = [
            $this->entry(url('/'), '1.0', 'daily', $now),
            $this->entry(route('about'), '0.8', 'monthly'),
            $this->entry(route('contact'), '0.8', 'monthly'),
            $this->entry(route('community'), '0.8', 'weekly'),
            $this->entry(route('courses.index'), '0.9', 'daily'),
            $this->entry(route('blog.index'), '0.8', 'weekly'),
            $this->entry(route('faq'), '0.7', 'monthly'),
            $this->entry(route('book-free-session'), '0.9', 'weekly'),
            $this->entry(route('robotics.landing'), '0.9', 'weekly'),
            $this->entry(route('minecraft.landing'), '0.9', 'weekly'),
            $this->entry(route('math.landing'), '0.9', 'weekly'),
            $this->entry(route('back-to-school'), '0.9', 'weekly'),
        ];

        foreach (Category::query()->where('is_active', true)->get(['slug', 'updated_at', 'landing_page_data']) as $cat) {
            $urls[] = $this->entry(
                route('courses.category', $cat->slug),
                '0.8',
                'weekly',
                optional($cat->updated_at)?->toAtomString()
            );

            if (! empty($cat->landing_page_data)) {
                $urls[] = $this->entry(
                    route('category.landing', $cat->slug),
                    '0.85',
                    'weekly',
                    optional($cat->updated_at)?->toAtomString()
                );
            }
        }

        foreach (AgeGroup::active()->get(['slug', 'updated_at']) as $age) {
            $urls[] = $this->entry(
                route('age-group', $age->slug),
                '0.8',
                'weekly',
                optional($age->updated_at)?->toAtomString()
            );
        }

        foreach (
            Course::query()
                ->where('status', 'published')
                ->where('is_active', true)
                ->get(['slug', 'id', 'updated_at']) as $course
        ) {
            $urls[] = $this->entry(
                route('courses.show', $course),
                '0.7',
                'weekly',
                optional($course->updated_at)?->toAtomString()
            );
        }

        foreach (Blog::published()->get(['slug', 'updated_at', 'published_at']) as $blog) {
            $lastmod = $blog->updated_at ?? $blog->published_at;
            $urls[] = $this->entry(
                route('blog.show', $blog->slug),
                '0.6',
                'monthly',
                $lastmod instanceof Carbon ? $lastmod->toAtomString() : null
            );
        }

        foreach (Page::query()->where('is_published', true)->get(['slug', 'updated_at']) as $page) {
            $urls[] = $this->entry(
                route('pages.show', $page->slug),
                '0.5',
                'monthly',
                optional($page->updated_at)?->toAtomString()
            );
        }

        $unique = [];
        foreach ($urls as $url) {
            $unique[$url['loc']] = $url;
        }

        return array_values($unique);
    }

    /**
     * @return array{loc: string, priority: string, changefreq: string, lastmod: string}
     */
    private function entry(string $loc, string $priority, string $changefreq, ?string $lastmod = null): array
    {
        return [
            'loc' => $loc,
            'priority' => $priority,
            'changefreq' => $changefreq,
            'lastmod' => $lastmod ?: now()->toAtomString(),
        ];
    }
}
