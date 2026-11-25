<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = ['user_id', 'course_id', 'rating', 'comment', 'reviewer_name', 'review_token'];

    /**
     * Get the display name for the reviewer (guest or authenticated user).
     */
    public function getReviewerDisplayNameAttribute(): string
    {
        if ($this->user_id && $this->user) {
            return $this->user->name;
        }
        return $this->reviewer_name ?? 'Anonymous';
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
