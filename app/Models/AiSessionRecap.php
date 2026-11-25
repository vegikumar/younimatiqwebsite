<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiSessionRecap extends Model
{
    protected $fillable = [
        'live_session_id',
        'tutor_id',
        'course_id',
        'transcript_summary',
        'edited_summary',
        'red_flag',
        'detected_pii',
        'status',
        'auto_send_at',
        'sent_at',
    ];

    protected $casts = [
        'auto_send_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function liveSession(): BelongsTo
    {
        return $this->belongsTo(LiveSession::class);
    }
}
