@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="bg-white rounded-[2rem] shadow-sm border border-surface-container-high p-5 md:p-8">
        <h1 class="text-3xl font-bold mb-2">Schedule Classes for {{ $course->title }}</h1>
        <p class="text-on-surface-variant mb-8">You have {{ $course->number_of_classes - $existingBookings }} classes left to schedule for this course.</p>

        <form action="{{ route('student.courses.schedule-wizard.store', $course) }}" method="POST">
            @csrf

            <!-- Timezone Selection -->
            <div class="mb-8">
                <label class="block text-sm font-bold text-on-surface mb-2">Your Timezone</label>
                <select name="timezone" class="w-full md:w-1/2 px-4 py-2 bg-surface-container-low border border-outline-variant/30 rounded-xl" required id="timezone">
                    <option value="America/New_York">Eastern Time (ET)</option>
                    <option value="America/Chicago">Central Time (CT)</option>
                    <option value="America/Denver">Mountain Time (MT)</option>
                    <option value="America/Los_Angeles">Pacific Time (PT)</option>
                    <option value="Europe/London">London (GMT/BST)</option>
                    <option value="Asia/Kolkata">India Standard Time (IST)</option>
                    <!-- Add more as needed, maybe autodetect with JS -->
                </select>
            </div>

            <!-- Start Date -->
            <div class="mb-8">
                <label class="block text-sm font-bold text-on-surface mb-2">When would you like to start?</label>
                <input type="date" name="start_date" required min="{{ date('Y-m-d') }}" class="w-full md:w-1/2 px-4 py-2 bg-surface-container-low border border-outline-variant/30 rounded-xl">
            </div>

            <!-- Days and Times -->
            <div class="mb-8">
                <label class="block text-sm font-bold text-on-surface mb-2">Select the days and times you wish to attend</label>
                <p class="text-sm text-on-surface-variant mb-4">Select the days and a time for each.</p>
                
                <div class="space-y-4">
                    @php
                        $daysOfWeek = [
                            1 => 'Monday',
                            2 => 'Tuesday',
                            3 => 'Wednesday',
                            4 => 'Thursday',
                            5 => 'Friday',
                            6 => 'Saturday',
                            0 => 'Sunday'
                        ];
                    @endphp

                    @foreach($daysOfWeek as $value => $label)
                        <div class="flex flex-col md:flex-row md:items-center gap-3 bg-surface-container-low p-4 rounded-xl border border-outline-variant/30" data-day-time-row>
                            <label class="flex items-center gap-3 w-40 cursor-pointer">
                                <input type="checkbox" name="days[]" value="{{ $value }}" class="day-checkbox w-5 h-5 text-primary bg-white border-outline-variant rounded focus:ring-primary focus:ring-2" onchange="toggleTimeInput(this, {{ $value }})">
                                <span class="font-medium">{{ $label }}</span>
                            </label>
                            <div class="flex-1 flex flex-col sm:flex-row sm:items-center gap-3">
                                <select name="times[{{ $value }}]" id="time_{{ $value }}" data-day-time-select data-day="{{ $value }}" disabled class="time-input w-full max-w-md px-3 py-1.5 bg-white border border-outline-variant/30 rounded-lg text-sm text-on-surface-variant disabled:opacity-50">
                                    <option value="">Select a recommended time</option>
                                </select>
                                <div data-custom-time-wrap style="display:none;">
                                    <input type="time" data-custom-time step="300" class="w-40 px-3 py-1.5 bg-white border border-outline-variant/30 rounded-lg text-sm">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end gap-4 mt-8 pt-6 border-t border-surface-container-high">
                <a href="{{ route('student.dashboard') }}" class="btn-secondary px-6">Cancel</a>
                <button type="submit" class="btn-primary px-8">Confirm Schedule</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleTimeInput(checkbox, dayValue) {
        const timeInput = document.getElementById('time_' + dayValue);
        if (checkbox.checked) {
            timeInput.disabled = false;
            timeInput.required = true;
            timeInput.dispatchEvent(new Event('change'));
        } else {
            timeInput.disabled = true;
            timeInput.required = false;
            timeInput.value = '';
        }
    }

    // Auto detect timezone
    document.addEventListener("DOMContentLoaded", function() {
        const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
        const select = document.getElementById('timezone');
        let optionExists = false;
        
        for(let i = 0; i < select.options.length; i++) {
            if(select.options[i].value === tz) {
                optionExists = true;
                break;
            }
        }
        
        if(!optionExists && tz) {
            const option = document.createElement("option");
            option.value = tz;
            option.text = tz + " (Auto)";
            select.add(option);
        }
        
        select.value = tz;
        select.dispatchEvent(new Event('change'));
    });
</script>
<script src="{{ asset('js/booking-slots.js') }}"></script>
@endsection
