<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentMilestone extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'badge_id',
        'certificate_hash',
        'unlocked_at',
    ];

    protected $casts = [
        'unlocked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function badge(): BelongsTo
    {
        return $this->belongsTo(MilestoneBadge::class, 'badge_id');
    }
}
