<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PopupBanner extends Model
{
    protected $fillable = [
        'title',
        'image_path',
        'link_url',
        'coupon_code',
        'delay_seconds',
        'show_once',
        'is_active',
        'sort_order',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'delay_seconds' => 'integer',
            'show_once' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        if (! Storage::disk('public')->exists($this->image_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->image_path);
    }

    public function isCurrentlyActive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }
        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        return (bool) $this->imageUrl();
    }

    public function scopeCurrentlyActive(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where('is_active', true)
            ->whereNotNull('image_path')
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            });
    }

    public function toPublicPayload(): ?array
    {
        $url = $this->imageUrl();
        if (! $url || ! $this->isCurrentlyActive()) {
            return null;
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'image_url' => $url,
            'link_url' => $this->link_url ?: null,
            'coupon_code' => $this->coupon_code ?: null,
            'delay_seconds' => max(0, min(30, (int) $this->delay_seconds)),
            'show_once' => (bool) $this->show_once,
        ];
    }

    public function deleteImage(): void
    {
        if ($this->image_path && Storage::disk('public')->exists($this->image_path)) {
            Storage::disk('public')->delete($this->image_path);
        }
    }
}
