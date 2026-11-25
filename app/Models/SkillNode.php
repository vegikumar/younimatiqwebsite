<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SkillNode extends Model
{
    protected $fillable = [
        'skill_tree_id',
        'title',
        'description',
        'icon',
        'level_number',
        'order',
    ];

    public function skillTree(): BelongsTo
    {
        return $this->belongsTo(SkillTree::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(StudentSkillProgress::class);
    }
}
