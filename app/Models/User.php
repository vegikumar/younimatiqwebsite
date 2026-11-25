<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    protected $fillable = ['name', 'email', 'password', 'role', 'avatar', 'timezone', 'bio', 'teaching_style', 'intro_video_url', 'policy_training_completed_at', 'nps_score', 'status', 'notification_preferences', 'is_auto_created', 'trial_ends_at', 'xp', 'grade', 'leaderboard_opt_out'];
    // Hide email from accidental model JSON dumps (tutor/student panels).
    // Admin pages must map email explicitly via $user->email (attribute access
    // still works). Own-profile auth props also set email explicitly.
    protected $hidden = ['password', 'remember_token', 'email'];

    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public function hasCompletedPolicyTraining(): bool
    {
        return $this->policy_training_completed_at !== null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'notification_preferences' => 'array',
            'trial_ends_at' => 'datetime',
            'is_auto_created' => 'boolean',
            'leaderboard_opt_out' => 'boolean',
            'grade' => 'integer',
        ];
    }

    public function aiProjectReviews(): HasMany
    {
        return $this->hasMany(AiProjectReview::class, 'student_id');
    }

    /**
     * Get the user's web push subscriptions.
     */
    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function wantsEmail(string $key): bool
    {
        return (bool) ($this->notification_preferences[$key] ?? true);
    }
    public function tipAssignments()
    {
        return $this->hasMany(TipAssignment::class, "student_id");
    }

    public function assignments()
    {
        return $this->belongsToMany(Assignment::class, 'assignment_user');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function rewardsReceived()
    {
        return $this->hasMany(StudentReward::class, 'student_id');
    }

    public function rewardsGiven()
    {
        return $this->hasMany(StudentReward::class, 'tutor_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'student_id');
    }

    public function courses()
    {
        return $this->hasMany(\App\Models\Course::class, 'tutor_id');
    }

    public function tutorProfile()
    {
        return $this->hasOne(TutorProfile::class);
    }

    public function tutorSkills()
    {
        return $this->hasMany(TutorSkill::class, 'tutor_id');
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'tutor_skills', 'tutor_id', 'skill_id')
            ->withPivot(['source', 'verified', 'earned_at'])
            ->withTimestamps();
    }

    public function trainingAssignments()
    {
        return $this->hasMany(TutorTrainingAssignment::class, 'tutor_id');
    }

    public function children()
    {
        return $this->belongsToMany(User::class, 'parent_student', 'parent_id', 'student_id')
            ->withPivot(['relationship', 'consent_at', 'linked_by', 'ai_assist_allowed'])
            ->withTimestamps();
    }

    public function parents()
    {
        return $this->belongsToMany(User::class, 'parent_student', 'student_id', 'parent_id')
            ->withPivot(['relationship', 'consent_at', 'linked_by', 'ai_assist_allowed'])
            ->withTimestamps();
    }
}

