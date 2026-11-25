<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'student_id', 'course_id', 'transaction_id', 'gateway', 'currency', 'amount', 'status', 'pricing_model', 'delivery_mode', 'schedule',
    ];

    protected function casts(): array
    {
        return [
            'schedule' => 'array',
        ];
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function order()
    {
        return $this->hasOne(Order::class);
    }
}
