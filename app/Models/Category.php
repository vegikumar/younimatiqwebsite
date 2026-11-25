<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'icon', 'image', 'seo_metadata', 'landing_page_data', 'sort_order', 'is_active'];

    protected static function booted(): void
    {
        static::saved(fn () => \App\Support\NavigationData::forget());
        static::deleted(fn () => \App\Support\NavigationData::forget());
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'seo_metadata' => 'array',
            'landing_page_data' => 'array',
        ];
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class);
    }
}
