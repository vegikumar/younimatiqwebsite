<?php

namespace App\Services;

use App\Http\Controllers\UnsubscribeController;
use App\Jobs\SendCampaignEmailJob;
use App\Models\CampBooking;
use App\Models\Course;
use App\Models\EmailAutomation;
use App\Models\EmailAutomationRun;
use App\Models\EmailLog;
use App\Models\EmailSuppression;
use App\Models\Enrollment;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class EmailAutomationService
{
    public function getTrendingAutomation(): EmailAutomation
    {
        return EmailAutomation::trending();
    }

    public function resolveAutomation(?int $id = null): EmailAutomation
    {
        if ($id) {
            return EmailAutomation::query()->findOrFail($id);
        }

        return $this->getTrendingAutomation();
    }

    public function shouldRunNow(EmailAutomation $automation): bool
    {
        if (! $automation->is_enabled) {
            return false;
        }

        $tz = $automation->timezone ?: 'Asia/Kolkata';
        $now = Carbon::now($tz);
        $days = collect($automation->days_of_week ?? [])->map(fn ($d) => (int) $d)->all();

        if (! in_array((int) $now->dayOfWeekIso, $days, true)) {
            return false;
        }

        [$hour, $minute] = array_map('intval', explode(':', $automation->send_time ?: '10:00'));
        $windowStart = $now->copy()->setTime($hour, $minute, 0);
        $windowEnd = $windowStart->copy()->addMinutes(20);

        if ($now->lt($windowStart) || $now->gte($windowEnd)) {
            return false;
        }

        if ($automation->last_sent_at) {
            $last = $automation->last_sent_at->copy()->timezone($tz);
            if ($last->isSameDay($now)) {
                return false;
            }
        }

        return true;
    }

    public function nextScheduledAt(EmailAutomation $automation): ?Carbon
    {
        $days = collect($automation->days_of_week ?? [])->map(fn ($d) => (int) $d)->filter()->unique()->sort()->values();
        if ($days->isEmpty()) {
            return null;
        }

        $tz = $automation->timezone ?: 'Asia/Kolkata';
        [$hour, $minute] = array_map('intval', explode(':', $automation->send_time ?: '10:00'));
        $cursor = Carbon::now($tz)->startOfMinute();

        for ($i = 0; $i < 14; $i++) {
            $day = $cursor->copy()->addDays($i);
            if (! $days->contains((int) $day->dayOfWeekIso)) {
                continue;
            }
            $candidate = $day->copy()->setTime($hour, $minute, 0);
            if ($candidate->greaterThan($cursor)) {
                return $candidate;
            }
        }

        return null;
    }

    public function deliveryStats(): array
    {
        $automationSlugs = EmailAutomation::query()->pluck('slug')->filter()->all();
        $campaignSlugs = array_values(array_unique(array_merge(
            $automationSlugs,
            ['trending_classes', 'new_course', 'course_offer', 'custom_campaign']
        )));
        $base = EmailLog::query()->whereIn('template_slug', $campaignSlugs);

        return [
            'sent_7d' => (clone $base)->where('status', 'sent')->where('created_at', '>=', now()->subDays(7))->count(),
            'failed_7d' => (clone $base)->where('status', 'failed')->where('created_at', '>=', now()->subDays(7))->count(),
            'sent_30d' => (clone $base)->where('status', 'sent')->where('created_at', '>=', now()->subDays(30))->count(),
            'failed_30d' => (clone $base)->where('status', 'failed')->where('created_at', '>=', now()->subDays(30))->count(),
            'queued' => (clone $base)->where('status', 'queued')->count(),
            'suppressions' => EmailSuppression::count(),
        ];
    }

    /**
     * @return array{run: EmailAutomationRun, queued: int, recipients: int}
     */
    public function dispatch(EmailAutomation $automation, string $trigger = 'schedule', ?string $testEmail = null): array
    {
        $courses = $this->resolveCourses($automation);

        if ($courses->isEmpty()) {
            throw new \RuntimeException('No published courses available for this campaign.');
        }

        $recipients = $testEmail
            ? collect([['email' => strtolower(trim($testEmail)), 'name' => 'there']])
            : $this->resolveRecipients($automation);

        if ($recipients->isEmpty()) {
            throw new \RuntimeException('No recipients matched the selected audiences (after suppressions/exclusions).');
        }

        $run = EmailAutomationRun::create([
            'email_automation_id' => $automation->id,
            'trigger' => $trigger,
            'status' => 'queued',
            'recipient_count' => $recipients->count(),
            'course_snapshot' => $courses->map(fn ($c) => [
                'id' => $c['id'],
                'title' => $c['title'],
                'url' => $c['url'],
            ])->values()->all(),
        ]);

        $queued = 0;
        foreach ($recipients as $recipient) {
            $html = $this->renderTrendingHtml(
                $automation,
                $courses,
                $recipient['name'],
                $recipient['email']
            );

            SendCampaignEmailJob::dispatch(
                $recipient['email'],
                $automation->subject,
                $html,
                $automation->from_name ?: 'Amy from Younimatiq',
                $automation->reply_to ?: 'support@younimatiq.com',
                $automation->slug ?: 'trending_classes',
                UnsubscribeController::signedUrl($recipient['email'])
            );
            $queued++;
        }

        $run->update([
            'status' => 'completed',
            'sent_count' => $queued,
        ]);

        if ($trigger !== 'test') {
            $automation->update([
                'last_sent_at' => now(),
                'last_sent_count' => $queued,
            ]);

            if ($automation->notify_admin_on_send) {
                try {
                    app(EmailService::class)->notifyAdmins(
                        'Trending digest queued ('.$trigger.')',
                        'Queued <strong>'.$queued.'</strong> trending-class emails.'
                    );
                } catch (\Throwable $e) {
                    Log::warning('Campaign admin notify failed: '.$e->getMessage());
                }
            }
        }

        return [
            'run' => $run->fresh(),
            'queued' => $queued,
            'recipients' => $recipients->count(),
        ];
    }

    public function previewHtml(EmailAutomation $automation, string $recipientName = 'there', string $recipientEmail = 'preview@younimatiq.com'): string
    {
        $courses = $this->resolveCourses($automation);

        return $this->renderTrendingHtml($automation, $courses, $recipientName, $recipientEmail);
    }

    public function recipientCount(EmailAutomation $automation): int
    {
        return $this->resolveRecipients($automation)->count();
    }

    public function resolveCourses(EmailAutomation $automation): Collection
    {
        $limit = max(1, min(12, (int) ($automation->max_courses ?: 4)));
        $query = Course::query()
            ->published()
            ->where('is_active', true)
            ->with('ageGroups');

        if ($automation->exclude_camps ?? true) {
            $query->where(function ($q) {
                $q->where('is_camp', false)->orWhereNull('is_camp');
            });
        }

        $source = $automation->course_source ?: 'top';

        if ($source === 'manual' && ! empty($automation->course_ids)) {
            $ids = collect($automation->course_ids)->map(fn ($id) => (int) $id)->filter()->values();
            $courses = $query->whereIn('id', $ids)->get()
                ->sortBy(fn ($course) => $ids->search($course->id))
                ->take($limit)
                ->values();
        } elseif ($source === 'recent') {
            $courses = $query->orderByDesc('published_at')->orderByDesc('id')->limit($limit)->get();
        } else {
            $courses = (clone $query)->where('is_top_course', true)
                ->orderByDesc('published_at')
                ->limit($limit)
                ->get();

            if ($courses->count() < $limit) {
                $extra = $query->whereNotIn('id', $courses->pluck('id'))
                    ->orderByDesc('published_at')
                    ->limit($limit - $courses->count())
                    ->get();
                $courses = $courses->concat($extra)->values();
            }
        }

        $utmCampaign = $automation->utm_campaign ?: 'trending_digest';

        return $courses->map(function (Course $course) use ($utmCampaign) {
            $url = route('courses.show', $course);

            return [
                'id' => $course->id,
                'title' => $course->title,
                'url' => $this->withUtm($url, $utmCampaign, 'course_link'),
                'age_label' => $this->ageLabel($course),
            ];
        });
    }

    public function resolveRecipients(EmailAutomation $automation): Collection
    {
        $audiences = collect($automation->audiences ?? [])->filter()->values();
        $map = collect();
        $blocked = $this->blockedEmails($automation);

        if ($audiences->contains('students')) {
            User::query()
                ->where('role', 'student')
                ->where('status', 'active')
                ->whereNotNull('email')
                ->orderBy('id')
                ->chunkById(200, function ($users) use (&$map, $blocked) {
                    foreach ($users as $user) {
                        if (! $user->wantsEmail('email_marketing')) {
                            continue;
                        }
                        $this->putRecipient($map, $user->email, $user->name, $blocked);
                    }
                });
        }

        if ($audiences->contains('enrolled')) {
            $userIds = Enrollment::query()
                ->where('status', 'active')
                ->distinct()
                ->pluck('user_id');

            User::query()
                ->whereIn('id', $userIds)
                ->where('status', 'active')
                ->whereNotNull('email')
                ->orderBy('id')
                ->chunkById(200, function ($users) use (&$map, $blocked) {
                    foreach ($users as $user) {
                        if (! $user->wantsEmail('email_marketing')) {
                            continue;
                        }
                        $this->putRecipient($map, $user->email, $user->name, $blocked);
                    }
                });
        }

        if ($audiences->contains('newsletter')) {
            NewsletterSubscriber::query()
                ->whereNotNull('email')
                ->orderBy('id')
                ->chunkById(200, function ($rows) use (&$map, $blocked) {
                    foreach ($rows as $row) {
                        $this->putRecipient($map, $row->email, $row->name ?: 'there', $blocked);
                    }
                });
        }

        if ($audiences->contains('camp_bookings')) {
            CampBooking::query()
                ->whereNotNull('email')
                ->select('id', 'email', 'parent_name')
                ->orderBy('id')
                ->chunkById(200, function ($rows) use (&$map, $blocked) {
                    foreach ($rows as $row) {
                        $this->putRecipient($map, $row->email, $row->parent_name ?: 'there', $blocked);
                    }
                });
        }

        // Always-include custom emails (still subject to exclude + suppressions).
        foreach ($automation->includedEmailList() as $email) {
            $this->putRecipient($map, $email, 'there', $blocked);
        }

        return $map->values();
    }

    private function blockedEmails(EmailAutomation $automation): array
    {
        $blocked = collect($automation->excludedEmailList());

        EmailSuppression::query()->orderBy('id')->chunkById(500, function ($rows) use (&$blocked) {
            foreach ($rows as $row) {
                $blocked->push(strtolower(trim($row->email)));
            }
        });

        $skipDays = max(0, (int) ($automation->skip_recent_days ?? 0));
        if ($skipDays > 0) {
            EmailLog::query()
                ->whereIn('template_slug', ['trending_classes', 'new_course', 'course_offer', 'custom_campaign'])
                ->where('status', 'sent')
                ->where('created_at', '>=', now()->subDays($skipDays))
                ->orderBy('id')
                ->chunkById(500, function ($rows) use (&$blocked) {
                    foreach ($rows as $row) {
                        $blocked->push(strtolower(trim($row->to_email)));
                    }
                });
        }

        return $blocked->filter()->unique()->values()->all();
    }

    private function putRecipient(Collection &$map, ?string $email, ?string $name, array $blocked = []): void
    {
        $email = strtolower(trim((string) $email));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        if (in_array($email, $blocked, true) || $map->has($email)) {
            return;
        }

        $name = trim((string) $name);
        $map->put($email, [
            'email' => $email,
            'name' => $name !== '' ? $name : 'there',
        ]);
    }

    private function ageLabel(Course $course): string
    {
        if ($course->relationLoaded('ageGroups') && $course->ageGroups->isNotEmpty()) {
            $ranges = $course->ageGroups->pluck('age_range')->filter()->values();
            if ($ranges->isNotEmpty()) {
                return $ranges->implode(', ');
            }

            return 'Ages '.$course->ageGroups->pluck('name')->filter()->implode(', ');
        }

        if ($course->grade_min || $course->grade_max) {
            $min = $course->grade_min ?? $course->grade_max;
            $max = $course->grade_max ?? $course->grade_min;

            return 'Grades '.$min.' to '.$max;
        }

        if ($course->age_group) {
            $label = trim((string) $course->age_group);
            if (stripos($label, 'age') === false) {
                return 'Ages '.$label;
            }

            return $label;
        }

        return 'All ages';
    }

    private function withUtm(string $url, string $campaign, string $content): string
    {
        $parts = parse_url($url);
        $query = [];
        if (! empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        $query['utm_source'] = 'email';
        $query['utm_medium'] = 'automation';
        $query['utm_campaign'] = $campaign;
        $query['utm_content'] = $content;

        $rebuilt = ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '');
        if (! empty($parts['port'])) {
            $rebuilt .= ':'.$parts['port'];
        }
        $rebuilt .= $parts['path'] ?? '';
        $rebuilt .= '?'.http_build_query($query);
        if (! empty($parts['fragment'])) {
            $rebuilt .= '#'.$parts['fragment'];
        }

        return $rebuilt;
    }

    private function renderTrendingHtml(
        EmailAutomation $automation,
        Collection $courses,
        string $recipientName,
        string $recipientEmail
    ): string {
        $siteUrl = rtrim((string) config('app.url'), '/');
        $utmCampaign = $automation->utm_campaign ?: 'trending_digest';
        $ctaBase = $automation->cta_url ?: ($siteUrl.'/courses');

        $heading = $automation->heading ?: 'Trending this week';
        $introText = $automation->intro_text ?: 'The classes families are booking most right now:';
        $ctaText = $automation->cta_text ?: 'See all classes';
        $ctaUrl = $this->withUtm($ctaBase, $utmCampaign, 'cta_button');
        $signOffName = $automation->sign_off_name ?: 'Amy from Younimatiq';
        $unsubscribeUrl = UnsubscribeController::signedUrl($recipientEmail);
        $logoUrl = asset('logo.webp');

        $themeHtml = $automation->theme_html;
        if (! $themeHtml && $automation->theme_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($automation->theme_path)) {
            $themeHtml = \Illuminate\Support\Facades\Storage::disk('public')->get($automation->theme_path);
        }

        if ($themeHtml) {
            $coursesBlock = '';
            foreach ($courses as $course) {
                $title = e($course->title ?? 'Course');
                $url = e($this->withUtm($siteUrl.'/courses/'.($course->slug ?? $course->id), $utmCampaign, 'course_card'));
                $coursesBlock .= '<div style="margin:12px 0;padding:12px;border:1px solid #eee;border-radius:8px;"><a href="'.$url.'" style="font-weight:bold;color:#0057bf;">'.$title.'</a></div>';
            }

            return strtr($themeHtml, [
                '{{subject}}' => e($automation->subject),
                '{{heading}}' => e($heading),
                '{{intro_text}}' => e($introText),
                '{{body}}' => e($introText),
                '{{cta_text}}' => e($ctaText),
                '{{cta_url}}' => e($ctaUrl),
                '{{sign_off_name}}' => e($signOffName),
                '{{recipient_name}}' => e($recipientName ?: 'there'),
                '{{unsubscribe_url}}' => e($unsubscribeUrl),
                '{{logo_url}}' => e($logoUrl),
                '{{site_url}}' => e($siteUrl),
                '{{courses_block}}' => $coursesBlock,
            ]);
        }

        return view('emails.campaigns.trending', [
            'subject' => $automation->subject,
            'heading' => $heading,
            'introText' => $introText,
            'ctaText' => $ctaText,
            'ctaUrl' => $ctaUrl,
            'signOffName' => $signOffName,
            'supportEmail' => 'support@younimatiq.com',
            'siteUrl' => $siteUrl,
            'logoUrl' => $logoUrl,
            'unsubscribeUrl' => $unsubscribeUrl,
            'recipientName' => $recipientName ?: 'there',
            'courses' => $courses,
        ])->render();
    }

    /**
     * Manual trigger for a single course announcement or offer email.
     *
     * @param  array{
     *   subject?: string,
     *   heading?: string,
     *   intro_text?: string,
     *   cta_text?: string,
     *   original_price?: float|string|null,
     *   offer_price?: float|string|null,
     *   offer_note?: string|null,
     *   test_email?: string|null
     * }  $options
     * @return array{run: EmailAutomationRun, queued: int, recipients: int}
     */
    public function dispatchCourseTrigger(Course $course, string $type, array $options = []): array
    {
        if (! in_array($type, ['new_course', 'offer'], true)) {
            throw new \InvalidArgumentException('Unsupported campaign type.');
        }

        $automation = $this->resolveAutomation(
            isset($options['email_automation_id']) ? (int) $options['email_automation_id'] : null
        );
        $testEmail = isset($options['test_email']) ? trim((string) $options['test_email']) : '';

        $recipients = $testEmail !== ''
            ? collect([['email' => strtolower($testEmail), 'name' => 'there']])
            : $this->resolveRecipients($automation);

        if ($recipients->isEmpty()) {
            throw new \RuntimeException('No recipients matched the selected audiences (after suppressions/exclusions).');
        }

        $payload = $this->buildCourseTriggerPayload($course, $type, $options, $automation);
        $templateSlug = $type === 'offer' ? 'course_offer' : 'new_course';
        $trigger = $testEmail !== '' ? 'test_'.$type : 'manual_'.$type;

        $run = EmailAutomationRun::create([
            'email_automation_id' => $automation->id,
            'trigger' => $trigger,
            'status' => 'queued',
            'recipient_count' => $recipients->count(),
            'course_snapshot' => [[
                'id' => $course->id,
                'title' => $course->title,
                'url' => $payload['course']['url'],
                'type' => $type,
                'offer' => $payload['offer'] ?? null,
            ]],
        ]);

        $queued = 0;
        foreach ($recipients as $recipient) {
            $html = $this->renderCourseTriggerHtml(
                $payload,
                $recipient['name'],
                $recipient['email']
            );

            SendCampaignEmailJob::dispatch(
                $recipient['email'],
                $payload['subject'],
                $html,
                $automation->from_name ?: 'Amy from Younimatiq',
                $automation->reply_to ?: 'support@younimatiq.com',
                $templateSlug,
                UnsubscribeController::signedUrl($recipient['email'])
            );
            $queued++;
        }

        $run->update([
            'status' => 'completed',
            'sent_count' => $queued,
        ]);

        if ($testEmail === '' && ($automation->notify_admin_on_send ?? true)) {
            try {
                $label = $type === 'offer' ? 'Offer' : 'New course';
                app(EmailService::class)->notifyAdmins(
                    $label.' email queued',
                    'Queued <strong>'.$queued.'</strong> emails for <em>'.e($course->title).'</em>.'
                );
            } catch (\Throwable $e) {
                Log::warning('Course trigger admin notify failed: '.$e->getMessage());
            }
        }

        return [
            'run' => $run->fresh(),
            'queued' => $queued,
            'recipients' => $recipients->count(),
        ];
    }

    public function previewCourseTrigger(Course $course, string $type, array $options = []): string
    {
        $automation = $this->resolveAutomation(
            isset($options['email_automation_id']) ? (int) $options['email_automation_id'] : null
        );
        $payload = $this->buildCourseTriggerPayload($course, $type, $options, $automation);

        return $this->renderCourseTriggerHtml($payload, 'Parent', 'preview@younimatiq.com');
    }

    /**
     * One-off custom campaign (subject + HTML/text body) to automation audiences + includes.
     *
     * @param  array{
     *   subject: string,
     *   body: string,
     *   test_email?: string|null
     * }  $options
     * @return array{run: EmailAutomationRun, queued: int, recipients: int}
     */
    public function dispatchCustomCampaign(EmailAutomation $automation, array $options): array
    {
        $subject = trim((string) ($options['subject'] ?? ''));
        $body = trim((string) ($options['body'] ?? ''));
        $testEmail = isset($options['test_email']) ? trim((string) $options['test_email']) : '';

        if ($subject === '' || $body === '') {
            throw new \RuntimeException('Subject and body are required for a custom email.');
        }

        $recipients = $testEmail !== ''
            ? collect([['email' => strtolower($testEmail), 'name' => 'there']])
            : $this->resolveRecipients($automation);

        if ($recipients->isEmpty()) {
            throw new \RuntimeException('No recipients matched (after suppressions/exclusions). Add audiences or include emails.');
        }

        $bodyHtml = $this->normalizeCustomBodyHtml($body);
        $trigger = $testEmail !== '' ? 'test_custom' : 'manual_custom';

        $run = EmailAutomationRun::create([
            'email_automation_id' => $automation->id,
            'trigger' => $trigger,
            'status' => 'queued',
            'recipient_count' => $recipients->count(),
            'course_snapshot' => [
                'custom' => true,
                'subject' => $subject,
            ],
        ]);

        $queued = 0;
        foreach ($recipients as $recipient) {
            $html = $this->renderCustomCampaignHtml(
                $automation,
                $subject,
                $bodyHtml,
                $recipient['name'],
                $recipient['email']
            );

            SendCampaignEmailJob::dispatch(
                $recipient['email'],
                $subject,
                $html,
                $automation->from_name ?: 'Amy from Younimatiq',
                $automation->reply_to ?: 'support@younimatiq.com',
                'custom_campaign',
                UnsubscribeController::signedUrl($recipient['email'])
            );
            $queued++;
        }

        $run->update([
            'status' => 'completed',
            'sent_count' => $queued,
        ]);

        if ($testEmail === '' && ($automation->notify_admin_on_send ?? true)) {
            try {
                app(EmailService::class)->notifyAdmins(
                    'Custom campaign queued',
                    'Queued <strong>'.$queued.'</strong> custom emails: <em>'.e($subject).'</em>.'
                );
            } catch (\Throwable $e) {
                Log::warning('Custom campaign admin notify failed: '.$e->getMessage());
            }
        }

        return [
            'run' => $run->fresh(),
            'queued' => $queued,
            'recipients' => $recipients->count(),
        ];
    }

    public function previewCustomCampaign(
        EmailAutomation $automation,
        string $subject,
        string $body,
        string $recipientName = 'Parent',
        string $recipientEmail = 'preview@younimatiq.com'
    ): string {
        return $this->renderCustomCampaignHtml(
            $automation,
            $subject !== '' ? $subject : 'Custom email preview',
            $this->normalizeCustomBodyHtml($body !== '' ? $body : '<p>Your message will appear here.</p>'),
            $recipientName,
            $recipientEmail
        );
    }

    private function normalizeCustomBodyHtml(string $body): string
    {
        $body = trim($body);
        if ($body === '') {
            return '';
        }

        // Plain text → simple paragraphs; HTML from admin is allowed as-is.
        if (! preg_match('/<\s*[a-z][\s\S]*>/i', $body)) {
            $escaped = e($body);

            return '<p style="margin:0 0 12px;">'.nl2br($escaped, false).'</p>';
        }

        return $body;
    }

    private function renderCustomCampaignHtml(
        EmailAutomation $automation,
        string $subject,
        string $bodyHtml,
        string $recipientName,
        string $recipientEmail
    ): string {
        $siteUrl = rtrim((string) config('app.url'), '/');

        return view('emails.campaigns.custom', [
            'subject' => $subject,
            'bodyHtml' => $bodyHtml,
            'recipientName' => $recipientName ?: 'there',
            'signOffName' => $automation->sign_off_name ?: 'Amy from Younimatiq',
            'supportEmail' => $automation->reply_to ?: 'support@younimatiq.com',
            'siteUrl' => $siteUrl,
            'logoUrl' => asset('logo.webp'),
            'unsubscribeUrl' => UnsubscribeController::signedUrl($recipientEmail),
        ])->render();
    }

    private function buildCourseTriggerPayload(
        Course $course,
        string $type,
        array $options,
        EmailAutomation $automation
    ): array {
        $course->loadMissing('ageGroups');
        $siteUrl = rtrim((string) config('app.url'), '/');
        $utm = $type === 'offer' ? 'course_offer' : 'new_course';
        $url = $this->withUtm(route('courses.show', $course), $utm, 'course_link');
        $priceUsd = (float) ($course->price_usd ?? $course->per_class_price ?? 0);

        $blurb = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($course->description ?? ''))));
        $blurb = \Illuminate\Support\Str::limit($blurb, 220);

        $thumbnail = $course->thumbnail_url
            ? (str_starts_with($course->thumbnail_url, 'http') ? $course->thumbnail_url : url($course->thumbnail_url))
            : null;

        $courseData = [
            'id' => $course->id,
            'title' => $course->title,
            'url' => $url,
            'age_label' => $this->ageLabel($course),
            'price_label' => $priceUsd > 0 ? '$'.number_format($priceUsd, 2) : null,
            'blurb' => $blurb,
            'thumbnail' => $thumbnail,
        ];

        $offer = null;
        if ($type === 'offer') {
            $offerPrice = (float) ($options['offer_price'] ?? 0);
            if ($offerPrice <= 0) {
                throw new \RuntimeException('Offer price is required for offer emails.');
            }
            $original = isset($options['original_price']) && $options['original_price'] !== '' && $options['original_price'] !== null
                ? (float) $options['original_price']
                : $priceUsd;

            $offer = [
                'original_price_label' => $original > 0 ? '$'.number_format($original, 2).'/class' : null,
                'offer_price_label' => '$'.number_format($offerPrice, 2).'/class',
                'note' => trim((string) ($options['offer_note'] ?? '')),
            ];
        }

        if ($type === 'offer') {
            $subject = trim((string) ($options['subject'] ?? '')) ?: ('Special offer: '.$course->title);
            $heading = trim((string) ($options['heading'] ?? '')) ?: 'A special offer for your family';
            $intro = trim((string) ($options['intro_text'] ?? ''))
                ?: 'For a limited time, this class is available at a special price:';
            $ctaText = trim((string) ($options['cta_text'] ?? '')) ?: 'Claim this offer';
            $accent = '#dc2626';
        } else {
            $subject = trim((string) ($options['subject'] ?? '')) ?: ('New class: '.$course->title);
            $heading = trim((string) ($options['heading'] ?? '')) ?: 'New class just launched';
            $intro = trim((string) ($options['intro_text'] ?? ''))
                ?: 'We just added a new class families are going to love:';
            $ctaText = trim((string) ($options['cta_text'] ?? '')) ?: 'View this class';
            $accent = '#2563eb';
        }

        return [
            'type' => $type,
            'subject' => $subject,
            'heading' => $heading,
            'introText' => $intro,
            'ctaText' => $ctaText,
            'ctaUrl' => $this->withUtm($url, $utm, 'cta_button'),
            'accentColor' => $accent,
            'signOffName' => $automation->sign_off_name ?: 'Amy from Younimatiq',
            'course' => $courseData,
            'offer' => $offer,
            'siteUrl' => $siteUrl,
            'logoUrl' => asset('logo.webp'),
            'supportEmail' => 'support@younimatiq.com',
        ];
    }

    private function renderCourseTriggerHtml(array $payload, string $recipientName, string $recipientEmail): string
    {
        return view('emails.campaigns.course-trigger', array_merge($payload, [
            'recipientName' => $recipientName ?: 'there',
            'unsubscribeUrl' => UnsubscribeController::signedUrl($recipientEmail),
        ]))->render();
    }

    public function runScheduled(): int
    {
        $totalQueued = 0;

        EmailAutomation::query()
            ->where('is_enabled', true)
            ->orderBy('id')
            ->each(function (EmailAutomation $automation) use (&$totalQueued) {
                if (! $this->shouldRunNow($automation)) {
                    return;
                }

                try {
                    $result = $this->dispatch($automation, 'schedule');
                    $totalQueued += $result['queued'];
                    Log::info('Email automation queued', [
                        'automation_id' => $automation->id,
                        'slug' => $automation->slug,
                        'queued' => $result['queued'],
                        'run_id' => $result['run']->id,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Email automation failed: '.$e->getMessage(), [
                        'automation_id' => $automation->id,
                        'slug' => $automation->slug,
                    ]);
                    EmailAutomationRun::create([
                        'email_automation_id' => $automation->id,
                        'trigger' => 'schedule',
                        'status' => 'failed',
                        'error_message' => $e->getMessage(),
                    ]);
                }
            });

        return $totalQueued;
    }
}

