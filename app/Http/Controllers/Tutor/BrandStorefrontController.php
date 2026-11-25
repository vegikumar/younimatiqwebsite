<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Review;
use App\Models\User;
use App\Support\TutorInertiaProps;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BrandStorefrontController extends Controller
{
    /**
     * Public Teacher Storefront Page ("The Storefront")
     */
    public function show(User $tutor)
    {
        if ($tutor->role !== 'tutor') {
            abort(404);
        }

        $courses = Course::where('tutor_id', $tutor->id)->where('status', 'published')->get();
        $courseIds = $courses->pluck('id');
        // Written parent/student reviews are admin-only; public storefront gets aggregate score only.
        $averageRating = round((float) (Review::whereIn('course_id', $courseIds)->avg('rating') ?? 5), 1);

        return \App\Support\InertiaPage::react(
            'Public/TutorStorefront',
            TutorInertiaProps::publicTutorStorefront($tutor, $averageRating, $courses, collect())
        );
    }

    public function edit()
    {
        $tutor = Auth::guard('tutor')->user();

        return \App\Support\InertiaPage::react('Tutor/ProfileStorefront', [
            'profile' => TutorInertiaProps::tutorStorefrontProfile($tutor),
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::guard('tutor')->user();

        $request->validate([
            'bio' => 'nullable|string',
            'teaching_style' => 'nullable|string',
            'intro_video_url' => 'nullable|url',
        ]);

        $user->update([
            'bio' => $request->bio,
            'teaching_style' => $request->teaching_style,
            'intro_video_url' => $request->intro_video_url,
        ]);

        return redirect()->back()->with('success', 'Teacher Brand Storefront updated!');
    }
}
