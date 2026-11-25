<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    protected $fillable = [
        'title', 'slug', 'excerpt', 'content', 'featured_image', 'author', 'seo_metadata', 'status', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'seo_metadata' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhereDate('published_at', '<=', now());
            });
    }
}
