<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Course;
use App\Models\Enrollment;
use App\Services\ClassScheduleService;
use App\Services\EmailService;
use App\Support\StudentInertiaProps;
use Illuminate\Http\Request;

class ScheduleWizardController extends Controller
{
    public function show(Course $course)
    {
        $enrollment = Enrollment::where('user_id', auth()->id())->where('course_id', $course->id)->first();
        if (! $enrollment) {
            return redirect()->route('student.dashboard')->with('error', 'You are not enrolled in this course.');
        }

        $paidClasses = $enrollment->paid_classes_count ?? $course->number_of_classes ?? 1;

        $existingBookings = Booking::where('student_id', auth()->id())
            ->where('course_id', $course->id)
            ->where('session_type', '!=', 'enrollment') // Ignore the placeholder enrollment booking
            ->orderBy('start_time')
            ->get();

        if ($existingBookings->count() >= $paidClasses) {
            return redirect()->route('student.dashboard')->with('info', 'You have already scheduled all your paid classes.');
        }

        return \App\Support\InertiaPage::react('Student/ScheduleWizard', [
            'course' => StudentInertiaProps::courseBrief($course),
            'existingBookings' => $existingBookings->map(fn ($b) => StudentInertiaProps::booking($b))->values()->all(),
            'paidClasses' => $paidClasses,
            'classesLeft' => max(0, $paidClasses - $existingBookings->count()),
        ]);
    }

    public function store(Request $request, Course $course, EmailService $emailService)
    {
        $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'days' => 'required|array|min:1',
            'times' => 'required|array',
            'timezone' => 'required|string',
        ]);

        $enrollment = Enrollment::where('user_id', auth()->id())->where('course_id', $course->id)->first();
        if (! $enrollment) {
            return redirect()->route('student.dashboard')->with('error', 'You are not enrolled in this course.');
        }

        $paidClasses = $enrollment->paid_classes_count ?? $course->number_of_classes ?? 1;

        $existingBookings = Booking::where('student_id', auth()->id())
            ->where('course_id', $course->id)
            ->where('session_type', '!=', 'enrollment')
            ->count();

        $classesToSchedule = $paidClasses - $existingBookings;
        if ($classesToSchedule <= 0) {
            return redirect()->route('student.dashboard')->with('info', 'All paid classes are already scheduled.');
        }

        $scheduleService = app(ClassScheduleService::class);
        $schedule = $scheduleService->normalize([
            'number_of_classes' => $classesToSchedule,
            'start_date' => $request->start_date,
            'days' => $request->days,
            'times' => collect($request->times ?? [])->reject(fn ($t) => ! $t || $t === '__custom__')->all(),
            'timezone' => $request->timezone,
        ]);

        if (! $schedule) {
            return redirect()->back()->with('error', 'Please select at least one day with a time to schedule your classes.');
        }

        $scheduleService->assertWithinBookingWindows($schedule);

        $created = $scheduleService->createBookings(
            auth()->id(),
            $course->id,
            $schedule,
            $course->tutor_id,
            null,
            'Scheduled class for '.$course->title
        );

        $emailService->notifyAdmins(
            'New 1:1 Classes Scheduled',
            'A student has scheduled '.count($created).' classes.<br>'.
            'Student: '.auth()->user()->name.'<br>'.
            'Course: '.$course->title.'<br>'.
            'Timezone: '.$request->timezone.'<br>'
        );

        return redirect()->route('student.dashboard')->with('success', count($created).' classes successfully scheduled!');
    }
}
