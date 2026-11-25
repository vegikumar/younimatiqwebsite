<?php

namespace App\Support;

use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Force a full document navigation when moving between the public Vite shell
 * (app.jsx) and the portal Vite shell (app-portal.jsx).
 */
class BundleRedirect
{
    public static function to(string $url, array $flash = []): Response
    {
        foreach ($flash as $key => $value) {
            session()->flash($key, $value);
        }

        return Inertia::location($url);
    }

    public static function route(string $name, mixed $parameters = [], array $flash = []): Response
    {
        return self::to(route($name, $parameters), $flash);
    }
}
