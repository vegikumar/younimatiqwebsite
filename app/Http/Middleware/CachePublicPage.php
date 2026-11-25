<?php

namespace App\Http\Middleware;

use App\Services\GeoLocaleService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Marks anonymous GET responses for public marketing pages as edge-cacheable.
 *
 * These pages render country-agnostic HTML: prices are emitted for the USD
 * base with per-currency values in data attributes (see App\Support\PriceRenderer
 * and resources/js/currency.js), which swaps them per visitor after load. So a
 * single cached HTML copy is correct for every country and no country cache key
 * is required.
 *
 * To keep cached HTML user-agnostic we also strip the session-bound CSRF token
 * and the session cookie for these responses. A small client script refreshes
 * the CSRF token from /csrf-token once the page is loaded.
 */
class CachePublicPage
{
    /**
     * Explicit allowlist of country-agnostic public marketing pages that may be
     * edge-cached. Anything not listed here (courses, checkout, community,
     * back-to-school, etc.) is left dynamic because it renders country-specific
     * or transactional content.
     *
     * @var array<int, string>
     */
    protected array $cacheable = [
        '/',
        'about',
        'contact',
        'faq',
        'pricing',
        'ai-robotics',
        'math',
        'minecraft',
        'blog',
        'blog/*',
        'page/*',
    ];

    /**
     * Route names of country-agnostic pages rendered by the catch-all {slug}
     * route (category landings), which cannot be matched by path prefix.
     *
     * @var array<int, string>
     */
    protected array $cacheableRouteNames = [
        'category.landing',
    ];

    protected const EDGE_TTL = 600;

    public function __construct(private readonly GeoLocaleService $geo) {}

    public function handle(Request $request, Closure $next): Response
    {
        $snapshot = [
            'method' => $request->isMethod('GET') || $request->isMethod('HEAD'),
            'cookie' => $request->cookies->has(config('session.cookie')),
        ];

        /** @var Response $response */
        $response = $next($request);

        // Route-based allowlist checks must run after $next(): this is global
        // middleware, so the route is not resolved until the request has passed
        // through the router.
        if (! $this->isCacheable($request, $snapshot)) {
            return $response;
        }

        // Never cache anything but a successful, full HTML document.
        if ($response->getStatusCode() !== 200) {
            return $response;
        }

        $contentType = $response->headers->get('Content-Type', '');
        if (! str_contains($contentType, 'text/html')) {
            return $response;
        }

        // Protected HTML activities must never be edge-cached or shared.
        $cacheControl = strtolower((string) $response->headers->get('Cache-Control', ''));
        if (
            $response->headers->has('X-Younimatiq-Activity')
            || str_contains($cacheControl, 'no-store')
            || str_contains($cacheControl, 'private')
        ) {
            return $response;
        }

        // Inertia pages need a live session + CSRF; do not edge-cache them.
        $content = $response->getContent();
        if (is_string($content) && (str_contains($content, 'data-page=') || str_contains($content, 'id="app" data-page'))) {
            return $response;
        }
        if ($response->headers->has('X-Inertia')) {
            return $response;
        }

        // This response is shared across all visitors: drop the per-session
        // CSRF token so the cached copy stays user-agnostic, and remove the
        // session cookies Laravel added.
        $this->deferCsrfToken($response);
        $this->stripSessionCookies($response);

        $response->headers->set(
            'Cache-Control',
            'public, max-age=0, s-maxage='.self::EDGE_TTL.', stale-while-revalidate=86400'
        );
        $response->headers->set('X-Cacheable', '1');
        // The HTML is identical for every country, so only vary by encoding.
        $response->headers->set('Vary', 'Accept-Encoding', false);

        return $response;
    }

    protected function isCacheable(Request $request, array $snapshot): bool
    {
        if (! $snapshot['method']) {
            return false;
        }

        $routeName = optional($request->route())->getName();
        $matchesRoute = $routeName !== null && in_array($routeName, $this->cacheableRouteNames, true);

        if (! $matchesRoute && ! $request->is(...$this->cacheable)) {
            return false;
        }

        // Any authenticated (or previously authenticated) visitor gets dynamic HTML.
        if (auth('web')->check() || auth('admin')->check() || auth('tutor')->check()) {
            return false;
        }

        // If the visitor already carried a session cookie we cannot safely treat
        // the request as anonymous: they may have cart/currency state. Only cache
        // the pristine first-visit response.
        if ($snapshot['cookie']) {
            return false;
        }

        return true;
    }

    /**
     * Replace the session-bound CSRF token in the HTML with a placeholder token
     * that the client refreshes from /csrf-token after load.
     */
    protected function deferCsrfToken(Response $response): void
    {
        $content = $response->getContent();

        if (! is_string($content) || $content === '') {
            return;
        }

        $token = csrf_token();
        $placeholder = 'deferred';

        if ($token !== '' && $token !== $placeholder) {
            $content = str_replace($token, $placeholder, $content);
            $response->setContent($content);
        }
    }

    protected function stripSessionCookies(Response $response): void
    {
        $sessionCookie = (string) config('session.cookie');
        $xsrfCookie = 'XSRF-TOKEN';

        $kept = [];

        foreach ($response->headers->getCookies() as $cookie) {
            if (in_array($cookie->getName(), [$sessionCookie, $xsrfCookie], true)) {
                continue;
            }

            $kept[] = $cookie;
        }

        // Rebuild the Set-Cookie header from the cookies we want to keep. This is
        // more reliable than removeCookie(), which does not always drop cookies
        // that were attached through the session/verification middleware.
        $response->headers->remove('Set-Cookie');

        foreach ($kept as $cookie) {
            $response->headers->setCookie($cookie);
        }
    }
}
