<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tip extends Model
{
    use HasFactory;
    
    protected $fillable = ['tip_folder_id', 'title', 'type', 'content_url'];

    public function folder()
    {
        return $this->belongsTo(TipFolder::class, 'tip_folder_id');
    }
}
