<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampBooking extends Model
{
    protected $fillable = [
        'course_id',
        'tutor_id',
        'parent_name',
        'child_name',
        'camp_name',
        'email',
        'phone',
        'whatsapp_number',
        'age',
        'state',
        'country',
        'timezone',
        'parent_timezone',
        'tutor_timezone',
        'scheduled_at',
        'zoom_join_url',
        'zoom_start_url',
        'zoom_passcode',
        'meeting_provider',
        'meeting_room_id',
        'payment_status',
        'payment_method',
        'amount',
        'currency',
        'razorpay_order_id',
        'razorpay_payment_id',
        'paypal_order_id',
        'reminder_24h_sent',
        'reminder_1h_sent',
        'reminder_30m_sent',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
        ];
    }
}
