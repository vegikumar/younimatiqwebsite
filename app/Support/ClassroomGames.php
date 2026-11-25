<?php

namespace App\Support;

use App\Models\ActivityLink;
use App\Models\Setting;

class ClassroomGames
{
    public const HTML_MIN_SECONDS = 20;

    public static function htmlGamePoints(): int
    {
        try {
            $pts = (int) (Setting::query()->where('key', 'html_game_points')->value('value') ?? 20);
        } catch (\Throwable) {
            $pts = 20;
        }

        return max(0, min(1000, $pts));
    }

    public static function isUploadKey(string $key): bool
    {
        return (bool) preg_match('/^upload-\d+$/', $key);
    }

    public static function all(): array
    {
        return [
            [
                'key' => 'friendship-quest',
                'title' => 'Friendship Quest',
                'summary' => 'Pick the right friend and build the tree house, one answer at a time.',
                'xp' => 8,
            ],
            [
                'key' => 'story-crafting-table',
                'title' => 'Story Crafting Table',
                'summary' => 'Choose a build, write a beginning, problem, and ending, then power up the story.',
                'xp' => 6,
            ],
            [
                'key' => 'rebuild-the-base',
                'title' => 'Rebuild the Base',
                'summary' => 'Answer ten questions. Every right answer puts a new row of blocks on the base.',
                'xp' => 10,
            ],
            [
                'key' => 'noun-miner',
                'title' => 'Noun Miner',
                'summary' => 'Tap the naming words in each sentence and turn them into diamonds.',
                'xp' => 16,
            ],
            [
                'key' => 'word-mining-tylers-story',
                'title' => "Word Mining: Tyler's Story",
                'summary' => "Look at Tyler's pictures, then mine the letters to spell the missing word.",
                'xp' => 7,
            ],
            [
                'key' => 'story-treasure-map',
                'title' => 'Story Treasure Map',
                'summary' => 'Walk the story map: who, where, and what happens next.',
                'xp' => 18,
            ],
            [
                'key' => 'math-dash',
                'title' => 'Math Dash',
                'summary' => 'Answer 10 quick sums across Grade 1–3 levels. Add, take away, or times tables.',
                'xp' => 10,
            ],
            [
                'key' => 'verb-detective',
                'title' => 'Verb Detective: Action, Helping & Linking Verbs',
                'summary' => 'Crack verb cases — spot action, helping, and linking verbs. Score stars as you go.',
                'xp' => 15,
            ],
            [
                'key' => 'alien-verb-lab',
                'title' => 'Alien Verb Lab: Action, Helping & Linking Verbs',
                'summary' => 'Help Zeke classify verbs across the worksheet lab. Check answers and earn a mission score.',
                'xp' => 15,
            ],
            [
                'key' => 'labyrinth-grammar',
                'title' => 'Labyrinth Grammar',
                'summary' => 'Battle of the Labyrinth English powers: Syllable Slicer, Open/Closed Gate, Monster Spotter, and Message Fixer.',
                'xp' => 20,
            ],
            [
                'key' => 'percy-jackson-ela-class-1',
                'title' => 'Escape the Labyrinth – Syllable Sword Quest',
                'summary' => 'Percy Jackson ELA mission: clap syllables, slice words, open vs closed gates, and defeat the Monster of Confusion.',
                'xp' => 20,
            ],
            [
                'key' => 'inequalities-in-class-game',
                'title' => 'Mission Crocodile Gate',
                'summary' => 'Crack 5 inequality gates across Space Station Orbit-Q. Symbols, number lines, solving, and double inequalities.',
                'xp' => 20,
            ],
            [
                'key' => 'treasure-of-algebra-island',
                'title' => 'Treasure of Algebra Island',
                'summary' => 'Sail five island stops: expressions, variables, like terms, and one- and two-step equations with PICO.',
                'xp' => 20,
            ],
            [
                'key' => 'inequalities-practice-worksheet',
                'title' => 'Inequalities Practice Worksheet',
                'summary' => '20 algebra inequality sums: symbols, number lines, one-step and two-step solving, with check / hint / show answer.',
                'xp' => 20,
            ],
            [
                'key' => 'power-up-snowed-in-house',
                'title' => 'Power Up the Snowed-In House',
                'summary' => 'Cabin Fever mission: switch five breakers back on with syllables, open/closed, sentence repairs, vocabulary, and spelling.',
                'xp' => 20,
            ],
            [
                'key' => 'algebra-world-quest',
                'title' => 'Algebra World Quest',
                'summary' => 'Minecraft-themed algebra adventure: 8 missions + Ender Boss, 39 sums from variables to inequalities.',
                'xp' => 20,
            ],
        ];
    }

    public static function find(string $key): ?array
    {
        foreach (self::all() as $game) {
            if ($game['key'] === $key) {
                return $game;
            }
        }

        return null;
    }

    public static function maxPoints(string $key): ?int
    {
        $game = self::find($key);
        if (isset($game['xp'])) {
            return (int) $game['xp'];
        }

        if (self::isUploadKey($key) && self::resolve($key)) {
            return self::htmlGamePoints();
        }

        return null;
    }

    public static function uploadKey(int $id): string
    {
        return 'upload-'.$id;
    }

    public static function resolve(string $key, bool $activeOnly = true): ?array
    {
        $built = self::find($key);
        if ($built) {
            return $built;
        }

        if (! preg_match('/^upload-(\d+)$/', $key, $matches)) {
            return null;
        }

        $link = ActivityLink::query()
            ->when($activeOnly, fn ($query) => $query->where('is_active', true)->where('status', 'published'))
            ->find((int) $matches[1]);

        if (! $link || self::find($link->slug)) {
            return null;
        }

        return [
            'key' => self::uploadKey($link->id),
            'title' => $link->title,
            'summary' => $link->description ?: 'Uploaded HTML activity. Students play it from Activities.',
            'subject' => $link->subject,
            'grade' => $link->grade,
            'thumbnail' => $link->thumbnailUrl(),
            'embed_url' => '/'.$link->slug,
            'html_play' => true,
            'xp' => self::htmlGamePoints(),
        ];
    }
}
