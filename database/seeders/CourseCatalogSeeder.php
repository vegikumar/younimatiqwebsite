<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CourseCatalogSeeder extends Seeder
{
    private const SOURCE_ROOT = 'courses';

    private const PUBLIC_ROOT = 'images/catalog';

    private const DEFAULT_PRICE = 149.99;

    /** @var array<string, array{slug: string, name: string, icon: string, camp_folder?: bool}> */
    private array $folderMap = [
        'Category-AI Thumbnails' => ['slug' => 'ai-camp', 'name' => 'AI Camp', 'icon' => 'psychology', 'camp_folder' => true],
        'Category-Arts and Animations' => ['slug' => 'arts-animation-camp', 'name' => 'Arts & Animation Camp', 'icon' => 'palette', 'camp_folder' => true],
        'Category-Lifeskills' => ['slug' => 'lifeskills', 'name' => 'Lifeskills', 'icon' => 'self_improvement', 'camp_folder' => false],
        'Category-Minecraft Thumbnails' => ['slug' => 'minecraft-camp', 'name' => 'Minecraft Camp', 'icon' => 'sports_esports', 'camp_folder' => true],
        'Category-Python Thumbnails' => ['slug' => 'coding', 'name' => 'Coding', 'icon' => 'code', 'camp_folder' => false],
        'Category-Roblox Thumbnails' => ['slug' => 'roblox-camp', 'name' => 'Roblox Camp', 'icon' => 'videogame_asset', 'camp_folder' => true],
        'Category-Robotics Thumbnails' => ['slug' => 'robotics-camp', 'name' => 'Robotics Camp', 'icon' => 'precision_manufacturing', 'camp_folder' => true],
        'Category-Scratch Thumbnails' => ['slug' => 'coding', 'name' => 'Coding', 'icon' => 'code', 'camp_folder' => false],
    ];

    public function run(): void
    {
        $tutor = User::firstOrCreate(
            ['email' => 'tutor@younimatiq.com'],
            [
                'name' => 'Tutor John',
                'password' => bcrypt('password'),
                'role' => 'tutor',
                'status' => 'active',
            ]
        );

        $ocrMap = require database_path('seeders/data/course_image_titles.php');
        $sourceRoot = base_path(self::SOURCE_ROOT);

        if (! is_dir($sourceRoot)) {
            $this->command?->warn('Course source folder missing: '.$sourceRoot);

            return;
        }

        $categories = [];
        foreach ($this->folderMap as $meta) {
            $categories[$meta['slug']] = Category::updateOrCreate(
                ['slug' => $meta['slug']],
                [
                    'name' => $meta['name'],
                    'icon' => $meta['icon'],
                    'is_active' => true,
                    'sort_order' => Category::where('slug', $meta['slug'])->value('sort_order') ?? 20,
                ]
            );
        }

        Category::updateOrCreate(
            ['slug' => 'summer-camp'],
            ['name' => 'Summer Camp', 'icon' => 'wb_sunny', 'is_active' => true, 'sort_order' => 5]
        );

        $count = 0;

        foreach ($this->folderMap as $folder => $meta) {
            $dir = $sourceRoot.DIRECTORY_SEPARATOR.$folder;
            if (! is_dir($dir)) {
                continue;
            }

            $category = $categories[$meta['slug']];
            $files = File::files($dir);

            foreach ($files as $file) {
                if (strtolower($file->getExtension()) !== 'png') {
                    continue;
                }

                $relativeKey = $folder.'/'.$file->getFilename();
                $metaFromOcr = $ocrMap[$relativeKey] ?? null;

                $title = $metaFromOcr['title'] ?? $this->titleFromFilename($file->getFilenameWithoutExtension());
                if ($title === '') {
                    continue;
                }

                $ageMin = $metaFromOcr['age_min'] ?? null;
                $ageMax = $metaFromOcr['age_max'] ?? null;
                $ageLabel = $metaFromOcr['age_label'] ?? null;

                if ($ageMin === null && $ageMax === null) {
                    [$ageMin, $ageMax, $ageLabel] = $this->extractAgeFromTitle($title);
                }

                $isCamp = (bool) ($metaFromOcr['is_camp'] ?? false);
                if (! $isCamp) {
                    $isCamp = $this->detectCamp($title, $meta['camp_folder'] ?? false);
                }

                $courseCategory = $category;
                $subtitle = $metaFromOcr['subtitle'] ?? null;
                $features = $metaFromOcr['features'] ?? [];
                if ($features === []) {
                    $features = $this->defaultFeatures($category->name, $isCamp);
                }

                $description = $this->buildDescription($title, $subtitle, $ageLabel, $category->name, $features);
                $importance = $this->buildImportance($title, $category->name, $ageLabel, $isCamp);
                $outcomes = $this->buildOutcomes($title, $category->name, $features);
                $highlights = array_values(array_unique(array_slice($features, 0, 6)));

                $thumbnailUrl = $this->copyThumbnail($folder, $file->getFilename(), $meta['slug']);
                $ageGroup = $this->mapAgeGroup($ageMin, $ageMax);

                $course = Course::updateOrCreate(
                    ['title' => $title, 'tutor_id' => $tutor->id],
                    [
                        'category_id' => $courseCategory->id,
                        'description' => $description,
                        'importance' => $importance,
                        'learning_outcomes' => $outcomes,
                        'highlights' => $highlights,
                        'price' => self::DEFAULT_PRICE,
                        'thumbnail_url' => $thumbnailUrl,
                        'course_type' => 'live',
                        'status' => 'published',
                        'age_group' => $ageGroup,
                        'is_camp' => $isCamp,
                        'seo_metadata' => [
                            'title' => $title.' | Younimatiq',
                            'description' => Str::limit(strip_tags($description), 155),
                            'age_label' => $ageLabel,
                            'age_min' => $ageMin,
                            'age_max' => $ageMax,
                            'keywords' => array_filter([$category->name, $ageLabel, $isCamp ? 'summer camp' : null]),
                        ],
                    ]
                );

                $this->seedModules($course, $features, $category->name);

                $count++;
                $this->command?->info("Seeded: {$title}");
            }
        }

        $this->command?->info("Course catalog seeded: {$count} courses.");
    }

    private function titleFromFilename(string $name): string
    {
        $name = trim($name);
        if (str_starts_with($name, 'Category-')) {
            $name = preg_replace('/^Category-/', '', $name) ?? $name;
            $name = preg_replace('/\s*Thumbnails\s*$/i', '', $name) ?? $name;
            $name = trim($name).' for Kids';
        }

        $name = str_replace(['_', '—'], [': ', ' — '], $name);
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;

        return trim($name);
    }

    /** @return array{0: ?int, 1: ?int, 2: ?string} */
    private function extractAgeFromTitle(string $title): array
    {
        if (preg_match('/Ages?\s*(\d+)\s*[–\-]\s*(\d+)/i', $title, $m)) {
            return [(int) $m[1], (int) $m[2], "Ages {$m[1]}-{$m[2]}"];
        }
        if (preg_match('/\((\d+)\s*[–\-]\s*(\d+)\s*(?:years?)?\)/i', $title, $m)) {
            return [(int) $m[1], (int) $m[2], "{$m[1]}-{$m[2]} years"];
        }
        if (preg_match('/Grade\s*(\d+)\s*[–\-]\s*(\d+)/i', $title, $m)) {
            $min = (int) $m[1] + 5;
            $max = (int) $m[2] + 5;

            return [$min, $max, "Grade {$m[1]}-{$m[2]}"];
        }

        return [null, null, null];
    }

    private function detectCamp(string $title, bool $campFolder): bool
    {
        $lower = strtolower($title);
        if (str_contains($lower, 'summer camp') || str_contains($lower, ' camp') || str_ends_with($lower, 'camp') || str_contains($lower, 'bootcamp')) {
            return true;
        }

        return false;
    }

    private function mapAgeGroup(?int $min, ?int $max): string
    {
        if ($min === null && $max === null) {
            return 'voyagers';
        }

        $mid = (($min ?? $max) + ($max ?? $min)) / 2;

        if ($mid <= 8.5) {
            return 'explorers';
        }
        if ($mid <= 11.5) {
            return 'voyagers';
        }

        return 'pioneers';
    }

    /** @return list<string> */
    private function defaultFeatures(string $category, bool $isCamp): array
    {
        $base = match (true) {
            str_contains(strtolower($category), 'ai') => ['Learn AI basics', 'Build fun projects', 'Explore smart tools', 'Present your creations'],
            str_contains(strtolower($category), 'art') => ['Draw & design', 'Animate ideas', 'Practice techniques', 'Share finished artwork'],
            str_contains(strtolower($category), 'minecraft') => ['Build in Minecraft', 'Problem-solve creatively', 'Collaborate with peers', 'Complete challenges'],
            str_contains(strtolower($category), 'roblox') => ['Design in Roblox Studio', 'Learn game logic', 'Build playable levels', 'Share with friends'],
            str_contains(strtolower($category), 'robot') => ['Build circuits', 'Write robot code', 'Test movements', 'Troubleshoot like engineers'],
            str_contains(strtolower($category), 'life') => ['Build confidence', 'Practice communication', 'Learn real-life skills', 'Grow independence'],
            default => ['Learn core concepts', 'Create hands-on projects', 'Practice with a live tutor', 'Showcase your progress'],
        };

        if ($isCamp) {
            $base[] = 'Camp-style project showcase';
        }

        return $base;
    }

    private function buildDescription(string $title, ?string $subtitle, ?string $ageLabel, string $category, array $features): string
    {
        $parts = [];
        $parts[] = $subtitle ?: "Live interactive {$category} program: {$title}.";
        if ($ageLabel) {
            $parts[] = "Designed for {$ageLabel}.";
        }
        $parts[] = "In this Younimatiq live class, students learn through projects, games, and guided practice with expert educators.";
        if ($features !== []) {
            $parts[] = 'Highlights include: '.implode(', ', array_slice($features, 0, 5)).'.';
        }
        $parts[] = 'Book a $1 demo class to experience Younimatiq live teaching.';

        return implode(' ', $parts);
    }

    private function buildImportance(string $title, string $category, ?string $ageLabel, bool $isCamp): string
    {
        $audience = $ageLabel ? "children ({$ageLabel})" : 'kids and teens';
        $format = $isCamp ? 'immersive camp sessions' : 'weekly live classes';

        return "{$title} matters because today's {$audience} need creative, tech-ready skills beyond traditional homework. "
            ."This {$category} program uses {$format} to build confidence, problem-solving, and real project experience. "
            .'Students leave with skills they can show parents, teachers, and peers — and a stronger foundation for school and future careers in STEM, design, and digital creation.';
    }

    /** @return list<string> */
    private function buildOutcomes(string $title, string $category, array $features): array
    {
        $outcomes = [];
        foreach (array_slice($features, 0, 5) as $feature) {
            $clean = rtrim(trim($feature), '.');
            $outcomes[] = "Be able to {$this->outcomeVerb($clean)}.";
        }

        $outcomes[] = "Complete a shareable {$category} project related to {$title}.";
        $outcomes[] = 'Explain key concepts to friends and family with confidence.';
        $outcomes[] = 'Earn a Younimatiq certificate of completion.';

        return array_values(array_unique($outcomes));
    }

    private function outcomeVerb(string $feature): string
    {
        $lower = strtolower($feature);
        if (str_starts_with($lower, 'learn ') || str_starts_with($lower, 'build ') || str_starts_with($lower, 'create ')
            || str_starts_with($lower, 'explore ') || str_starts_with($lower, 'code ') || str_starts_with($lower, 'design ')
            || str_starts_with($lower, 'train ') || str_starts_with($lower, 'program ')) {
            return $lower;
        }

        return 'apply skills in: '.$feature;
    }

    /** @param list<string> $features */
    private function seedModules(Course $course, array $features, string $category): void
    {
        $moduleTitles = [];
        foreach (array_slice($features, 0, 4) as $feature) {
            $moduleTitles[] = ucfirst(rtrim(trim($feature), '.'));
        }

        if (count($moduleTitles) < 4) {
            $moduleTitles = array_merge($moduleTitles, [
                "Introduction to {$category}",
                'Hands-on Project Lab',
                'Guided Practice Session',
                'Final Showcase & Feedback',
            ]);
        }

        $moduleTitles = array_slice(array_values(array_unique($moduleTitles)), 0, 5);
        $moduleTitles[] = 'Final Project Showcase';

        CourseModule::where('course_id', $course->id)->delete();

        foreach ($moduleTitles as $index => $moduleTitle) {
            CourseModule::create([
                'course_id' => $course->id,
                'title' => $moduleTitle,
                'order' => $index + 1,
                'content_type' => $index === 0 ? 'text' : 'zoom',
                'content_text' => $index === 0
                    ? "Welcome to {$course->title}. In this module you will get oriented and meet your live educator."
                    : null,
            ]);
        }
    }

    private function copyThumbnail(string $folder, string $filename, string $categorySlug): string
    {
        $source = base_path(self::SOURCE_ROOT.DIRECTORY_SEPARATOR.$folder.DIRECTORY_SEPARATOR.$filename);
        $destDir = public_path(self::PUBLIC_ROOT.DIRECTORY_SEPARATOR.$categorySlug);
        File::ensureDirectoryExists($destDir);

        $safeName = Str::slug(pathinfo($filename, PATHINFO_FILENAME));
        if ($safeName === '') {
            $safeName = 'course-'.md5($filename);
        }
        $destName = $safeName.'.png';
        $dest = $destDir.DIRECTORY_SEPARATOR.$destName;

        if (! file_exists($dest) || filemtime($source) > filemtime($dest)) {
            File::copy($source, $dest);
        }

        return '/'.self::PUBLIC_ROOT.'/'.$categorySlug.'/'.$destName;
    }
}
