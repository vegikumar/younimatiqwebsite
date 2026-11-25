<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use App\Jobs\ProcessHLSVideo;

class MigrateSecureAssets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-secure-assets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Moves existing public assets to private storage and queues videos for HLS encoding.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting migration of Course assets...");

        $courses = Course::all();
        $bar = $this->output->createProgressBar(count($courses));

        foreach ($courses as $course) {
            
            // 1. Migrate Thumbnail
            if ($course->thumbnail_url && !str_starts_with($course->thumbnail_url, 'http')) {
                // Assuming url is like /storage/courses/thumbnails/xyz.jpg
                $relativePath = str_replace('/storage/', '', $course->thumbnail_url);
                if (Storage::disk('public')->exists($relativePath)) {
                    $newPath = 'courses/thumbnails/' . basename($relativePath);
                    Storage::disk('local')->put($newPath, Storage::disk('public')->get($relativePath));
                    $course->thumbnail_url = route('secure.asset', ['path' => $newPath]);
                    $course->save();
                }
            }

            // 2. Migrate 3D Asset
            if ($course->{'3d_asset_path'} && !str_starts_with($course->{'3d_asset_path'}, 'http')) {
                $relativePath = str_replace('/storage/', '', $course->{'3d_asset_path'});
                if (Storage::disk('public')->exists($relativePath)) {
                    $newPath = 'courses/3d_assets/' . basename($relativePath);
                    Storage::disk('local')->put($newPath, Storage::disk('public')->get($relativePath));
                    $course->{'3d_asset_path'} = route('secure.asset', ['path' => $newPath]);
                    $course->save();
                }
            }

            // 3. Migrate Video (Dispatch Job)
            if ($course->video_url && !str_starts_with($course->video_url, 'http') && !str_contains($course->video_url, 'secure-asset')) {
                $relativePath = str_replace('/storage/', '', $course->video_url);
                if (Storage::disk('public')->exists($relativePath)) {
                    $newPath = 'courses/videos/' . basename($relativePath);
                    // Move it to private storage first
                    Storage::disk('local')->put($newPath, Storage::disk('public')->get($relativePath));
                    // Queue for HLS
                    ProcessHLSVideo::dispatch(Course::class, $course->id, $newPath, 'video_url');
                }
            }

            // 4. Migrate Welcome Video (Dispatch Job)
            if ($course->welcome_video_url && !str_starts_with($course->welcome_video_url, 'http') && !str_contains($course->welcome_video_url, 'secure-asset')) {
                $relativePath = str_replace('/storage/', '', $course->welcome_video_url);
                if (Storage::disk('public')->exists($relativePath)) {
                    $newPath = 'courses/welcome_videos/' . basename($relativePath);
                    Storage::disk('local')->put($newPath, Storage::disk('public')->get($relativePath));
                    ProcessHLSVideo::dispatch(Course::class, $course->id, $newPath, 'welcome_video_url');
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Migration completed! Videos have been queued for HLS processing.");
    }
}
