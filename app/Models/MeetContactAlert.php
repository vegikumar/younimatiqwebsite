<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetContactAlert extends Model
{
    protected $fillable = [
        'room_id',
        'source',
        'actor_name',
        'actor_role',
        'shared_text',
        'matches',
        'primary_type',
        'occurred_at',
        'reviewed_at',
        'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'matches' => 'array',
            'occurred_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeUnreviewed($query)
    {
        return $query->whereNull('reviewed_at');
    }

    public function markReviewed(?int $adminId = null): void
    {
        if ($this->reviewed_at) {
            return;
        }

        $this->update([
            'reviewed_at' => now(),
            'reviewed_by' => $adminId,
        ]);
    }

    public function typeLabel(): string
    {
        return match ($this->primary_type) {
            'email' => 'Email',
            'phone' => 'Phone number',
            'messaging_link' => 'Messaging link',
            default => 'Contact info',
        };
    }

    public function sourceLabel(): string
    {
        return match ($this->source) {
            'chat' => 'Chat',
            'speech' => 'Speech',
            'captions' => 'Captions (CC)',
            default => ucfirst((string) $this->source),
        };
    }
}
