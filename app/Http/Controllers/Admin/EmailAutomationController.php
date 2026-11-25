<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\EmailAutomation;
use App\Models\EmailAutomationRun;
use App\Models\EmailLog;
use App\Models\EmailSuppression;
use App\Services\EmailAutomationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EmailAutomationController extends Controller
{
    private function dayLabels(): array
    {
        return [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ];
    }

    public function index(EmailAutomationService $service)
    {
        EmailAutomation::trending();

        $automations = EmailAutomation::query()
            ->orderByDesc('is_enabled')
            ->orderBy('name')
            ->get();

        $stats = $service->deliveryStats();
        $recentRuns = EmailAutomationRun::query()
            ->with('automation:id,name,slug')
            ->latest()
            ->limit(20)
            ->get();
        $recentLogs = EmailLog::query()
            ->whereIn('template_slug', array_merge(
                $automations->pluck('slug')->all(),
                ['trending_classes', 'new_course', 'course_offer', 'custom_campaign']
            ))
            ->latest()
            ->limit(25)
            ->get();
        $suppressions = EmailSuppression::query()->latest('suppressed_at')->limit(50)->get();

        $audienceCounts = $automations->mapWithKeys(function (EmailAutomation $automation) use ($service) {
            return [$automation->id => $service->recipientCount($automation)];
        });

        $nextSendAts = $automations->mapWithKeys(function (EmailAutomation $automation) use ($service) {
            return [$automation->id => $service->nextScheduledAt($automation)];
        });

        return \App\Support\InertiaPage::react('Admin/EmailAutomations/Index', [
            'automations' => \App\Support\AdminInertiaProps::emailAutomationSummaries(
                $automations,
                $audienceCounts->all(),
                $nextSendAts->all(),
                $this->dayLabels()
            ),
            'stats' => $stats,
            'recentRuns' => \App\Support\AdminInertiaProps::emailAutomationRuns($recentRuns),
            'recentLogs' => \App\Support\AdminInertiaProps::emailLogs($recentLogs),
            'suppressions' => \App\Support\AdminInertiaProps::emailSuppressions($suppressions),
            'routes' => [
                'create' => route('admin.email-automations.create'),
                'suppressionsStore' => route('admin.email-automations.suppressions.store'),
            ],
        ]);
    }

    public function create()
    {
        return \App\Support\InertiaPage::react('Admin/EmailAutomations/Create', [
            'defaults' => EmailAutomation::defaultAttributes(),
            'routes' => [
                'index' => route('admin.email-automations.index'),
                'store' => route('admin.email-automations.store'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'subject' => ['nullable', 'string', 'max:180'],
        ]);

        $name = trim($data['name']);
        $attrs = EmailAutomation::defaultAttributes($name);
        $attrs['slug'] = EmailAutomation::makeUniqueSlug($name);
        if (! empty($data['subject'])) {
            $attrs['subject'] = $data['subject'];
        }

        $automation = EmailAutomation::create($attrs);

        return redirect()
            ->route('admin.email-automations.edit', $automation)
            ->with('success', 'Email automation created. Configure audiences and schedule, then save.');
    }

    public function edit(EmailAutomation $emailAutomation, EmailAutomationService $service)
    {
        $courses = Course::query()
            ->published()
            ->where('is_active', true)
            ->orderBy('title')
            ->get(['id', 'title', 'is_top_course', 'is_camp', 'age_group', 'grade_min', 'grade_max']);

        $recipientCount = $service->recipientCount($emailAutomation);
        $previewCourses = $service->resolveCourses($emailAutomation);
        $nextSendAt = $service->nextScheduledAt($emailAutomation);
        $recentRuns = EmailAutomationRun::query()
            ->where('email_automation_id', $emailAutomation->id)
            ->latest()
            ->limit(15)
            ->get();

        return \App\Support\InertiaPage::react('Admin/EmailAutomations/Edit', [
            'automation' => \App\Support\AdminInertiaProps::emailAutomationForEdit($emailAutomation),
            'courses' => $courses->map(fn ($c) => [
                'id' => $c->id,
                'title' => $c->title,
                'is_top_course' => (bool) $c->is_top_course,
            ])->values()->all(),
            'recipientCount' => $recipientCount,
            'previewCourses' => $previewCourses,
            'nextSendLabel' => $nextSendAt
                ? $nextSendAt->timezone($emailAutomation->timezone ?: 'Asia/Kolkata')->format('D, M j · g:i A T')
                : null,
            'recentRuns' => \App\Support\AdminInertiaProps::emailAutomationRuns($recentRuns),
            'dayLabels' => $this->dayLabels(),
            'timezoneOptions' => ['Asia/Kolkata', 'America/New_York', 'America/Los_Angeles', 'Europe/London', 'Australia/Sydney', 'UTC'],
            'routes' => [
                'index' => route('admin.email-automations.index'),
            ],
        ]);
    }

    public function update(Request $request, EmailAutomation $emailAutomation)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'is_enabled' => ['nullable', 'boolean'],
            'subject' => ['required', 'string', 'max:180'],
            'heading' => ['required', 'string', 'max:120'],
            'intro_text' => ['required', 'string', 'max:1000'],
            'cta_text' => ['required', 'string', 'max:80'],
            'cta_url' => ['nullable', 'url', 'max:500'],
            'from_name' => ['required', 'string', 'max:120'],
            'sign_off_name' => ['required', 'string', 'max:120'],
            'reply_to' => ['nullable', 'email', 'max:190'],
            'days_of_week' => ['nullable', 'array'],
            'days_of_week.*' => ['integer', 'between:1,7'],
            'send_time' => ['required', 'date_format:H:i'],
            'timezone' => ['required', 'timezone'],
            'audiences' => ['nullable', 'array'],
            'audiences.*' => ['in:students,enrolled,newsletter,camp_bookings'],
            'exclude_emails' => ['nullable', 'string', 'max:10000'],
            'include_emails' => ['nullable', 'string', 'max:10000'],
            'course_source' => ['required', 'in:top,recent,manual'],
            'course_ids' => ['nullable', 'array'],
            'course_ids.*' => ['integer', 'exists:courses,id'],
            'max_courses' => ['required', 'integer', 'min:1', 'max:12'],
            'exclude_camps' => ['nullable', 'boolean'],
            'skip_recent_days' => ['required', 'integer', 'min:0', 'max:30'],
            'utm_campaign' => ['nullable', 'string', 'max:80'],
            'notify_admin_on_send' => ['nullable', 'boolean'],
            'theme_html' => ['nullable', 'string', 'max:500000'],
            'theme_file' => ['nullable', 'file', 'mimes:html,htm,txt', 'max:1024'],
            'clear_theme' => ['nullable', 'boolean'],
        ]);

        $themeHtml = $data['theme_html'] ?? $emailAutomation->theme_html;
        $themePath = $emailAutomation->theme_path;

        if ($request->boolean('clear_theme')) {
            $themeHtml = null;
            $themePath = null;
        }

        if ($request->hasFile('theme_file')) {
            $themePath = $request->file('theme_file')->store('email-themes', 'public');
            $themeHtml = $request->file('theme_file')->get();
        } elseif (! empty($data['theme_html'])) {
            $themeHtml = $data['theme_html'];
        }

        $name = trim($data['name']);
        $slug = $emailAutomation->slug;
        if ($name !== $emailAutomation->name) {
            $slug = EmailAutomation::makeUniqueSlug($name, $emailAutomation->id);
        }

        $emailAutomation->update([
            'name' => $name,
            'slug' => $slug,
            'is_enabled' => $request->boolean('is_enabled'),
            'subject' => $data['subject'],
            'heading' => $data['heading'],
            'intro_text' => $data['intro_text'],
            'cta_text' => $data['cta_text'],
            'cta_url' => $data['cta_url'] ?? null,
            'from_name' => $data['from_name'],
            'sign_off_name' => $data['sign_off_name'],
            'reply_to' => $data['reply_to'] ?? 'support@younimatiq.com',
            'days_of_week' => array_values(array_unique(array_map('intval', $data['days_of_week'] ?? [1, 3, 5]))),
            'send_time' => $data['send_time'],
            'timezone' => $data['timezone'],
            'audiences' => array_values(array_unique($data['audiences'] ?? [])),
            'exclude_emails' => $data['exclude_emails'] ?? null,
            'include_emails' => $data['include_emails'] ?? null,
            'course_source' => $data['course_source'],
            'course_ids' => $data['course_source'] === 'manual'
                ? array_values(array_unique(array_map('intval', $data['course_ids'] ?? [])))
                : null,
            'max_courses' => $data['max_courses'],
            'exclude_camps' => $request->boolean('exclude_camps'),
            'skip_recent_days' => $data['skip_recent_days'],
            'utm_campaign' => $data['utm_campaign'] ?: 'email_campaign',
            'notify_admin_on_send' => $request->boolean('notify_admin_on_send'),
            'theme_html' => $themeHtml,
            'theme_path' => $themePath,
        ]);

        return back()->with('success', 'Email automation settings saved.');
    }

    public function destroy(EmailAutomation $emailAutomation)
    {
        if (EmailAutomation::query()->count() <= 1) {
            return back()->with('error', 'Keep at least one email automation. Create another before deleting this one.');
        }

        if ($emailAutomation->theme_path && Storage::disk('public')->exists($emailAutomation->theme_path)) {
            Storage::disk('public')->delete($emailAutomation->theme_path);
        }

        $emailAutomation->delete();

        return redirect()
            ->route('admin.email-automations.index')
            ->with('success', 'Email automation deleted.');
    }

    public function preview(EmailAutomation $emailAutomation, EmailAutomationService $service)
    {
        $html = $service->previewHtml($emailAutomation, 'Parent', 'preview@younimatiq.com');

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function sendTest(Request $request, EmailAutomation $emailAutomation, EmailAutomationService $service)
    {
        $data = $request->validate([
            'test_email' => ['required', 'email'],
        ]);

        try {
            $result = $service->dispatch($emailAutomation, 'test', $data['test_email']);

            return back()->with('success', "Test email queued to {$data['test_email']} (run #{$result['run']->id}).");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function sendNow(EmailAutomation $emailAutomation, EmailAutomationService $service)
    {
        try {
            $result = $service->dispatch($emailAutomation, 'manual');

            return back()->with(
                'success',
                "Campaign queued to {$result['queued']} recipients (run #{$result['run']->id})."
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function previewCustom(Request $request, EmailAutomation $emailAutomation, EmailAutomationService $service)
    {
        $subject = (string) $request->query('subject', '');
        $body = (string) $request->query('body', '');

        $html = $service->previewCustomCampaign($emailAutomation, $subject, $body);

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function sendCustom(Request $request, EmailAutomation $emailAutomation, EmailAutomationService $service)
    {
        $data = $request->validate([
            'mode' => ['required', 'in:test,audience'],
            'test_email' => ['nullable', 'email', 'required_if:mode,test'],
            'subject' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:50000'],
        ]);

        try {
            $result = $service->dispatchCustomCampaign($emailAutomation, [
                'subject' => $data['subject'],
                'body' => $data['body'],
                'test_email' => $data['mode'] === 'test' ? ($data['test_email'] ?? null) : null,
            ]);

            $target = $data['mode'] === 'test'
                ? ($data['test_email'] ?? 'test inbox')
                : ($result['queued'].' recipients');

            return back()->with(
                'success',
                "Custom email queued to {$target} (run #{$result['run']->id})."
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function addSuppression(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'reason' => ['nullable', 'string', 'max:80'],
        ]);

        EmailSuppression::suppress(
            $data['email'],
            $data['reason'] ?: 'manual',
            'admin'
        );

        return back()->with('success', 'Email added to suppression list.');
    }

    public function removeSuppression(EmailSuppression $suppression)
    {
        $suppression->delete();

        return back()->with('success', 'Email removed from suppression list.');
    }

    public function previewCourseTrigger(Request $request, Course $course, EmailAutomationService $service)
    {
        $type = $request->query('type', 'new_course');
        if (! in_array($type, ['new_course', 'offer'], true)) {
            abort(404);
        }

        $html = $service->previewCourseTrigger($course, $type, [
            'email_automation_id' => $request->query('email_automation_id'),
            'subject' => $request->query('subject'),
            'heading' => $request->query('heading'),
            'intro_text' => $request->query('intro_text'),
            'cta_text' => $request->query('cta_text'),
            'original_price' => $request->query('original_price'),
            'offer_price' => $request->query('offer_price', $course->price_usd),
            'offer_note' => $request->query('offer_note'),
        ]);

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function sendCourseTrigger(Request $request, Course $course, EmailAutomationService $service)
    {
        $data = $request->validate([
            'type' => ['required', 'in:new_course,offer'],
            'mode' => ['required', 'in:test,audience'],
            'email_automation_id' => ['nullable', 'integer', Rule::exists('email_automations', 'id')],
            'test_email' => ['nullable', 'email', 'required_if:mode,test'],
            'subject' => ['nullable', 'string', 'max:180'],
            'heading' => ['nullable', 'string', 'max:120'],
            'intro_text' => ['nullable', 'string', 'max:1000'],
            'cta_text' => ['nullable', 'string', 'max:80'],
            'original_price' => ['nullable', 'numeric', 'min:0'],
            'offer_price' => ['nullable', 'numeric', 'min:0', 'required_if:type,offer'],
            'offer_note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $result = $service->dispatchCourseTrigger($course, $data['type'], [
                'email_automation_id' => $data['email_automation_id'] ?? null,
                'subject' => $data['subject'] ?? null,
                'heading' => $data['heading'] ?? null,
                'intro_text' => $data['intro_text'] ?? null,
                'cta_text' => $data['cta_text'] ?? null,
                'original_price' => $data['original_price'] ?? null,
                'offer_price' => $data['offer_price'] ?? null,
                'offer_note' => $data['offer_note'] ?? null,
                'test_email' => $data['mode'] === 'test' ? ($data['test_email'] ?? null) : null,
            ]);

            $label = $data['type'] === 'offer' ? 'Offer' : 'New course';
            $target = $data['mode'] === 'test'
                ? ($data['test_email'] ?? 'test inbox')
                : ($result['queued'].' recipients');

            return back()->with(
                'success',
                "{$label} email queued to {$target} (run #{$result['run']->id})."
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }
}
