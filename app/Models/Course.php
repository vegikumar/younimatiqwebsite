<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

class Course extends Model
{
    protected $fillable = ['tutor_id', 'title', 'slug', 'description', 'importance', 'learning_outcomes', 'highlights', 'price', 'price_usd', 'price_aed', 'price_inr', 'per_class_price', 'thumbnail_url', 'video_url', '3d_asset_path', 'seo_metadata', 'course_type', 'self_paced_enabled', 'format', 'number_of_classes', 'welcome_message', 'welcome_video_url', 'status', 'published_at', 'age_group', 'is_camp', 'is_active', 'is_top_course', 'grade_min', 'grade_max'];

    protected static function booted(): void
    {
        static::saved(fn () => \App\Support\NavigationData::forget());
        static::deleted(fn () => \App\Support\NavigationData::forget());
    }

    protected function casts(): array
    {
        return [
            'seo_metadata' => 'array',
            'learning_outcomes' => 'array',
            'highlights' => 'array',
            'is_camp' => 'boolean',
            'is_active' => 'boolean',
            'is_top_course' => 'boolean',
            'self_paced_enabled' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    /**
     * Public catalog visibility: published, active, and tied to at least one active category.
     * Turning a category off removes its courses from the website until another active category remains.
     */
    public function scopeVisibleOnSite($query)
    {
        return $query->where('status', 'published')
            ->where('is_active', true)
            ->whereHas('categories', fn ($q) => $q->where('categories.is_active', true));
    }

    public function units()
    {
        return $this->hasMany(CourseUnit::class)->orderBy('order');
    }

    public function ageGroups()
    {
        return $this->belongsToMany(AgeGroup::class, 'course_age_group', 'course_id', 'age_group_id')
            ->withTimestamps();
    }

    public function modules()
    {
        return $this->hasMany(CourseModule::class)->orderBy('order');
    }

    /**
     * Delivery modes that can be purchased at checkout. Self-paced only becomes
     * selectable when the course is explicitly enabled for it.
     */
    public function availableDeliveryModes(): array
    {
        $modes = [$this->course_type ?: 'live'];

        if ($this->self_paced_enabled) {
            $other = $this->course_type === 'self-paced' ? 'live' : 'self-paced';
            if (! in_array($other, $modes, true)) {
                $modes[] = $other;
            }
        }

        return array_values(array_filter($modes));
    }

    public function availabilities()
    {
        return $this->hasMany(TutorAvailability::class);
    }

    public function announcements()
    {
        return $this->hasMany(Announcement::class)->latest();
    }

    public function discussions()
    {
        return $this->hasMany(ClassroomDiscussion::class)->whereNull('parent_id')->latest();
    }

    public function directConversations()
    {
        return $this->hasMany(DirectConversation::class);
    }

    public function recordings()
    {
        return $this->hasMany(ClassRecording::class)->latest();
    }

    public function tutor()
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'course_skill_requirements')->withTimestamps();
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function studentRewards()
    {
        return $this->hasMany(StudentReward::class);
    }

    public function badges()
    {
        return $this->hasMany(MilestoneBadge::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class)->latest();
    }

    public function averageRating(): float
    {
        return round((float) $this->reviews()->avg('rating'), 1);
    }

    public function isEnrolled(?int $userId): bool
    {
        if (!$userId) {
            return false;
        }

        return Enrollment::where('user_id', $userId)
            ->where('course_id', $this->id)
            ->whereIn('status', ['active', 'completed'])
            ->exists();
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Allow API/mobile clients to bind by numeric id or slug.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $field ??= $this->getRouteKeyName();

        return $this->newQuery()
            ->where(function ($q) use ($value, $field) {
                $q->where($field, $value)->orWhere($this->getKeyName(), $value);
            })
            ->first();
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($course) {
            if (empty($course->slug)) {
                $course->slug = \Illuminate\Support\Str::slug($course->title);
            }
        });
    }
}
