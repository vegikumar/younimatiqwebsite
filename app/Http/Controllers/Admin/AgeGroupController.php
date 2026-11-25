<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgeGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AgeGroupController extends Controller
{
    public function index()
    {
        $ageGroups = AgeGroup::orderBy('sort_order')->orderBy('name')->get()->map(fn (AgeGroup $group) => [
            'id' => $group->id,
            'name' => $group->name,
            'slug' => $group->slug,
            'age_range' => $group->age_range,
            'description' => $group->description,
            'sort_order' => $group->sort_order,
            'is_active' => (bool) $group->is_active,
        ])->values()->all();

        return \App\Support\InertiaPage::react('Admin/AgeGroups/Index', [
            'ageGroups' => $ageGroups,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'age_range' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer',
            'slug' => 'nullable|string|max:100',
        ]);

        $data['slug'] = \Illuminate\Support\Str::slug(empty($data['slug']) ? $data['name'] : $data['slug']);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active', true);

        AgeGroup::create($data);

        return back()->with('success', 'Age group created. It will appear in header dropdowns and filters.');
    }

    public function update(Request $request, AgeGroup $ageGroup)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'age_range' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer',
            'slug' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $data['slug'] = \Illuminate\Support\Str::slug(empty($data['slug']) ? $data['name'] : $data['slug']);
        if (! array_key_exists('is_active', $data)) {
            $data['is_active'] = $ageGroup->is_active;
        }

        $ageGroup->update($data);

        return back()->with('success', 'Age group updated.');
    }

    public function destroy(AgeGroup $ageGroup)
    {
        $ageGroup->delete();

        return back()->with('success', 'Age group deleted.');
    }
}
