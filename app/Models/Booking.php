<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

class Booking extends Model
{
    protected $fillable = [
        'student_id', 'tutor_id', 'course_id', 'payment_id', 'status',
        'description', 'zoom_join_url', 'zoom_start_url', 'zoom_passcode', 'meeting_provider', 'meeting_room_id',
        'timezone', 'parent_timezone', 'tutor_timezone', 'session_type', 'scheduled_at',
        'reminder_24h_sent', 'reminder_1h_sent', 'reminder_30m_sent',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
        ];
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function tutor()
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
