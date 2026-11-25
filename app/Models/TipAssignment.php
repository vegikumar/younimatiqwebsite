<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipAssignment extends Model
{
    use HasFactory;
    
    protected $fillable = ['student_id', 'tip_folder_id', 'tip_id', 'expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function folder()
    {
        return $this->belongsTo(TipFolder::class, 'tip_folder_id');
    }

    public function tip()
    {
        return $this->belongsTo(Tip::class, 'tip_id');
    }
}
