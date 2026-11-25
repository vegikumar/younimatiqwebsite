<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipFolder extends Model
{
    use HasFactory;
    
    protected $fillable = ['name', 'parent_id'];

    public function parent()
    {
        return $this->belongsTo(TipFolder::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(TipFolder::class, 'parent_id');
    }

    public function tips()
    {
        return $this->hasMany(Tip::class, 'tip_folder_id');
    }
}
