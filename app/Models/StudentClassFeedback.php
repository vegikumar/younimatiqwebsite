<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentClassFeedback extends Model
{
    protected $table = 'student_class_feedback';

    protected $fillable = [
        'student_id',
        'booking_id',
        'tutor_id',
        'rating',
        'class_experience',
        'tutor_communication',
        'would_recommend',
        'comments',
        'admin_notes',
        'reviewed_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function tutor()
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
