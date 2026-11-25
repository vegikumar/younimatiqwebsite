<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\StudentPortfolio;
use App\Services\ClassScheduleService;
use App\Services\SkillTreeService;
use App\Services\TimezoneService;
use App\Support\ApiAssetUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CourseExtrasController extends Controller
{
    use RespondsWithJson;

    public function scheduleWizardShow(Request $request, Course $course)
    {
        $enrollment = Enrollment::where('user_id', $request->user()->id)->where('course_id', $course->id)->first();
        if (! $enrollment) {
            return $this->error('Not enrolled.', 403);
        }

        $paidClasses = $enrollment->paid_classes_count ?? $course->number_of_classes ?? 1;
        $existingBookings = Booking::where('student_id', $request->user()->id)
            ->where('course_id', $course->id)
            ->where('session_type', '!=', 'enrollment')
            ->count();

        $remaining = max(0, $paidClasses - $existingBookings);

        return $this->success([
            'course_id' => $course->id,
            'course_title' => $course->title,
            'paid_classes' => $paidClasses,
            'existing_bookings' => $existingBookings,
            'remaining' => $remaining,
            'message' => $remaining > 0
                ? "You have {$remaining} class(es) left to schedule."
                : 'All paid classes are already scheduled.',
            'default_timezone' => $request->user()->timezone
                ?? TimezoneService::sanitizeTimezone(null),
            'timezones' => collect(TimezoneService::commonTimezones())
                ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                ->values(),
            'days' => [
                ['value' => 1, 'label' => 'Monday'],
                ['value' => 2, 'label' => 'Tuesday'],
                ['value' => 3, 'label' => 'Wednesday'],
                ['value' => 4, 'label' => 'Thursday'],
                ['value' => 5, 'label' => 'Friday'],
                ['value' => 6, 'label' => 'Saturday'],
                ['value' => 0, 'label' => 'Sunday'],
            ],
            'default_time' => '17:00',
        ]);
    }

    public function scheduleWizardStore(Request $request, Course $course)
    {
        $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'days' => 'required|array|min:1',
            'days.*' => 'integer|between:0,6',
            'times' => 'required|array',
            'timezone' => 'required|string',
        ]);

        $enrollment = Enrollment::where('user_id', $request->user()->id)->where('course_id', $course->id)->first();
        if (! $enrollment) {
            return $this->error('Not enrolled.', 403);
        }

        $paidClasses = $enrollment->paid_classes_count ?? $course->number_of_classes ?? 1;
        $existingBookings = Booking::where('student_id', $request->user()->id)
            ->where('course_id', $course->id)
            ->where('session_type', '!=', 'enrollment')
            ->count();

        $classesToSchedule = $paidClasses - $existingBookings;
        if ($classesToSchedule <= 0) {
            return $this->error('All paid classes are already scheduled.', 422);
        }

        $scheduleService = app(ClassScheduleService::class);
        $schedule = $scheduleService->normalize([
            'number_of_classes' => $classesToSchedule,
            'start_date' => $request->start_date,
            'days' => $request->days,
            'times' => $request->times,
            'timezone' => $request->timezone,
        ]);

        if (! $schedule) {
            return $this->error('Select at least one day with a time, and a valid start date.', 422);
        }

        $created = $scheduleService->createBookings(
            $request->user()->id,
            $course->id,
            $schedule,
            $course->tutor_id,
            null,
            'Scheduled class for '.$course->title
        );

        if (empty($created)) {
            return $this->error('No future sessions could be scheduled with the selected days/times.', 422);
        }

        return $this->success(['bookings_created' => count($created)], 'Schedule saved.');
    }

    public function skillTree(Request $request, Course $course, SkillTreeService $skillTreeService)
    {
        return $this->success($skillTreeService->getStudentSkillTree($request->user()->id, $course->id));
    }

    public function portfolioIndex(Request $request, Course $course)
    {
        $enrollment = Enrollment::where('user_id', $request->user()->id)->where('course_id', $course->id)->first();
        if (! $enrollment) {
            return $this->error('Not enrolled.', 403);
        }

        $items = StudentPortfolio::where('user_id', $request->user()->id)
            ->where('course_id', $course->id)
            ->latest()
            ->get()
            ->map(fn (StudentPortfolio $p) => [
                'id' => $p->id,
                'project_name' => $p->project_name,
                'project_url' => $p->project_url,
                'screenshot_url' => ApiAssetUrl::fromPath($p->screenshot_url),
                'tutor_feedback' => $p->tutor_feedback,
                'badge_earned' => $p->badge_earned,
                'visibility' => $p->visibility ?? 'private',
                'status' => $p->status,
                'created_at' => $p->created_at?->toIso8601String(),
            ]);

        return $this->success(['portfolios' => $items]);
    }

    public function sharePortfolio(Request $request, Course $course, StudentPortfolio $portfolio)
    {
        if ((int) $portfolio->user_id !== (int) $request->user()->id
            || (int) $portfolio->course_id !== (int) $course->id) {
            return $this->error('Portfolio not found.', 404);
        }

        $enrollment = Enrollment::where('user_id', $request->user()->id)->where('course_id', $course->id)->first();
        if (! $enrollment) {
            return $this->error('Not enrolled.', 403);
        }

        $data = $request->validate([
            'visibility' => 'required|in:private,parents,public',
        ]);

        $portfolio->update([
            'visibility' => $data['visibility'],
            'status' => $data['visibility'] === 'private' ? 'draft' : 'approved',
            'approved_at' => $data['visibility'] === 'private' ? null : now(),
        ]);

        $message = match ($data['visibility']) {
            'parents' => 'Project shared with linked parents.',
            'public' => 'Project marked public.',
            default => 'Project set to private.',
        };

        return $this->success([
            'id' => $portfolio->id,
            'project_name' => $portfolio->project_name,
            'project_url' => $portfolio->project_url,
            'screenshot_url' => ApiAssetUrl::fromPath($portfolio->screenshot_url),
            'visibility' => $portfolio->visibility,
            'status' => $portfolio->status,
            'created_at' => $portfolio->created_at?->toIso8601String(),
        ], $message);
    }

    public function storePortfolio(Request $request, Course $course)
    {
        $enrollment = Enrollment::where('user_id', $request->user()->id)->where('course_id', $course->id)->first();
        if (! $enrollment) {
            return $this->error('Not enrolled.', 403);
        }

        $data = $request->validate([
            'project_name' => 'required_without:title|nullable|string|max:255',
            'title' => 'required_without:project_name|nullable|string|max:255',
            'project_url' => 'nullable|url|max:2048',
            'screenshot_url' => 'nullable|url|max:2048',
            'description' => 'nullable|string|max:2000',
            'file' => 'nullable|file|image|max:10240',
        ]);

        $screenshotUrl = $data['screenshot_url'] ?? null;
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('portfolios/'.$request->user()->id, 'public');
            $screenshotUrl = '/storage/'.$path;
        }

        $portfolio = StudentPortfolio::create([
            'user_id' => $request->user()->id,
            'course_id' => $course->id,
            'project_name' => $data['project_name'] ?? $data['title'],
            'project_url' => $data['project_url'] ?? null,
            'screenshot_url' => $screenshotUrl,
        ]);

        return $this->success([
            'id' => $portfolio->id,
            'project_name' => $portfolio->project_name,
            'project_url' => $portfolio->project_url,
            'screenshot_url' => ApiAssetUrl::fromPath($portfolio->screenshot_url),
            'created_at' => $portfolio->created_at?->toIso8601String(),
        ], 'Project added to portfolio.', 201);
    }

    public function certificate(Request $request, Course $course)
    {
        $enrollment = Enrollment::where('user_id', $request->user()->id)
            ->where('course_id', $course->id)
            ->whereIn('status', ['active', 'completed'])
            ->first();

        if (! $enrollment) {
            return $this->error('Enrollment not found.', 404);
        }

        if ($enrollment->hasUploadedCertificate()) {
            return $this->success([
                'course' => ['id' => $course->id, 'title' => $course->title],
                'student_name' => $request->user()->name,
                'completed_at' => $enrollment->updated_at?->toIso8601String(),
                'certificate_download_url' => url('/api/v1/student/courses/'.$course->getRouteKey().'/certificate/download'),
                'has_uploaded_certificate' => true,
            ]);
        }

        if ($enrollment->status !== 'completed') {
            return $this->error('Certificate available only for completed courses.', 403);
        }

        return $this->success([
            'course' => ['id' => $course->id, 'title' => $course->title],
            'student_name' => $request->user()->name,
            'completed_at' => $enrollment->updated_at?->toIso8601String(),
            'certificate_download_url' => null,
            'has_uploaded_certificate' => false,
        ]);
    }

    public function downloadCertificate(Request $request, Course $course)
    {
        $enrollment = Enrollment::where('user_id', $request->user()->id)
            ->where('course_id', $course->id)
            ->whereIn('status', ['active', 'completed'])
            ->first();

        if (! $enrollment || ! $enrollment->hasUploadedCertificate()) {
            return $this->error('Certificate not available.', 404);
        }

        $path = $enrollment->certificate_path;
        if (! Storage::disk('public')->exists($path)) {
            return $this->error('Certificate file missing.', 404);
        }

        return Storage::disk('public')->download(
            $path,
            basename($path),
            ['Content-Disposition' => 'attachment; filename="'.basename($path).'"']
        );
    }
}
