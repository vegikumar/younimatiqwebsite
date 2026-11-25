<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use App\Services\EnrollmentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEditScheduleTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active', 'email_verified_at' => now()]);
    }

    private function makeStudent(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'student',
            'status' => 'active',
            'email_verified_at' => now(),
        ], $overrides));
    }

    private function makeCourse(): Course
    {
        $tutor = User::factory()->create(['role' => 'tutor', 'status' => 'active']);

        return Course::create([
            'tutor_id' => $tutor->id,
            'title' => 'Live Math',
            'description' => 'Live classes',
            'price' => 100,
            'course_type' => 'live',
            'format' => 'tutoring',
            'number_of_classes' => 8,
            'status' => 'published',
        ]);
    }

    private function enroll(User $student, Course $course, int $classes, string $timezone = 'UTC'): Enrollment
    {
        $payment = Payment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'transaction_id' => 'TXN-EDIT-'.uniqid(),
            'gateway' => 'stripe',
            'currency' => 'USD',
            'amount' => 100,
            'pricing_model' => 'custom',
            'delivery_mode' => 'live',
            'schedule' => [
                'classes' => $classes,
                'start_date' => now()->addDay()->toDateString(),
                'days' => [1, 3, 5],
                'times' => [1 => '17:00', 3 => '17:00', 5 => '17:00'],
                'timezone' => $timezone,
            ],
            'status' => 'completed',
        ]);

        return app(EnrollmentService::class)->enrollFromPayment($payment);
    }

    private function enrollmentBooking(User $student, Course $course): Booking
    {
        return Booking::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->where('session_type', 'enrollment')
            ->firstOrFail();
    }

    private function edit(User $admin, Booking $booking, array $data)
    {
        return $this->actingAs($admin, 'admin')
            ->post(route('admin.bookings.update-schedule', $booking->id), $data)
            ->assertSessionHasNoErrors();
    }

    public function test_edit_regenerates_series_when_classes_are_already_in_the_past(): void
    {
        $admin = $this->makeAdmin();
        $student = $this->makeStudent();
        $course = $this->makeCourse();
        $enrollment = $this->enroll($student, $course, 3);

        // Push the whole series into the past: these are stale, not completed.
        Booking::where('student_id', $student->id)
            ->where('session_type', 'scheduled_class')
            ->update(['scheduled_at' => now()->subDays(14)]);

        $this->edit($admin, $this->enrollmentBooking($student, $course), [
            'number_of_classes' => 3,
            'schedule_start_date' => now()->addDays(3)->toDateString(),
            'timezone' => 'Asia/Kolkata',
            'schedule_days' => [2, 4],
            'schedule_times' => [2 => '10:00', 4 => '11:00'],
        ]);

        $scheduled = Booking::where('student_id', $student->id)
            ->where('session_type', 'scheduled_class')
            ->get();

        $this->assertCount(3, $scheduled, 'Stale past classes must be replaced by 3 new ones.');
        $this->assertTrue($scheduled->every(fn ($b) => $b->scheduled_at->isFuture()));
        $this->assertSame(3, $enrollment->fresh()->paid_classes_count);

        foreach ($scheduled as $b) {
            $local = Carbon::parse($b->scheduled_at)->setTimezone('Asia/Kolkata');
            $this->assertContains((int) $local->dayOfWeek, [2, 4]);
            $this->assertContains($local->format('H:i'), ['10:00', '11:00']);
        }
    }

    public function test_edit_keeps_completed_classes_as_history(): void
    {
        $admin = $this->makeAdmin();
        $student = $this->makeStudent();
        $course = $this->makeCourse();
        $enrollment = $this->enroll($student, $course, 4);

        Booking::where('student_id', $student->id)
            ->where('session_type', 'scheduled_class')
            ->orderBy('scheduled_at')
            ->limit(2)
            ->get()
            ->each(function ($b) {
                $b->status = 'completed';
                $b->save();
            });

        $this->edit($admin, $this->enrollmentBooking($student, $course), [
            'number_of_classes' => 4,
            'schedule_start_date' => now()->addDays(5)->toDateString(),
            'timezone' => 'UTC',
            'schedule_days' => [1],
            'schedule_times' => [1 => '09:00'],
        ]);

        $all = Booking::where('student_id', $student->id)
            ->where('session_type', 'scheduled_class')
            ->get();

        $this->assertSame(2, $all->where('status', 'completed')->count());
        $this->assertSame(2, $all->where('status', 'scheduled')->count());
        $this->assertSame(4, $enrollment->fresh()->paid_classes_count);
    }

    public function test_edit_schedule_form_renders_when_start_date_is_in_the_past(): void
    {
        $admin = $this->makeAdmin();
        $student = $this->makeStudent(['timezone' => '-03:30']);
        $course = $this->makeCourse();
        $this->enroll($student, $course, 3, '-03:30');

        Booking::where('student_id', $student->id)
            ->where('session_type', 'scheduled_class')
            ->update(['scheduled_at' => now()->subDays(10)]);

        $html = $this->actingAs($admin, 'admin')->get(route('admin.enrollments'))->assertOk()->getContent();

        // The editor's prefilled start date is usually in the past (the series already
        // started), so it must not carry a `min` that would block form submission.
        preg_match_all('/<input[^>]*name="schedule_start_date"[^>]*>/', $html, $matches);
        $editorTag = collect($matches[0])->first(fn ($tag) => str_contains($tag, 'value="'));
        $this->assertNotNull($editorTag, 'Editor start-date input not rendered.');
        $this->assertStringNotContainsString('min=', $editorTag);

        // An unlisted fixed-offset timezone must still render as a selected option,
        // otherwise the browser silently submits the first option in the list.
        $this->assertMatchesRegularExpression(
            '/<option value="-03:30" selected>/',
            $html
        );
    }
}
