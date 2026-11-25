<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Skill extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'is_system'];

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (Skill $skill) {
            if (! $skill->slug) {
                $skill->slug = Str::slug($skill->name);
            }
        });
    }

    public function tutors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tutor_skills', 'skill_id', 'tutor_id')
            ->withPivot(['source', 'verified', 'earned_at'])
            ->withTimestamps();
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_skill_requirements')
            ->withTimestamps();
    }
}
