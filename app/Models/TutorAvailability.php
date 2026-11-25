<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TutorAvailability extends Model
{
    protected $fillable = [
        'tutor_id',
        'course_id',
        'format_type',
        'day_of_week',
        'start_time',
        'end_time',
        'specific_date',
        'is_recurring',
        'is_booked',
        'timezone',
        'max_capacity',
    ];

    protected $casts = [
        'is_recurring' => 'boolean',
        'is_booked' => 'boolean',
        'specific_date' => 'date',
        'max_capacity' => 'integer',
    ];

    public function scopeTutoring($query)
    {
        return $query->where('format_type', 'tutoring_1on1');
    }

    public function scopeGroupClass($query)
    {
        return $query->where('format_type', 'group_class');
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
