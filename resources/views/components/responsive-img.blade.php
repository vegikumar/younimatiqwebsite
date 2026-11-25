@props([
    'src',
    'alt' => '',
    'lazy' => true,
    'priority' => false,
    'srcset' => null,
    'sizes' => null,
])

@php
    $src = (string) $src;
    $isAbsolute = \Illuminate\Support\Str::startsWith($src, ['http://', 'https://', '//', 'data:']);

    $publicRelative = null;
    if (! $isAbsolute) {
        $path = parse_url($src, PHP_URL_PATH) ?: $src;
        $path = ltrim(str_replace(asset(''), '', $path), '/');
        if (\Illuminate\Support\Str::contains($src, '/')) {
            $publicRelative = preg_match('#^https?://#', $src)
                ? ltrim(parse_url($src, PHP_URL_PATH) ?? '', '/')
                : ltrim($src, '/');
        } else {
            $publicRelative = $src;
        }
        $publicRelative = preg_replace('#^public/#', '', $publicRelative);
    }

    $imgSrc = $isAbsolute ? $src : asset($publicRelative);
    $webpSrc = null;
    $webpRelative = null;
    if ($publicRelative && preg_match('/\.(png|jpe?g)$/i', $publicRelative)) {
        $webpRelative = preg_replace('/\.(png|jpe?g)$/i', '.webp', $publicRelative);
        if (is_file(public_path($webpRelative))) {
            $webpSrc = asset($webpRelative);
        }
    }

    $webpSrcset = $srcset;
    if (! $webpSrcset && $webpSrc && $webpRelative) {
        $candidates = [];
        $base = preg_replace('/\.webp$/i', '', $webpRelative);

        // Prefer sized variants when present (logo-168, card-276, card-480, etc.)
        // Cap marketing cards at 480w — larger mains inflate Lighthouse on mobile DPR.
        foreach ([168, 276, 336, 480] as $w) {
            $sized = $base . '-' . $w . '.webp';
            if (is_file(public_path($sized))) {
                $candidates[] = asset($sized) . ' ' . $w . 'w';
            }
        }

        // Infer intrinsic width of the main webp for the srcset descriptor
        $mainW = 480;
        if (preg_match('/logo\.webp$/i', $webpRelative)) {
            $mainW = 336;
        } elseif (is_file(public_path($webpRelative))) {
            try {
                $size = @getimagesize(public_path($webpRelative));
                if (! empty($size[0])) {
                    $mainW = (int) $size[0];
                }
            } catch (\Throwable $e) {
                //
            }
        }
        // Skip oversized mains (>480) for course cards — sized -480.webp already covers retina.
        if ($mainW <= 480 || preg_match('/logo\.webp$/i', $webpRelative)) {
            $candidates[] = $webpSrc . ' ' . $mainW . 'w';
        } elseif (is_file(public_path($base . '-480.webp'))) {
            // already added via foreach
        } else {
            $candidates[] = $webpSrc . ' 480w';
        }
        // de-dupe by descriptor
        $seen = [];
        $unique = [];
        foreach ($candidates as $c) {
            $desc = preg_replace('/^.*\s/', '', $c);
            if (isset($seen[$desc])) {
                continue;
            }
            $seen[$desc] = true;
            $unique[] = $c;
        }
        $webpSrcset = implode(', ', $unique);
    }

    // Prefer explicit sizes prop; fall back to attribute bag if present.
    $sizesAttr = $sizes ?? ($attributes->get('sizes'));
    $imgAttributes = $attributes->except(['sizes']);
    if ($sizesAttr) {
        $imgAttributes = $imgAttributes->merge(['sizes' => $sizesAttr]);
    }

    $loading = $priority ? 'eager' : ($lazy ? 'lazy' : 'eager');
@endphp

@if($webpSrc)
    <picture>
        <source
            type="image/webp"
            srcset="{{ $webpSrcset }}"
            @if($sizesAttr) sizes="{{ $sizesAttr }}" @endif
        >
        <img
            src="{{ $imgSrc }}"
            alt="{{ $alt }}"
            loading="{{ $loading }}"
            @if($priority) fetchpriority="high" @endif
            decoding="async"
            {{ $imgAttributes }}
        >
    </picture>
@else
    <img
        src="{{ $imgSrc }}"
        alt="{{ $alt }}"
        loading="{{ $loading }}"
        @if($priority) fetchpriority="high" @endif
        decoding="async"
        @if($srcset) srcset="{{ $srcset }}" @endif
        {{ $imgAttributes }}
    >
@endif
