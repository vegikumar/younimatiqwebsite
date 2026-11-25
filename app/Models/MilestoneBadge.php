<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MilestoneBadge extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'badge_icon',
        'is_system',
        'course_id',
        'created_by',
        'level_number',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function studentMilestones(): HasMany
    {
        return $this->hasMany(StudentMilestone::class, 'badge_id');
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(StudentReward::class, 'badge_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }
}
