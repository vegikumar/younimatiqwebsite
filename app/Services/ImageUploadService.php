<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Throwable;

class ImageUploadService
{
    /**
     * Image mime types that should be re-encoded to WebP.
     *
     * @var array<int, string>
     */
    protected array $convertibleMimes = [
        'image/jpeg',
        'image/jpg',
        'image/pjpeg',
        'image/png',
        'image/x-png',
        'image/gif',
        'image/bmp',
        'image/x-ms-bmp',
        'image/webp',
        'image/avif',
    ];

    protected const WEBP_QUALITY = 82;

    public function __construct(
        protected ?ImageManager $manager = null,
    ) {
        $this->manager ??= new ImageManager(new Driver);
    }

    /**
     * Store an uploaded file, converting real images to WebP.
     *
     * @return array{path: string, url: string, converted: bool}
     */
    public function store(UploadedFile $file, string $directory, string $disk = 'public'): array
    {
        if (! $this->isConvertibleImage($file)) {
            $path = $file->store($directory, $disk);

            return [
                'path' => $path,
                'url' => $this->urlFor($path, $disk),
                'converted' => false,
            ];
        }

        $converted = $this->convertUploadedFileToWebp($file, $directory, $disk);

        if ($converted !== null) {
            return $converted;
        }

        // Fall back to storing the original file untouched.
        $path = $file->store($directory, $disk);

        return [
            'path' => $path,
            'url' => $this->urlFor($path, $disk),
            'converted' => false,
        ];
    }

    /**
     * Return only the stored path (most common convention in this app).
     */
    public function storePath(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        return $this->store($file, $directory, $disk)['path'];
    }

    /**
     * Return only the public URL (used by Blog thumbnails etc.).
     */
    public function storeUrl(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        return $this->store($file, $directory, $disk)['url'];
    }

    /**
     * Convert an already-stored image on disk to WebP in place.
     *
     * @return array{path: string, url: string, converted: bool}|null Null when not convertible.
     */
    public function convertStoredPath(string $path, string $disk = 'public'): ?array
    {
        if (! Storage::disk($disk)->exists($path)) {
            return null;
        }

        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'webp') {
            return [
                'path' => $path,
                'url' => $this->urlFor($path, $disk),
                'converted' => false,
            ];
        }

        $fullPath = Storage::disk($disk)->path($path);

        if (! $this->isImageFile($fullPath)) {
            return null;
        }

        $newPath = $this->webpPathFor($path);
        $newFullPath = Storage::disk($disk)->path($newPath);

        try {
            $image = $this->manager->decodePath($fullPath);
            $encoded = $image->encode($this->webpEncoder());
            $encoded->save($newFullPath);

            if (! is_file($newFullPath) || filesize($newFullPath) === 0) {
                @unlink($newFullPath);

                return null;
            }

            Storage::disk($disk)->delete($path);

            return [
                'path' => $newPath,
                'url' => $this->urlFor($newPath, $disk),
                'converted' => true,
            ];
        } catch (Throwable $e) {
            Log::warning('ImageUploadService: failed to convert stored image', [
                'path' => $path,
                'disk' => $disk,
                'error' => $e->getMessage(),
            ]);

            if (is_file($newFullPath) && $newFullPath !== $fullPath) {
                @unlink($newFullPath);
            }

            return null;
        }
    }

    /**
     * Whether the uploaded file is a real raster image we can convert.
     */
    public function isConvertibleImage(UploadedFile $file): bool
    {
        $mime = $file->getMimeType();

        if ($mime === 'image/svg+xml') {
            // SVG is vector; converting to raster WebP loses scalability.
            return false;
        }

        return in_array($mime, $this->convertibleMimes, true);
    }

    protected function convertUploadedFileToWebp(UploadedFile $file, string $directory, string $disk): ?array
    {
        $realPath = $file->getRealPath();

        if (! $realPath || ! is_file($realPath)) {
            return null;
        }

        $directory = trim($directory, '/');
        $filename = (string) Str::ulid();
        $relativePath = ($directory !== '' ? $directory.'/' : '').$filename.'.webp';
        $fullPath = Storage::disk($disk)->path($relativePath);

        try {
            $image = $this->manager->decodePath($realPath);
            $encoded = $image->encode($this->webpEncoder());

            if (! is_dir(dirname($fullPath))) {
                mkdir(dirname($fullPath), 0755, true);
            }

            $encoded->save($fullPath);

            if (! is_file($fullPath) || filesize($fullPath) === 0) {
                @unlink($fullPath);

                return null;
            }

            return [
                'path' => $relativePath,
                'url' => $this->urlFor($relativePath, $disk),
                'converted' => true,
            ];
        } catch (Throwable $e) {
            Log::warning('ImageUploadService: failed to convert uploaded image', [
                'name' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
            ]);

            if (is_file($fullPath)) {
                @unlink($fullPath);
            }

            return null;
        }
    }

    protected function webpEncoder(): object
    {
        return new WebpEncoder(self::WEBP_QUALITY);
    }

    protected function webpPathFor(string $path): string
    {
        $dir = pathinfo($path, PATHINFO_DIRNAME);
        $name = pathinfo($path, PATHINFO_FILENAME);

        return ($dir && $dir !== '.' ? $dir.'/' : '').$name.'.webp';
    }

    protected function urlFor(string $path, string $disk): string
    {
        return Storage::disk($disk)->url($path);
    }

    protected function isImageFile(string $fullPath): bool
    {
        $mime = @mime_content_type($fullPath);

        if (is_string($mime) && str_starts_with($mime, 'image/') && $mime !== 'image/svg+xml') {
            return getimagesize($fullPath) !== false || $mime === 'image/webp';
        }

        return false;
    }
}
