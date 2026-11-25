<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSkillProgress extends Model
{
    protected $fillable = [
        'user_id',
        'skill_node_id',
        'status',
        'mastered_at',
    ];

    protected $casts = [
        'mastered_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skillNode(): BelongsTo
    {
        return $this->belongsTo(SkillNode::class);
    }
}
