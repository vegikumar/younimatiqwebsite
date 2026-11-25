<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassFeedback extends Model
{
    use HasFactory;

    protected $fillable = [
        'bookable_id',
        'bookable_type',
        'tutor_id',
        'student_id',
        'session_type',
        'customer_type',
        'feedback_data',
        'admin_notes',
        'shared_with_parent_at',
        'parent_message',
        'shared_by',
    ];

    protected $casts = [
        'feedback_data' => 'array',
        'shared_with_parent_at' => 'datetime',
    ];

    public function bookable()
    {
        return $this->morphTo();
    }

    public function tutor()
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
