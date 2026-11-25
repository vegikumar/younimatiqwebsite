<?php

namespace App\Helpers;

class MediaHelper
{
    public static function embedUrl(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        if (self::isDirectVideo($url)) {
            return null;
        }

        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/', $url, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }

        if (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
            return 'https://player.vimeo.com/video/' . $matches[1];
        }

        return null;
    }

    public static function isDirectVideo(?string $url): bool
    {
        return $url && (bool) preg_match('/\.(mp4|webm|ogg)(\?|$)/i', $url);
    }
}
