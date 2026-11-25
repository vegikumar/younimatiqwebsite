<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotTraining extends Model
{
    protected $fillable = [
        'question',
        'answer',
        'tags'
    ];
}
