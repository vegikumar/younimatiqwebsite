<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassroomDiscussion extends Model
{
    protected $fillable = [
        'course_id',
        'user_id',
        'parent_id',
        'content',
        'attachments',
    ];

    protected $casts = [
        'attachments' => 'array',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ClassroomDiscussion::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ClassroomDiscussion::class, 'parent_id')->oldest();
    }

    public function mentions(): HasMany
    {
        return $this->hasMany(ClassroomMention::class, 'discussion_id');
    }
}
