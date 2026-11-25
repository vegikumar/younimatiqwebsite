<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    protected $fillable = ['course_id', 'course_module_id', 'title', 'description', 'questions', 'passing_score', 'creator_id', 'xp_reward'];

    protected function casts(): array
    {
        return [
            'questions' => 'array',
        ];
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function assignments()
    {
        return $this->hasMany(QuizAssignment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
