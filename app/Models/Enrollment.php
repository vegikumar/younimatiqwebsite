<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    protected $fillable = [
        'user_id', 'course_id', 'payment_id', 'upsold_by_tutor_id', 'status', 'progress', 'completed_at', 'certificate_path', 'pricing_model', 'paid_classes_count', 'delivery_mode'
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function hasUploadedCertificate(): bool
    {
        return !empty($this->certificate_path);
    }
}
