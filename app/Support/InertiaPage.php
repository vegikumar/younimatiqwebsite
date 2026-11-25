<?php

namespace App\Support;

use Illuminate\Contracts\Support\Arrayable;
use Inertia\Inertia;
use Inertia\Response;

class InertiaPage
{
    public static function render(string $component, array|Arrayable $props = [], string $layout = 'app'): Response
    {
        $data = $props instanceof Arrayable ? $props->toArray() : $props;
        $data['layoutType'] = $layout;

        return Inertia::render($component, $data);
    }

    /**
     * Pure React Inertia page (serialized props + JSX component).
     */
    public static function react(string $component, array|Arrayable $props = []): Response
    {
        return static::render($component, $props);
    }
}
