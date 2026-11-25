<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TutorPayout extends Model
{
    protected $fillable = [
        'tutor_id',
        'period_month',
        'period_year',
        'base_earnings',
        'upsell_commission',
        'bonus',
        'net_payout',
        'status',
        'itemized_details',
    ];

    protected $casts = [
        'itemized_details' => 'array',
        'base_earnings' => 'float',
        'upsell_commission' => 'float',
        'bonus' => 'float',
        'net_payout' => 'float',
    ];

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }
}
