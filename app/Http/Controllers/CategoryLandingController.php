<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ActivityPlayController;
use App\Models\ActivityLink;
use App\Models\Category;
use App\Models\Course;
use App\Support\ClassroomGames;
use App\Support\PublicInertiaProps;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CategoryLandingController extends Controller
{
    public function show($slug)
    {
        if (Schema::hasTable('activity_links')) {
            $activity = ActivityLink::query()->where('slug', $slug)->first();
            if ($activity) {
                if (! $activity->isPublished()) {
                    abort(404);
                }

                // Activity links are login-only React experiences (never raw public HTML).
                $signedIn = auth('web')->check()
                    || auth('parent')->check()
                    || auth('tutor')->check()
                    || auth('admin')->check();
                if (! $signedIn) {
                    return redirect()->route('login.student')
                        ->with('error', 'Please sign in to open this activity and earn XP.');
                }

                if ($meta = ClassroomGames::find($activity->slug)) {
                    $studentId = auth('web')->id();
                    if ($studentId) {
                        return redirect()->route('student.games.show', $meta['key']);
                    }

                    return \App\Support\InertiaPage::react('Student/Games/Play', [
                        'game' => $meta,
                        'backUrl' => '/',
                        'xp' => null,
                    ]);
                }

                // Uploaded HTML activities: students play in React with XP; staff get secure shell.
                if (auth('web')->check()) {
                    return redirect()->route('student.games.show', ClassroomGames::uploadKey($activity->id));
                }

                return app(ActivityPlayController::class)->shell($activity);
            }
        }

        $category = Category::where('slug', $slug)->where('is_active', true)->first();

        if (! $category) {
            abort(404);
        }

        $courses = Course::with(['tutor', 'modules', 'categories'])
            ->visibleOnSite()
            ->whereHas('categories', function ($q) use ($category) {
                $q->where('categories.is_active', true)
                    ->where('categories.id', $category->id);
            })
            ->latest()
            ->get();

        $topicAliases = [
            'math-camp' => 'math',
            'math' => 'math',
            'minecraft-modding' => 'minecraft',
            'minecraft-camp' => 'minecraft',
            'minecraft' => 'minecraft',
            'robotics-engineering' => 'robotics',
            'robotics-camp' => 'robotics',
            'robotics' => 'robotics',
            'ai-robotics' => 'robotics',
            'ai-camp' => 'robotics',
        ];

        if (isset($topicAliases[$slug])) {
            $payloadCourses = $courses->take(8)->map(function (Course $course) {
                $plain = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($course->description ?? ''))));

                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'landing_title' => $course->title,
                    'slug' => $course->slug,
                    'href' => '/courses/'.$course->slug,
                    'description' => Str::limit($plain, 80),
                    'thumbnail_url' => $course->thumbnail_url,
                    'age_group' => $course->age_group,
                ];
            })->values()->all();

            return \App\Support\InertiaPage::react('Public/Landing/MarketingTopic', [
                'topic' => $topicAliases[$slug],
                'courses' => $payloadCourses,
                'showPricing' => true,
            ]);
        }

        return \App\Support\InertiaPage::react('Public/Category/Landing', [
            'category' => PublicInertiaProps::category($category),
            'courses' => PublicInertiaProps::coursesCollection($courses),
            'template' => 'default',
        ]);
    }
}
