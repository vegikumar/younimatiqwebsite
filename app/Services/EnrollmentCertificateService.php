<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Enrollment;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class EnrollmentCertificateService
{
    public function upload(Enrollment $enrollment, UploadedFile $file): Enrollment
    {
        if ($enrollment->certificate_path) {
            Storage::disk('public')->delete($enrollment->certificate_path);
        }

        $path = $file->store('certificates/enrollments', 'public');

        $enrollment->update(['certificate_path' => $path]);

        $this->notifyStudent($enrollment);

        return $enrollment->fresh();
    }

    public function tutorCanManage(User $tutor, Enrollment $enrollment): bool
    {
        if ((int) $enrollment->course?->tutor_id === (int) $tutor->id) {
            return true;
        }

        return Booking::query()
            ->where('tutor_id', $tutor->id)
            ->where('student_id', $enrollment->user_id)
            ->where('course_id', $enrollment->course_id)
            ->exists();
    }

    public function downloadFilename(Enrollment $enrollment): string
    {
        $courseTitle = $enrollment->course?->title ?? 'course';
        $extension = pathinfo($enrollment->certificate_path, PATHINFO_EXTENSION) ?: 'pdf';
        $slug = \Illuminate\Support\Str::slug($courseTitle);

        return "{$slug}-certificate.{$extension}";
    }

    private function notifyStudent(Enrollment $enrollment): void
    {
        $student = $enrollment->user;
        $course = $enrollment->course;

        if (!$student || !$course) {
            return;
        }

        Notification::create([
            'user_id' => $student->id,
            'title' => 'Certificate Ready',
            'message' => "Your certificate for {$course->title} is ready to download.",
            'data' => [
                'type' => 'certificate_ready',
                'course_id' => $course->id,
                'url' => route('student.courses'),
            ],
        ]);
    }
}
