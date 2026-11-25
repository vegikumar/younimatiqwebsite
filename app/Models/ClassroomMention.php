<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassroomMention extends Model
{
    protected $fillable = [
        'discussion_id',
        'mentioned_user_id',
        'notified_at',
    ];

    protected $casts = [
        'notified_at' => 'datetime',
    ];

    public function discussion(): BelongsTo
    {
        return $this->belongsTo(ClassroomDiscussion::class, 'discussion_id');
    }

    public function mentionedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentioned_user_id');
    }
}
