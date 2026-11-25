<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Support\StudentInertiaProps;
use Illuminate\Support\Facades\Auth;

class CertificateController extends Controller
{
    public function show(Course $course)
    {
        $enrollment = Enrollment::where('user_id', Auth::id())
            ->where('course_id', $course->id)
            ->whereIn('status', ['active', 'completed'])
            ->firstOrFail();

        if ($enrollment->hasUploadedCertificate()) {
            return redirect()->route('student.courses.certificate.download', $course);
        }

        abort_unless($enrollment->status === 'completed', 403, 'Certificate available only for completed courses.');

        $course->load('tutor');
        $user = Auth::user();

        return \App\Support\InertiaPage::react('Student/Certificate', [
            'course' => StudentInertiaProps::courseBrief($course),
            'enrollment' => [
                'status' => $enrollment->status,
                'certificate_url' => $enrollment->certificate_path
                    ? route('student.courses.certificate.download', $course)
                    : null,
            ],
            'user' => ['name' => $user->name],
        ]);
    }
}
