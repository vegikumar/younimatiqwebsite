<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * When an Inertia XHR would follow a same-origin redirect into the other Vite
 * shell (public app.jsx vs portal app-portal.jsx), force a full document load
 * via Inertia::location so the correct page map is used.
 *
 * Note: unauthenticated redirects thrown as AuthenticationException bypass this
 * middleware's post-handle path — those are upgraded in bootstrap/app.php
 * via Exceptions::respond using the same helper.
 */
class CrossBundleInertiaRedirect
{
    public function handle(Request $request, Closure $next): Response
    {
        return self::upgrade($request, $next($request));
    }

    public static function upgrade(Request $request, Response $response): Response
    {
        if (! $request->headers->has('X-Inertia') || ! $response->isRedirection()) {
            return $response;
        }

        $target = $response->headers->get('Location');
        if (! $target) {
            return $response;
        }

        $targetPath = parse_url($target, PHP_URL_PATH) ?: '/';
        $fromPortal = self::isPortalPath($request->getPathInfo() ?: '/');
        $toPortal = self::isPortalPath($targetPath);

        if ($fromPortal === $toPortal) {
            return $response;
        }

        // Preserve flashed session data already attached by redirect()->with(...).
        return Inertia::location($target);
    }

    public static function isPortalPath(string $pathname): bool
    {
        $path = $pathname !== '' ? $pathname : '/';

        $publicPatterns = [
            '#^/checkout(/|$)#i',
            '#^/payment(/|$)#i',
            '#^/login(/|$)#i',
            '#^/register(/|$)#i',
            '#^/logout(/|$)#i',
            '#^/impersonate(/|$)#i',
            '#^/book-#i',
            '#^/booking(/|$)#i',
            '#^/custom-enroll(/|$)#i',
        ];

        foreach ($publicPatterns as $pattern) {
            if (preg_match($pattern, $path)) {
                return false;
            }
        }

        return (bool) preg_match('#^/(admin|tutor|student|parent)(/|$)#i', $path);
    }
}
