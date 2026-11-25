<?php

namespace App\Support;

class ApiAssetUrl
{
    /**
     * Absolutize media paths for the mobile API without breaking public /storage and /images URLs.
     */
    public static function fromPath(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        $path = trim($path);
        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            // Collapse accidental double secure-asset in absolute URLs.
            if (str_contains($path, '/secure-asset/secure-asset/')) {
                $path = str_replace('/secure-asset/secure-asset/', '/secure-asset/', $path);
            }

            return $path;
        }

        // Normalize leading slash and collapse double secure-asset prefixes.
        $normalized = '/' . ltrim($path, '/');
        while (str_starts_with($normalized, '/secure-asset/secure-asset/')) {
            $normalized = substr($normalized, strlen('/secure-asset'));
        }

        $publicPrefixes = [
            '/secure-asset/',
            '/storage/',
            '/images/',
            '/fonts/',
            '/videos/',
            '/build/',
        ];

        foreach ($publicPrefixes as $prefix) {
            if (str_starts_with($normalized, $prefix)) {
                return url($normalized);
            }
        }

        // "storage/..." without leading slash
        if (str_starts_with(ltrim($path, '/'), 'storage/')) {
            return url('/' . ltrim($path, '/'));
        }

        // Disk-relative private/public course assets → secure-asset gateway
        return url('/secure-asset/' . ltrim($path, '/'));
    }

    public static function avatar(?string $avatar, ?string $name = 'Student'): string
    {
        if ($avatar) {
            return self::fromPath($avatar) ?? '';
        }

        $initial = urlencode(substr($name ?? 'S', 0, 1));

        return "https://ui-avatars.com/api/?name={$initial}&background=7130D8&color=fff";
    }
}
