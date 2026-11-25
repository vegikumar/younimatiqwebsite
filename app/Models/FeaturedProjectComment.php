<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeaturedProjectComment extends Model
{
    protected $fillable = [
        'featured_project_id',
        'user_id',
        'parent_id',
        'visitor_name',
        'content',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('created_at');
    }

    public function project()
    {
        return $this->belongsTo(FeaturedProject::class, 'featured_project_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
