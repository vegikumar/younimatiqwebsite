<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    protected $fillable = [
        'author_name',
        'text',
        'rating',
        'avatar_path',
        'video_path',
        'is_active',
        'sort_order',
    ];
}
