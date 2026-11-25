<?php

namespace App\Http\Middleware;

use App\Models\Category;
use App\Models\Course;
use App\Models\PopupBanner;
use App\Services\TimezoneService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $impersonating = (bool) $request->session()->get('impersonated_by');
        $user = null;
        $guard = null;
        $guardOrder = $impersonating
            ? ['tutor', 'parent', 'web', 'admin']
            : ['admin', 'tutor', 'parent', 'web'];
        foreach ($guardOrder as $g) {
            if (Auth::guard($g)->check()) {
                $user = Auth::guard($g)->user();
                $guard = $g;
                break;
            }
        }

        $isPortal = $request->is('admin*') || $request->is('tutor*') || $request->is('student*') || $request->is('parent*');

        return [
            ...parent::share($request),
            'appName' => config('app.name', 'Younimatiq'),
            'appUrl' => config('app.url'),
            'csrfToken' => csrf_token(),
            'flash' => [
                'success' => fn () => $request->session()->get('success') ?? $request->session()->get('status'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
                'status' => fn () => $request->session()->get('status'),
            ],
            'errors' => function () use ($request) {
                $viewErrors = $request->session()->get('errors');
                if (! $viewErrors) {
                    return (object) [];
                }
                $messages = $viewErrors->getBag('default')->getMessages();
                if ($viewErrors->hasBag('consultation') && $viewErrors->getBag('consultation')->any()) {
                    $messages['consultation'] = $viewErrors->getBag('consultation')->all();
                }

                return $messages;
            },
            'auth' => [
                'guard' => $guard,
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => $user->status,
                    'avatar' => $user->avatar,
                    'timezone' => $user->timezone,
                    'grade' => $user->grade,
                    'grade_band' => \App\Support\GradeBand::fromGrade($user->grade !== null ? (int) $user->grade : null),
                    'leaderboard_opt_out' => (bool) ($user->leaderboard_opt_out ?? false),
                ] : null,
                'impersonating' => $impersonating,
            ],
            'displayCurrency' => session('display_currency', 'USD'),
            'locale' => app()->getLocale(),
            'viewerTimezone' => TimezoneService::sanitizeTimezone(
                (string) (session('timezone') ?: ($user->timezone ?? config('app.timezone', 'UTC')))
            ),
            'navigation' => fn () => $isPortal ? null : $this->navigationPayload(),
            'leadCategories' => fn () => $isPortal ? [] : $this->leadModalCategories(),
            'timezones' => fn () => $isPortal ? [] : TimezoneService::commonTimezones(),
            'countryCode' => strtolower((string) (session('country_code') ?: '')),
            'popupBanner' => fn () => $isPortal ? null : $this->activePopupBanner(),
        ];
    }

    protected function activePopupBanner(): ?array
    {
        try {
            if (! Schema::hasTable('popup_banners')) {
                return null;
            }

            return Cache::remember('public_popup_banner_v1', now()->addMinutes(5), function () {
                $banner = PopupBanner::query()
                    ->currentlyActive()
                    ->orderBy('sort_order')
                    ->orderByDesc('id')
                    ->first();

                return $banner?->toPublicPayload();
            });
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return list<array{id: int, name: string, slug: string, courses: list<array{id: int, title: string}>}>
     */
    protected function leadModalCategories(): array
    {
        try {
            return Cache::remember('lead_modal_categories', now()->addMinutes(30), function () {
                return Category::query()
                    ->where('is_active', true)
                    ->whereHas('courses', fn ($q) => $q->where('is_active', true))
                    ->with(['courses' => function ($q) {
                        $q->select('courses.id', 'courses.title')
                            ->where('is_active', true)
                            ->orderBy('title');
                    }])
                    ->get(['id', 'name', 'slug'])
                    ->map(fn ($c) => [
                        'id' => $c->id,
                        'name' => $c->name,
                        'slug' => $c->slug,
                        'courses' => $c->courses->map(fn ($co) => [
                            'id' => $co->id,
                            'title' => $co->title,
                        ])->values()->all(),
                    ])
                    ->values()
                    ->all();
            });
        } catch (\Throwable) {
            return [];
        }
    }

    protected function navigationPayload(): array
    {
        // Plain arrays only — never cache Eloquent Collections.
        return \App\Support\NavigationData::payload();
    }
}
