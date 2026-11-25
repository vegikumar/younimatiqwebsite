<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CareerPathway extends Model
{
    protected $fillable = [
        'slug', 'title', 'tagline', 'description', 'icon',
        'min_grade', 'max_grade', 'skill_tags', 'related_course_ids',
        'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'skill_tags' => 'array',
            'related_course_ids' => 'array',
            'is_active' => 'boolean',
            'min_grade' => 'integer',
            'max_grade' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForGrade($query, ?int $grade)
    {
        if ($grade === null) {
            return $query->where('min_grade', '<=', 12);
        }

        return $query->where('min_grade', '<=', $grade)->where('max_grade', '>=', $grade);
    }
}
