<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentReward extends Model
{
    protected $fillable = [
        'student_id',
        'tutor_id',
        'course_id',
        'booking_id',
        'badge_id',
        'xp_amount',
        'compliments',
        'level_up_tip',
        'is_trial',
        'awarded_at',
    ];

    protected function casts(): array
    {
        return [
            'compliments' => 'array',
            'is_trial' => 'boolean',
            'awarded_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function badge(): BelongsTo
    {
        return $this->belongsTo(MilestoneBadge::class, 'badge_id');
    }
}
