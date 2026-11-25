<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class EmailAutomation extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'is_enabled',
        'subject',
        'theme_html',
        'theme_path',
        'heading',
        'intro_text',
        'cta_text',
        'cta_url',
        'from_name',
        'sign_off_name',
        'reply_to',
        'days_of_week',
        'send_time',
        'timezone',
        'audiences',
        'exclude_emails',
        'include_emails',
        'course_source',
        'course_ids',
        'max_courses',
        'exclude_camps',
        'skip_recent_days',
        'utm_campaign',
        'notify_admin_on_send',
        'last_sent_at',
        'last_sent_count',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'days_of_week' => 'array',
            'audiences' => 'array',
            'course_ids' => 'array',
            'exclude_camps' => 'boolean',
            'notify_admin_on_send' => 'boolean',
            'last_sent_at' => 'datetime',
            'max_courses' => 'integer',
            'skip_recent_days' => 'integer',
            'last_sent_count' => 'integer',
        ];
    }

    public function runs(): HasMany
    {
        return $this->hasMany(EmailAutomationRun::class);
    }

    public function excludedEmailList(): array
    {
        return $this->parseEmailList($this->exclude_emails);
    }

    public function includedEmailList(): array
    {
        return $this->parseEmailList($this->include_emails);
    }

    protected function parseEmailList(?string $raw): array
    {
        return collect(preg_split('/[\s,;]+/', (string) $raw) ?: [])
            ->map(fn ($email) => strtolower(trim($email)))
            ->filter(fn ($email) => $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }

    public static function defaultAttributes(string $name = 'New email campaign'): array
    {
        return [
            'name' => $name,
            'is_enabled' => false,
            'subject' => 'Trending classes this week',
            'heading' => 'Trending this week',
            'intro_text' => 'The classes families are booking most right now:',
            'cta_text' => 'See all classes',
            'from_name' => 'Amy from Younimatiq',
            'sign_off_name' => 'Amy from Younimatiq',
            'reply_to' => 'support@younimatiq.com',
            'days_of_week' => [1, 3, 5],
            'send_time' => '10:00',
            'timezone' => 'Asia/Kolkata',
            'audiences' => ['students', 'newsletter'],
            'course_source' => 'top',
            'max_courses' => 4,
            'exclude_camps' => true,
            'skip_recent_days' => 2,
            'utm_campaign' => 'email_campaign',
            'notify_admin_on_send' => true,
        ];
    }

    public static function makeUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'email-campaign';
        $slug = $base;
        $i = 2;

        while (
            static::query()
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    public static function trending(): self
    {
        return static::firstOrCreate(
            ['slug' => 'trending_classes'],
            array_merge(static::defaultAttributes('Trending Classes Digest'), [
                'utm_campaign' => 'trending_digest',
            ])
        );
    }
}
