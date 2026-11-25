<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CourseReviewController extends Controller
{
    /**
     * Show the public review form for a given course.
     */
    public function create(Course $course)
    {
        return \App\Support\InertiaPage::react('Public/Courses/Review', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
            ],
        ]);
    }

    /**
     * Store a new guest review for the course.
     */
    public function store(Request $request, Course $course)
    {
        $validated = $request->validate([
            'reviewer_name' => 'required|string|max:100',
            'rating'        => 'required|integer|min:1|max:5',
            'comment'       => 'required|string|min:10|max:2000',
        ]);

        Review::create([
            'user_id'       => null,
            'course_id'     => $course->id,
            'reviewer_name' => $validated['reviewer_name'],
            'rating'        => $validated['rating'],
            'comment'       => $validated['comment'],
            'review_token'  => Str::uuid(),
        ]);

        return redirect()
            ->route('courses.review.create', $course)
            ->with('success', 'Thank you for your review! It has been posted successfully.');
    }
}
