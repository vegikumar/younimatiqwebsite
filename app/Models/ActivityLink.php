<?php

namespace App\Models;

use App\Support\HtmlGameGuard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ActivityLink extends Model
{
    protected $fillable = [
        'title',
        'description',
        'subject',
        'grade',
        'access',
        'status',
        'slug',
        'file_path',
        'original_filename',
        'thumbnail_path',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->is_active;
    }

    public function isPublicAccess(): bool
    {
        return $this->access === 'public';
    }

    public function thumbnailUrl(): ?string
    {
        if (! $this->thumbnail_path) {
            return null;
        }

        if (! Storage::disk('public')->exists($this->thumbnail_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->thumbnail_path);
    }

    public function publicUrl(): string
    {
        return rtrim((string) config('app.url'), '/').'/'.$this->slug;
    }

    public function htmlContents(): string
    {
        if (! preg_match('#^activity-links/[A-Za-z0-9._-]+\.html$#', (string) $this->file_path)) {
            return '';
        }

        if (! Storage::disk('local')->exists($this->file_path)) {
            return '';
        }

        return (string) Storage::disk('local')->get($this->file_path);
    }

    public function httpResponse(): Response
    {
        if (! preg_match('#^activity-links/[A-Za-z0-9._-]+\.html$#', (string) $this->file_path)) {
            abort(404);
        }

        if (! Storage::disk('local')->exists($this->file_path)) {
            abort(404);
        }

        $html = (string) Storage::disk('local')->get($this->file_path);
        // Direct HTML responses must still require the secure player embed.
        $html = HtmlGameGuard::inject($html, requireEmbed: true);

        return response($html, 200, HtmlGameGuard::responseHeaders(allowSameOriginFrame: true));
    }

    public function deleteStoredFile(): void
    {
        if ($this->file_path && Storage::disk('local')->exists($this->file_path)) {
            Storage::disk('local')->delete($this->file_path);
        }
    }

    public function deleteThumbnail(): void
    {
        if ($this->thumbnail_path && Storage::disk('public')->exists($this->thumbnail_path)) {
            Storage::disk('public')->delete($this->thumbnail_path);
        }
    }

    public function tutors()
    {
        return $this->belongsToMany(User::class, 'activity_link_tutor', 'activity_link_id', 'tutor_id')
            ->withPivot(['assigned_by'])
            ->withTimestamps();
    }
}
