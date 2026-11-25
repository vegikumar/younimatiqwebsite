<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class HrAuditEvent extends Model
{
    protected $fillable = [
        'event_type', 'actor_id', 'subject_user_id', 'subject_type', 'subject_id', 'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function subjectUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subject_user_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public static function log(string $type, ?User $actor, ?User $subjectUser, $subject = null, array $payload = []): self
    {
        return self::create([
            'event_type' => $type,
            'actor_id' => $actor?->id,
            'subject_user_id' => $subjectUser?->id,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->id,
            'payload' => $payload,
        ]);
    }
}
