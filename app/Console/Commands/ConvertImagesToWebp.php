<?php

namespace App\Console\Commands;

use App\Services\ImageUploadService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ConvertImagesToWebp extends Command
{
    /**
     * @var string
     */
    protected $signature = 'app:convert-images-to-webp
        {--dry-run : Show what would change without writing anything}
        {--force : Skip the confirmation prompt}';

    /**
     * @var string
     */
    protected $description = 'Convert previously stored raster images (jpg/png/gif/bmp) to WebP and update database references.';

    /**
     * Database columns that store an image reference, keyed by [table => [column => format]].
     *
     * format:
     *   url    => value is a public URL like "/storage/blogs/xyz.png"
     *   path   => value is a disk path like "avatars/xyz.png"
     *   secure => value is a secure-asset route/path like "/secure-asset/assignments/questions/xyz.png"
     *
     * @var array<string, array<string, string>>
     */
    protected array $columns = [
        'categories' => ['image' => 'path'],
        'blogs' => ['featured_image' => 'url'],
        'courses' => ['thumbnail_url' => 'url'],
        'testimonials' => ['avatar_path' => 'path', 'video_path' => 'path'],
        'featured_projects' => ['video_path' => 'path'],
        'users' => ['avatar' => 'path'],
    ];

    public function handle(ImageUploadService $images): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN — no files or database rows will be modified.');
        }

        $converted = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($this->columns as $table => $columns) {
            if (! $this->tableExists($table)) {
                $this->line("<comment>Skipping missing table:</comment> {$table}");

                continue;
            }

            foreach ($columns as $column => $format) {
                if (! $this->columnExists($table, $column)) {
                    continue;
                }

                $rows = DB::table($table)->select('id', $column)->whereNotNull($column)->where($column, '!=', '')->get();

                foreach ($rows as $row) {
                    $value = (string) $row->{$column};
                    $result = $this->processValue($images, $value, $format, $table, $column, $row->id, $dryRun);

                    if ($result === 'converted') {
                        $converted++;
                    } elseif ($result === 'failed') {
                        $failed++;
                    } else {
                        $skipped++;
                    }
                }
            }
        }

        $this->newLine();
        $this->info("Done. Converted: {$converted}, skipped: {$skipped}, failed: {$failed}.");

        return self::SUCCESS;
    }

    protected function processValue(
        ImageUploadService $images,
        string $value,
        string $format,
        string $table,
        string $column,
        int|string $id,
        bool $dryRun,
    ): string {
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return 'skipped';
        }

        [$path, $prefix, $suffix] = match ($format) {
            'url' => [$this->stripLeadingSlash(str_replace('/storage/', '', $value)), '/storage/', ''],
            'secure' => [$this->securePathFromValue($value), '', ''],
            default => [$this->stripLeadingSlash($value), '', ''],
        };

        if ($path === '') {
            return 'skipped';
        }

        // Only stored raster images are eligible (service returns null otherwise).
        if (! Storage::disk('public')->exists($path)) {
            return 'skipped';
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp'], true)) {
            return 'skipped';
        }

        $this->line("  {$table}.{$column} #{$id}: {$value}");

        if ($dryRun) {
            return 'converted';
        }

        $result = $images->convertStoredPath($path, 'public');

        if ($result === null || ! ($result['converted'] ?? false)) {
            $this->error("    -> FAILED: {$value}");

            return 'failed';
        }

        $newValue = match ($format) {
            'url' => $prefix.$result['path'],
            'secure' => '/secure-asset/'.$result['path'],
            default => $result['path'],
        };

        DB::table($table)->where('id', $id)->update([$column => $newValue]);
        $this->info("    -> {$newValue}");

        return 'converted';
    }

    protected function securePathFromValue(string $value): string
    {
        return ltrim(str_replace(['/secure-asset/', 'secure-asset/'], '', $value), '/');
    }

    protected function stripLeadingSlash(string $value): string
    {
        return ltrim($value, '/');
    }

    protected function tableExists(string $table): bool
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }

    protected function columnExists(string $table, string $column): bool
    {
        return DB::getSchemaBuilder()->hasColumn($table, $column);
    }
}
