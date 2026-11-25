<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GamePlaySession extends Model
{
    protected $fillable = [
        'jti',
        'game_key',
        'user_id',
        'used',
        'rewarded',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'used' => 'boolean',
            'rewarded' => 'boolean',
            'used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
