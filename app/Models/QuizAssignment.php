<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizAssignment extends Model
{
    protected $fillable = ['quiz_id', 'assignable_id', 'assignable_type'];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function assignable()
    {
        return $this->morphTo();
    }
}
