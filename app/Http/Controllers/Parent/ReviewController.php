<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Review;
use App\Support\StudentInertiaProps;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function index()
    {
        $parent = Auth::guard('parent')->user();
        $childIds = $parent->children()->pluck('users.id');

        $reviews = Review::with('course')
            ->where('user_id', $parent->id)
            ->orderByDesc('created_at')
            ->get();

        $enrolledCourseIds = Enrollment::whereIn('user_id', $childIds)
            ->whereIn('status', ['active', 'completed'])
            ->pluck('course_id')
            ->unique()
            ->values();

        $reviewedCourseIds = $reviews->pluck('course_id');

        $unreviewedCourses = Course::whereIn('id', $enrolledCourseIds)
            ->whereNotIn('id', $reviewedCourseIds)
            ->orderBy('title')
            ->get();

        $childrenByCourse = Enrollment::with('user:id,name')
            ->whereIn('user_id', $childIds)
            ->whereIn('course_id', $enrolledCourseIds)
            ->whereIn('status', ['active', 'completed'])
            ->get()
            ->groupBy('course_id')
            ->map(fn ($rows) => $rows->pluck('user.name')->filter()->unique()->values()->all());

        return \App\Support\InertiaPage::react('Parent/Reviews', [
            'reviews' => $reviews->map(fn (Review $r) => array_merge(
                StudentInertiaProps::review($r),
                ['childNames' => $childrenByCourse->get($r->course_id, [])]
            ))->values()->all(),
            'unreviewedCourses' => $unreviewedCourses->map(fn (Course $c) => [
                'id' => $c->id,
                'title' => $c->title,
                'childNames' => $childrenByCourse->get($c->id, []),
            ])->values()->all(),
            'storeUrl' => route('parent.reviews.store'),
        ]);
    }

    public function store(Request $request)
    {
        $parent = Auth::guard('parent')->user();

        $data = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $childIds = $parent->children()->pluck('users.id');
        $allowed = Enrollment::whereIn('user_id', $childIds)
            ->where('course_id', $data['course_id'])
            ->whereIn('status', ['active', 'completed'])
            ->exists();

        if (! $allowed) {
            return back()->with('error', 'You can only review courses your children are enrolled in.');
        }

        Review::updateOrCreate(
            [
                'user_id' => $parent->id,
                'course_id' => $data['course_id'],
            ],
            [
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null,
                'reviewer_name' => $parent->name,
            ]
        );

        return redirect()
            ->route('parent.reviews')
            ->with('success', 'Review submitted.');
    }
}
