<?php

namespace App\Services;

use App\Models\ActivityLink;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ActivityLinkService
{
    public function validatedMeta(Request $request, bool $creating): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'subject' => ['nullable', 'string', 'max:80'],
            'grade' => ['nullable', 'string', 'max:40'],
            'access' => ['nullable', Rule::in(['members'])],
            'status' => ['required', Rule::in(['published', 'draft'])],
            'slug' => [$creating ? 'nullable' : 'required', 'string', 'max:80'],
            'html' => ['nullable', 'string', 'max:10485760'],
            'file' => ['nullable', 'file', 'max:10240'],
            'thumbnail' => ['nullable', 'image', 'max:4096'],
            'remove_thumbnail' => ['nullable', 'boolean'],
        ]);
    }

    public function createFromRequest(Request $request, ?int $createdBy = null): ActivityLink
    {
        $data = $this->validatedMeta($request, creating: true);
        $slug = $this->normalizeSlug($data['slug'] ?: $data['title']);
        $this->assertSlug($slug);

        $html = $this->resolveHtml($request, creating: true);
        $status = $data['status'];

        return ActivityLink::create([
            'title' => trim($data['title']),
            'description' => $data['description'] ?? '',
            'subject' => $data['subject'] ?? '',
            'grade' => $data['grade'] ?? '',
            'access' => 'members',
            'status' => $status,
            'slug' => $slug,
            'file_path' => $this->storeHtmlString($html),
            'original_filename' => $request->hasFile('file')
                ? $this->originalName($request->file('file'))
                : 'pasted.html',
            'thumbnail_path' => $this->storeThumbnail($request->file('thumbnail')),
            'is_active' => $status === 'published',
            'created_by' => $createdBy,
        ]);
    }

    public function resolveHtml(Request $request, bool $creating): ?string
    {
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $extension = strtolower($file->getClientOriginalExtension());
            if (! in_array($extension, ['html', 'htm'], true)) {
                throw ValidationException::withMessages([
                    'file' => 'Only .html or .htm files are allowed.',
                ]);
            }
            $contents = file_get_contents($file->getRealPath());
            if ($contents === false || trim($contents) === '') {
                throw ValidationException::withMessages(['file' => 'That file is empty.']);
            }

            return $contents;
        }

        $html = trim((string) $request->input('html', ''));
        if ($html !== '') {
            return $html;
        }

        if ($creating) {
            throw ValidationException::withMessages([
                'html' => 'Paste the HTML game code, or upload an .html file.',
            ]);
        }

        return null;
    }

    public function storeThumbnail(?UploadedFile $file): ?string
    {
        if (! $file) {
            return null;
        }

        return $file->store('activity-thumbnails', 'public');
    }

    public function originalName(UploadedFile $file): string
    {
        $name = basename(str_replace('\\', '/', $file->getClientOriginalName()));

        return Str::limit($name, 180, '');
    }

    public function normalizeSlug(string $value): string
    {
        return Str::slug($value);
    }

    public function assertSlug(string $slug, ?int $ignoreId = null): void
    {
        if ($slug === '' || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            throw ValidationException::withMessages([
                'slug' => 'Use a link name made of lowercase letters, numbers, and hyphens.',
            ]);
        }

        $taken = ActivityLink::query()
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists();

        if ($taken || $this->slugIsReserved($slug)) {
            throw ValidationException::withMessages([
                'slug' => 'That link name is already used. Pick another one.',
            ]);
        }
    }

    public function slugIsReserved(string $slug): bool
    {
        $blocked = [
            'admin', 'student', 'tutor', 'parent', 'api', 'login', 'register', 'logout',
            'storage', 'build', 'assets', 'blog', 'courses', 'pricing', 'page', 'csrf-token',
        ];
        if (in_array($slug, $blocked, true)) {
            return true;
        }

        try {
            $route = Route::getRoutes()->match(Request::create('/'.$slug, 'GET'));
        } catch (NotFoundHttpException) {
            return false;
        }

        return $route->getName() !== 'category.landing';
    }

    public function storeHtmlString(string $contents): string
    {
        $contents = trim($contents);
        if ($contents === '') {
            throw ValidationException::withMessages(['html' => 'Paste the full HTML game code.']);
        }

        if (preg_match('/<\?(php|=)/i', $contents)) {
            throw ValidationException::withMessages([
                'html' => 'This code contains PHP. Paste plain HTML only.',
            ]);
        }

        if (! preg_match('/<!doctype\s+html|<html[\s>]/i', $contents)) {
            throw ValidationException::withMessages([
                'html' => 'This does not look like a web page. Paste a full HTML document that starts with <!DOCTYPE html> or <html>.',
            ]);
        }

        $name = 'activity-links/'.Str::uuid().'.html';
        Storage::disk('local')->put($name, $contents);

        return $name;
    }
}
