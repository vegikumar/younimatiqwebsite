<?php

use App\Http\Middleware\Api\CheckStudentTrialAccess;
use App\Http\Middleware\Api\EnsureStudentActive;
use App\Http\Middleware\Api\EnsureStudentEnrolled;
use App\Http\Middleware\CachePublicPage;
use App\Http\Middleware\CheckTrialAccess;
use App\Http\Middleware\CrossBundleInertiaRedirect;
use App\Http\Middleware\DetectLocale;
use App\Http\Middleware\EnsureEnrolled;
use App\Http\Middleware\EnsureUserActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'enrolled' => EnsureEnrolled::class,
            'active' => EnsureUserActive::class,
            'trial_access' => CheckTrialAccess::class,
            'api.student.active' => EnsureStudentActive::class,
            'api.student.trial' => CheckStudentTrialAccess::class,
            'api.student.enrolled' => EnsureStudentEnrolled::class,
        ]);
        $middleware->statefulApi();
        $middleware->validateCsrfTokens(except: [
            'webhooks/stripe',
            'webhooks/paypal',
            'webhooks/razorpay',
            'webhooks/meet/*',
        ]);
        $middleware->web(append: [
            DetectLocale::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            // After Inertia: upgrade public↔portal redirects to full document loads.
            \App\Http\Middleware\CrossBundleInertiaRedirect::class,
        ]);

        // Runs globally (outermost) so it sees the final response after the web
        // group has attached session/XSRF cookies, then strips them for anonymous
        // public pages so Cloudflare can edge-cache the HTML.
        $middleware->append(CachePublicPage::class);
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('login.admin');
            }
            if ($request->is('tutor') || $request->is('tutor/*') || $request->is('register/tutor/pending')) {
                return route('login.tutor');
            }
            if ($request->is('parent') || $request->is('parent/*')) {
                return route('login.parent');
            }

            return route('login.student');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Keep API JSON responses, and also honor Accept: application/json /
        // X-Requested-With so browser fetch() checkout calls get JSON errors
        // (CSRF 419, validation) instead of HTML that the UI can't parse.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Auth / authorization redirects are thrown as exceptions, so they skip
        // post-handle middleware. Upgrade public↔portal ones for Inertia here.
        $exceptions->respond(function (Response $response, \Throwable $e, Request $request) {
            return CrossBundleInertiaRedirect::upgrade($request, $response);
        });
    })->create();
