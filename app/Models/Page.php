<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = ['title', 'slug', 'content', 'seo_metadata', 'is_published'];

    protected function casts(): array
    {
        return [
            'seo_metadata' => 'array',
            'is_published' => 'boolean',
        ];
    }
}
