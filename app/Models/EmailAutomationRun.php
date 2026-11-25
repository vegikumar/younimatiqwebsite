<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailAutomationRun extends Model
{
    protected $fillable = [
        'email_automation_id',
        'trigger',
        'status',
        'recipient_count',
        'sent_count',
        'failed_count',
        'course_snapshot',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'course_snapshot' => 'array',
            'recipient_count' => 'integer',
            'sent_count' => 'integer',
            'failed_count' => 'integer',
        ];
    }

    public function automation(): BelongsTo
    {
        return $this->belongsTo(EmailAutomation::class, 'email_automation_id');
    }
}
