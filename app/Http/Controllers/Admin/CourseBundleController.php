<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseBundle;
use Illuminate\Http\Request;

class CourseBundleController extends Controller
{
    public function index()
    {
        $bundles = CourseBundle::with('courses:id,title')->latest()->get();
        $courses = Course::where('status', 'published')->orderBy('title')->get(['id', 'title']);

        return \App\Support\InertiaPage::react('Admin/Bundles/Index', [
            'bundles' => $bundles->map(fn (CourseBundle $b) => [
                'id' => $b->id,
                'name' => $b->name,
                'description' => $b->description,
                'discount_percent' => $b->discount_percent,
                'is_active' => $b->is_active,
                'course_ids' => $b->courses->pluck('id')->all(),
                'courses' => $b->courses->map(fn ($c) => ['id' => $c->id, 'title' => $c->title])->values()->all(),
            ])->values()->all(),
            'courses' => $courses->map(fn ($c) => ['id' => $c->id, 'title' => $c->title])->values()->all(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:1000',
            'discount_percent' => 'required|integer|min:1|max:50',
            'course_ids' => 'required|array|min:2',
            'course_ids.*' => 'exists:courses,id',
            'is_active' => 'nullable|boolean',
        ]);

        $bundle = CourseBundle::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'discount_percent' => $data['discount_percent'],
            'is_active' => $request->boolean('is_active', true),
        ]);
        $bundle->courses()->sync($data['course_ids']);

        return back()->with('success', 'Bundle created.');
    }

    public function update(Request $request, CourseBundle $bundle)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:1000',
            'discount_percent' => 'required|integer|min:1|max:50',
            'course_ids' => 'required|array|min:2',
            'course_ids.*' => 'exists:courses,id',
            'is_active' => 'nullable|boolean',
        ]);

        $bundle->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'discount_percent' => $data['discount_percent'],
            'is_active' => $request->boolean('is_active', true),
        ]);
        $bundle->courses()->sync($data['course_ids']);

        return back()->with('success', 'Bundle updated.');
    }

    public function destroy(CourseBundle $bundle)
    {
        $bundle->delete();

        return back()->with('success', 'Bundle removed.');
    }
}
