<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code', 'type', 'value', 'min_amount', 'max_uses', 'used_count', 'expires_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function isValid(float $amount = 0): bool
    {
        if (!$this->is_active) {
            return false;
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }
        if ($this->max_uses && $this->used_count >= $this->max_uses) {
            return false;
        }
        if ($this->min_amount && $amount < $this->min_amount) {
            return false;
        }

        return true;
    }

    public function applyDiscount(float $amount): float
    {
        if ($this->type === 'percent') {
            return max(0, $amount - ($amount * $this->value / 100));
        }

        return max(0, $amount - $this->value);
    }
}
