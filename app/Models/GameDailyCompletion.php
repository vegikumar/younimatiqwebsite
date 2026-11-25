<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameDailyCompletion extends Model
{
    protected $fillable = [
        'user_id',
        'game_key',
        'day',
        'points',
    ];

    protected function casts(): array
    {
        return [
            'day' => 'date',
            'points' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
