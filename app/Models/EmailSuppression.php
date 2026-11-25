<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailSuppression extends Model
{
    protected $fillable = [
        'email',
        'reason',
        'source',
        'suppressed_at',
    ];

    protected function casts(): array
    {
        return [
            'suppressed_at' => 'datetime',
        ];
    }

    public static function suppress(string $email, string $reason = 'unsubscribe', ?string $source = null): self
    {
        $email = strtolower(trim($email));

        return static::updateOrCreate(
            ['email' => $email],
            [
                'reason' => $reason,
                'source' => $source,
                'suppressed_at' => now(),
            ]
        );
    }

    public static function isSuppressed(string $email): bool
    {
        return static::where('email', strtolower(trim($email)))->exists();
    }
}
