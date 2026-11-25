<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseModule extends Model
{
    protected $fillable = ['course_id', 'unit_id', 'title', 'order', 'content_type', 'content_url', 'host_url', 'content_text', 'description', 'learning_outcome', 'practical_video_url', 'duration_minutes'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function unit()
    {
        return $this->belongsTo(CourseUnit::class, 'unit_id');
    }
}
