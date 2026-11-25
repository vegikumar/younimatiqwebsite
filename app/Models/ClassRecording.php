<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassRecording extends Model
{
    public const RETENTION_DAYS = 60;

    protected $fillable = [
        'course_id',
        'live_session_id',
        'booking_id',
        'meeting_room_id',
        'provider',
        'meet_recording_id',
        'title',
        'recording_url',
        'storage_path',
        'duration_minutes',
        'file_size_mb',
        'recorded_at',
        'expires_at',
        'deleted_from_storage_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'expires_at' => 'datetime',
        'deleted_from_storage_at' => 'datetime',
        'file_size_mb' => 'float',
    ];

    protected static function booted(): void
    {
        static::creating(function (ClassRecording $recording) {
            if (! $recording->expires_at) {
                $base = $recording->recorded_at ?? now();
                $recording->expires_at = Carbon::parse($base)->addDays(self::RETENTION_DAYS);
            }
        });
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function liveSession(): BelongsTo
    {
        return $this->belongsTo(LiveSession::class, 'live_session_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(RecordingAssignment::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(RecordingRequest::class);
    }

    public function isAvailable(): bool
    {
        if ($this->deleted_from_storage_at) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function retentionEndsAt(): ?Carbon
    {
        return $this->expires_at;
    }
}
