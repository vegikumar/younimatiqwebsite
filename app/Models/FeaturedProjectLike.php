<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeaturedProjectLike extends Model
{
    protected $fillable = [
        'featured_project_id',
        'user_id',
        'visitor_key',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(FeaturedProject::class, 'featured_project_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
