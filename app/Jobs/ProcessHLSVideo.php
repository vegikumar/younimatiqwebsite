<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use FFMpeg\Format\Video\X264;
use Illuminate\Support\Facades\Storage;
use App\Models\Course;

class ProcessHLSVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $modelClass;
    public $modelId;
    public $originalVideoPath;
    public $attributeName;

    /**
     * Create a new job instance.
     */
    public function __construct($modelClass, $modelId, $originalVideoPath, $attributeName = 'video_url')
    {
        $this->modelClass = $modelClass;
        $this->modelId = $modelId;
        $this->originalVideoPath = $originalVideoPath;
        $this->attributeName = $attributeName;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $model = $this->modelClass::find($this->modelId);
        
        if (!$model || !Storage::disk('local')->exists($this->originalVideoPath)) {
            return;
        }

        // Generate unique folder name based on video file
        $fileInfo = pathinfo($this->originalVideoPath);
        $folderPath = 'courses/hls/' . $fileInfo['filename'];
        $m3u8Path = $folderPath . '/' . $fileInfo['filename'] . '.m3u8';

        // 720p format for HLS (You can add more bitrates/resolutions here)
        $lowBitrateFormat = (new X264('aac'))->setKiloBitrate(1000);

        // Process via FFmpeg
        FFMpeg::fromDisk('local')
            ->open($this->originalVideoPath)
            ->exportForHLS()
            ->addFormat($lowBitrateFormat)
            ->toDisk('local')
            ->save($m3u8Path);

        // Update the model with the secure route
        $attribute = $this->attributeName;
        $model->$attribute = route('secure.asset', ['path' => $m3u8Path], false);
        $model->save();

        // Optionally, delete the original mp4 to save space
        Storage::disk('local')->delete($this->originalVideoPath);
    }
}
