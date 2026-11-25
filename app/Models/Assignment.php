<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    protected $fillable = ['course_id', 'course_module_id', 'title', 'description', 'questions', 'file_path', 'due_at', 'xp_reward'];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'questions' => 'array',
        ];
    }

    public function hasQuestions(): bool
    {
        return !empty($this->questions);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function module()
    {
        return $this->belongsTo(CourseModule::class, 'course_module_id');
    }

    public function submissions()
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function students()
    {
        return $this->belongsToMany(User::class, 'assignment_user');
    }
}
