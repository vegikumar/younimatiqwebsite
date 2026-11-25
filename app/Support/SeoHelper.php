<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SeoHelper
{
    public static function absoluteImageUrl(?string $image): string
    {
        $fallback = file_exists(public_path('og-default.png'))
            ? url('/og-default.png')
            : url('/logo.png');

        if (blank($image)) {
            return $fallback;
        }

        if (Str::startsWith($image, ['http://', 'https://'])) {
            return $image;
        }

        if (Str::startsWith($image, '/')) {
            return url($image);
        }

        if (Storage::disk('public')->exists($image)) {
            return url(Storage::disk('public')->url($image));
        }

        if (file_exists(public_path($image))) {
            return url('/' . ltrim($image, '/'));
        }

        return $fallback;
    }

    public static function globalDefaults(): array
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('settings')) {
                return self::defaultGlobals();
            }

            $settings = \Illuminate\Support\Facades\Cache::rememberForever('global_settings', function () {
                return \App\Models\Setting::pluck('value', 'key')->toArray();
            });

            return [
                'title' => $settings['seo_meta_title'] ?? self::defaultGlobals()['title'],
                'description' => $settings['seo_meta_description'] ?? self::defaultGlobals()['description'],
                'keywords' => $settings['seo_meta_keywords'] ?? self::defaultGlobals()['keywords'],
            ];
        } catch (\Throwable) {
            return self::defaultGlobals();
        }
    }

    public static function defaultGlobals(): array
    {
        return [
            'title' => 'Younimatiq — Personalized K–12 Online Learning',
            'description' => 'Personalized K–12 online learning with expert mentors. Coding, AI, robotics, math, science, arts and more — built around your child’s interests.',
            'keywords' => 'Younimatiq, personalized learning, online classes for kids, K-12 tutoring, coding for kids, STEM courses, AI for kids, online math classes',
        ];
    }

    /**
     * @param  iterable<int, object{question: string, answer: string}>  $faqs
     * @return array<int, array{question: string, answer: string}>
     */
    public static function faqsToSchema(iterable $faqs): array
    {
        $items = [];

        foreach ($faqs as $faq) {
            $question = trim(strip_tags((string) ($faq->question ?? '')));
            $answer = trim(strip_tags((string) ($faq->answer ?? '')));

            if ($question === '' || $answer === '') {
                continue;
            }

            $items[] = [
                'question' => $question,
                'answer' => $answer,
            ];
        }

        return $items;
    }
}
