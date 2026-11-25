<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SkillTree extends Model
{
    protected $fillable = [
        'course_id',
        'name',
        'total_levels',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function nodes(): HasMany
    {
        return $this->hasMany(SkillNode::class)->orderBy('level_number')->orderBy('order');
    }
}
