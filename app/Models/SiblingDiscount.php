<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiblingDiscount extends Model
{
    protected $fillable = [
        'parent_id',
        'primary_student_id',
        'sibling_student_id',
        'discount_percent',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function primaryStudent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'primary_student_id');
    }

    public function siblingStudent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sibling_student_id');
    }
}
