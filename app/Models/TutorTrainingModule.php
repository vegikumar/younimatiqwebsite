<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TutorTrainingModule extends Model
{
    protected $fillable = [
        'title',
        'course_name',
        'class_name',
        'class_number',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(TutorTrainingItem::class, 'module_id')->orderBy('sort_order');
    }

    public function skillRewards(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'tutor_training_skill_rewards', 'module_id', 'skill_id')
            ->withTimestamps();
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TutorTrainingAssignment::class, 'module_id');
    }
}
