<?php

namespace App\Http\Middleware;

use App\Services\GeoLocaleService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectLocale
{
    public function __construct(private readonly GeoLocaleService $geo) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('admin/*') && ! $request->is('api/*')) {
            $this->geo->applyToSession($request);
        }

        return $next($request);
    }
}
