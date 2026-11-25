<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TutorMatchSuggestion extends Model
{
    protected $fillable = [
        'tutor_id', 'course_id', 'booking_id', 'reason', 'status', 'match_score', 'score_breakdown',
    ];

    protected function casts(): array
    {
        return [
            'match_score' => 'float',
            'score_breakdown' => 'array',
        ];
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
}
