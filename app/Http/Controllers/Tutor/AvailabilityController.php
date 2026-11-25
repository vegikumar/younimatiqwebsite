<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\TutorAvailability;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AvailabilityController extends Controller
{
    public function index()
    {
        $user = Auth::guard('tutor')->user();

        $availabilities = TutorAvailability::where('tutor_id', $user->id)
            ->whereNull('course_id') // generic availability
            ->get();

        // Map to a grid array [day_of_week => [hour, hour]]
        $grid = [];
        foreach ($availabilities as $av) {
            $hour = (int) substr($av->start_time, 0, 2);
            $grid[$av->day_of_week][] = $hour;
        }

        $gridRows = collect($grid)->map(fn ($hours, $day) => [
            'day' => $day,
            'hours' => array_values($hours),
        ])->values()->all();

        return \App\Support\InertiaPage::react('Tutor/Availability', [
            'availabilityCommitment' => $user->availability_commitment ?? 'flexible',
            'grid' => $gridRows,
        ]);
    }

    public function store(Request $request)
    {
        // Single-slot format: add one availability row.
        if ($request->filled('day_of_week')) {
            $request->validate([
                'day_of_week' => 'required|integer|between:0,6',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
                'format_type' => 'nullable|string|max:50',
                'is_recurring' => 'nullable|boolean',
                'max_capacity' => 'nullable|integer|min:1',
            ]);

            TutorAvailability::create([
                'tutor_id' => Auth::guard('tutor')->id(),
                'course_id' => null,
                'day_of_week' => (int) $request->day_of_week,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'is_recurring' => $request->boolean('is_recurring', true),
                'format_type' => $request->input('format_type') ?: 'tutoring_1on1',
                'max_capacity' => $request->input('max_capacity') ?: 1,
            ]);

            return back()->with('success', 'Availability slot added successfully!');
        }

        // Legacy grid format: replace all generic availabilities.
        $request->validate([
            'availability_commitment' => 'required|string',
            'slots' => 'nullable|array',
        ]);

        $user = Auth::guard('tutor')->user();
        $user->availability_commitment = $request->availability_commitment;
        $user->save();

        TutorAvailability::where('tutor_id', $user->id)
            ->whereNull('course_id')
            ->delete();

        if ($request->has('slots')) {
            $inserts = [];
            foreach ($request->slots as $day => $hours) {
                foreach ($hours as $hour) {
                    $inserts[] = [
                        'tutor_id' => $user->id,
                        'day_of_week' => $day,
                        'start_time' => sprintf('%02d:00:00', $hour),
                        'end_time' => sprintf('%02d:59:59', $hour),
                        'is_recurring' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            TutorAvailability::insert($inserts);
        }

        return back()->with('success', 'Availability schedule updated successfully!');
    }

    public function destroy(TutorAvailability $availability)
    {
        abort_unless($availability->tutor_id === Auth::guard('tutor')->id(), 403);
        $availability->delete();

        return back()->with('success', 'Availability slot removed.');
    }
}
