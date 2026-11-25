<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Services\EnrollmentCertificateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EnrollmentCertificateController extends Controller
{
    public function __construct(private EnrollmentCertificateService $certificates)
    {
    }

    public function upload(Request $request, Enrollment $enrollment)
    {
        $request->validate([
            'certificate' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $this->certificates->upload($enrollment, $request->file('certificate'));

        return back()->with('success', 'Certificate uploaded successfully.');
    }

    public function uploadAsTutor(Request $request, Enrollment $enrollment)
    {
        abort_unless($this->certificates->tutorCanManage(Auth::user(), $enrollment), 403);

        return $this->upload($request, $enrollment);
    }

    public function download(Course $course)
    {
        $enrollment = Enrollment::query()
            ->where('user_id', Auth::id())
            ->where('course_id', $course->id)
            ->whereNotNull('certificate_path')
            ->firstOrFail();

        if (!Storage::disk('public')->exists($enrollment->certificate_path)) {
            abort(404, 'Certificate file not found.');
        }

        return Storage::disk('public')->download(
            $enrollment->certificate_path,
            $this->certificates->downloadFilename($enrollment)
        );
    }
}
