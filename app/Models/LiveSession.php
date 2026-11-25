<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveSession extends Model
{
    protected $fillable = [
        'course_id', 'course_module_id', 'tutor_id', 'zoom_meeting_id', 'zoom_passcode',
        'start_time', 'duration_minutes', 'join_url', 'start_url', 'meeting_provider', 'meeting_room_id', 'max_students', 'status',
        'reminder_24h_sent', 'reminder_1h_sent', 'reminder_30m_sent',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'reminder_24h_sent' => 'boolean',
            'reminder_1h_sent' => 'boolean',
            'reminder_30m_sent' => 'boolean',
        ];
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function tutor()
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function module()
    {
        return $this->belongsTo(CourseModule::class, 'course_module_id');
    }
}
