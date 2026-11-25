<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Course;
use App\Models\Setting;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SkillController extends Controller
{
    public function index()
    {
        $skills = Skill::withCount('tutors')->orderBy('name')->get();
        $courses = Course::with('skills:id')->orderBy('title')->get(['id', 'title']);
        $categories = Category::orderBy('name')->get(['id', 'name']);
        $categorySkillMap = \DB::table('category_skill_requirements')
            ->select('category_id', 'skill_id')
            ->get()
            ->groupBy('category_id')
            ->map(fn ($rows) => $rows->pluck('skill_id')->all())
            ->all();

        $skillsPayload = $skills->map(fn (Skill $skill) => [
            'id' => $skill->id,
            'name' => $skill->name,
            'tutors_count' => $skill->tutors_count,
            'is_system' => (bool) $skill->is_system,
        ])->values()->all();

        $coursesPayload = $courses->map(fn ($course) => [
            'id' => $course->id,
            'title' => $course->title,
            'skill_ids' => $course->skills->pluck('id')->all(),
        ])->values()->all();

        $categoriesPayload = $categories->map(fn ($category) => [
            'id' => $category->id,
            'name' => $category->name,
            'skill_ids' => $categorySkillMap[$category->id] ?? [],
        ])->values()->all();

        return \App\Support\InertiaPage::react('Admin/Skills/Index', [
            'skills' => $skillsPayload,
            'courses' => $coursesPayload,
            'categories' => $categoriesPayload,
            'matchMode' => Setting::where('key', 'tutor_match_mode')->value('value') ?? 'suggest',
            'hrEmails' => Setting::where('key', 'hr_review_emails')->value('value') ?? 'hr@younimatiq.com',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        Skill::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'is_system' => false,
        ]);

        return back()->with('success', 'Skill created.');
    }

    public function update(Request $request, Skill $skill)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        $skill->update([
            'name' => $data['name'],
            'slug' => $skill->is_system ? $skill->slug : Str::slug($data['name']),
            'description' => $data['description'] ?? null,
        ]);

        return back()->with('success', 'Skill updated.');
    }

    public function destroy(Skill $skill)
    {
        if ($skill->is_system) {
            return back()->with('error', 'System skills cannot be deleted.');
        }
        $skill->delete();

        return back()->with('success', 'Skill deleted.');
    }

    public function syncCourse(Request $request, Course $course)
    {
        $data = $request->validate([
            'skill_ids' => 'nullable|array',
            'skill_ids.*' => 'exists:skills,id',
        ]);
        $course->skills()->sync($data['skill_ids'] ?? []);

        return back()->with('success', 'Course skill requirements saved.');
    }

    public function syncCategory(Request $request, Category $category)
    {
        $data = $request->validate([
            'skill_ids' => 'nullable|array',
            'skill_ids.*' => 'exists:skills,id',
        ]);

        \DB::table('category_skill_requirements')->where('category_id', $category->id)->delete();
        foreach ($data['skill_ids'] ?? [] as $skillId) {
            \DB::table('category_skill_requirements')->insert([
                'category_id' => $category->id,
                'skill_id' => $skillId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return back()->with('success', 'Category skill requirements saved.');
    }

    public function updateMatchSettings(Request $request)
    {
        $data = $request->validate([
            'tutor_match_mode' => 'required|in:auto,suggest,manual',
            'hr_review_emails' => 'nullable|string|max:1000',
        ]);

        Setting::updateOrCreate(['key' => 'tutor_match_mode'], ['value' => $data['tutor_match_mode']]);
        Setting::updateOrCreate(['key' => 'hr_review_emails'], ['value' => $data['hr_review_emails'] ?? '']);

        return back()->with('success', 'Matching & HR settings saved.');
    }
}
