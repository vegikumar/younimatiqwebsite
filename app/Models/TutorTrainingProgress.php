<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TutorTrainingProgress extends Model
{
    protected $table = 'tutor_training_progress';

    protected $fillable = [
        'tutor_id', 'item_id', 'status', 'score', 'submission_text',
        'submission_path', 'quiz_answers', 'review_notes', 'reviewed_by',
        'reviewed_at', 'submitted_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'quiz_answers' => 'array',
            'completed_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(TutorTrainingItem::class, 'item_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
