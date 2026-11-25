<?php

namespace Tests\Feature;

use App\Services\ImageUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageUploadWebpTest extends TestCase
{
    use RefreshDatabase;

    private ImageUploadService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->service = new ImageUploadService;
    }

    public function test_png_upload_is_converted_to_webp(): void
    {
        $file = UploadedFile::fake()->image('photo.png', 120, 90);

        $result = $this->service->store($file, 'categories', 'public');

        $this->assertTrue($result['converted']);
        $this->assertStringEndsWith('.webp', $result['path']);
        Storage::disk('public')->assertExists($result['path']);
        $this->assertSame('image/webp', mime_content_type(Storage::disk('public')->path($result['path'])));
    }

    public function test_jpg_upload_is_converted_to_webp(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg', 200, 200);

        $result = $this->service->store($file, 'blogs', 'public');

        $this->assertTrue($result['converted']);
        $this->assertStringEndsWith('.webp', $result['path']);
        Storage::disk('public')->assertExists($result['path']);
    }

    public function test_store_path_returns_only_path(): void
    {
        $file = UploadedFile::fake()->image('avatar.png', 64, 64);

        $path = $this->service->storePath($file, 'avatars', 'public');

        $this->assertIsString($path);
        $this->assertStringStartsWith('avatars/', $path);
        $this->assertStringEndsWith('.webp', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_non_image_file_is_stored_untouched(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 20, 'application/pdf');

        $result = $this->service->store($file, 'assignments', 'public');

        $this->assertFalse($result['converted']);
        $this->assertStringEndsWith('.pdf', $result['path']);
        Storage::disk('public')->assertExists($result['path']);
    }

    public function test_svg_is_not_converted(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"></svg>';
        $file = UploadedFile::fake()->createWithContent('icon.svg', $svg);

        $this->assertFalse($this->service->isConvertibleImage($file));

        $result = $this->service->store($file, 'icons', 'public');

        $this->assertFalse($result['converted']);
        $this->assertStringEndsWith('.svg', $result['path']);
    }

    public function test_convert_stored_path_converts_and_removes_original(): void
    {
        $file = UploadedFile::fake()->image('cats.png', 100, 100);
        $originalPath = $file->store('categories', 'public');

        $result = $this->service->convertStoredPath($originalPath, 'public');

        $this->assertNotNull($result);
        $this->assertTrue($result['converted']);
        $this->assertStringEndsWith('.webp', $result['path']);
        Storage::disk('public')->assertExists($result['path']);
        Storage::disk('public')->assertMissing($originalPath);
    }

    public function test_convert_stored_path_is_noop_for_existing_webp(): void
    {
        $file = UploadedFile::fake()->image('cats.png', 100, 100);
        $webpPath = $this->service->storePath($file, 'categories', 'public');

        $result = $this->service->convertStoredPath($webpPath, 'public');

        $this->assertNotNull($result);
        $this->assertFalse($result['converted']);
        $this->assertSame($webpPath, $result['path']);
    }
}
