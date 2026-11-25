<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassSchedule extends Model
{
    protected $fillable = [
        'booking_id',
        'live_session_id',
        'tutor_id',
        'student_id',
        'scheduled_at',
        'zoom_join_url',
        'zoom_start_url',
        'meeting_provider',
        'meeting_room_id',
        'status',
        'reminder_sent',
        'reminder_24h_sent',
        'reminder_1h_sent',
        'reminder_30m_sent',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'reminder_sent' => 'boolean',
        ];
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function liveSession()
    {
        return $this->belongsTo(LiveSession::class);
    }

    public function tutor()
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
