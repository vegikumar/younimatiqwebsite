<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgeGroup extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'age_range',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'age_group', 'slug');
    }

    public function linkedCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_age_group', 'age_group_id', 'course_id')
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }

    public function label(): string
    {
        return $this->age_range
            ? "{$this->name} ({$this->age_range})"
            : $this->name;
    }
}
