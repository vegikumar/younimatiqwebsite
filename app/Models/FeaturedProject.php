<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeaturedProject extends Model
{
    protected $fillable = [
        'title',
        'author_name',
        'category',
        'video_path',
        'project_url',
        'views_count',
        'likes_count',
        'is_active',
    ];

    public function likes()
    {
        return $this->hasMany(FeaturedProjectLike::class, 'featured_project_id');
    }

    public function comments()
    {
        return $this->hasMany(FeaturedProjectComment::class, 'featured_project_id');
    }
}
