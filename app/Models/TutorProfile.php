<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TutorProfile extends Model
{
    public const PIPELINE = ['NEW', 'UNDER_REVIEW', 'VIDEO_REVIEWED', 'INTERVIEW', 'APPROVED', 'REJECTED'];

    protected $fillable = [
        'user_id', 'phone', 'city_country', 'linkedin_url', 'source', 'profile_photo_path',
        'is_18_plus', 'background_check_consent', 'child_safety_consent',
        'subject_verticals', 'preferred_age_bands', 'teaching_experience', 'countries_taught',
        'platforms_taught', 'higher_education', 'higher_institution', 'english_fluency',
        'about_yourself', 'additional_languages', 'availability_commitment', 'preferred_schedule',
        'start_date', 'has_laptop_camera', 'has_stable_internet', 'internet_speed_screenshot_path',
        'has_quiet_workspace', 'comfort_with_zoom', 'intro_video_path', 'certifications_paths',
        'currency', 'expected_hourly_rate', 'references', 'pipeline_status', 'reviewer_notes',
    ];

    protected function casts(): array
    {
        return [
            'subject_verticals' => 'array',
            'preferred_age_bands' => 'array',
            'preferred_schedule' => 'array',
            'certifications_paths' => 'array',
            'is_18_plus' => 'boolean',
            'background_check_consent' => 'boolean',
            'child_safety_consent' => 'boolean',
            'has_laptop_camera' => 'boolean',
            'has_stable_internet' => 'boolean',
            'has_quiet_workspace' => 'boolean',
            'comfort_with_zoom' => 'boolean',
            'start_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
