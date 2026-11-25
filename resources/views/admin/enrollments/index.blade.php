@extends('layouts.dashboard')
@section('title', 'Enrollments & Demos')
@section('content')
<div class="p-5 md:p-8 space-y-8" x-data="{ tab: '{{ request('tab', 'enrollments') }}' }">
    <div class="flex justify-between items-center">
        <h1 class="font-headline-lg text-2xl font-bold">Enrollment Management</h1>
    </div>

    <!-- Manual Enrollment Form -->
    <form method="POST" action="{{ route('admin.enrollments.store') }}" class="bg-white rounded-2xl border p-6 space-y-5 max-w-4xl">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-bold mb-1">Student</label>
                <select name="user_id" required class="w-full px-4 py-3 border rounded-xl">
                    @foreach($students as $s)<option value="{{ $s->id }}">{{ $s->name }} ({{ $s->email }})</option>@endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold mb-1">Course</label>
                <select name="course_id" required class="w-full px-4 py-3 border rounded-xl">
                    @foreach($courses as $c)<option value="{{ $c->id }}">{{ $c->title }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold mb-1">Number of Classes</label>
                <input type="number" name="number_of_classes" min="1" max="48" placeholder="Default: course classes" class="w-full px-4 py-3 border rounded-xl">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-bold mb-1">Learning Mode</label>
                <select name="delivery_mode" class="w-full px-4 py-3 border rounded-xl">
                    <option value="live">Live</option>
                    <option value="self-paced">Self-paced</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold mb-1">Schedule Start Date</label>
                <input type="date" name="schedule_start_date" min="{{ date('Y-m-d') }}" class="w-full px-4 py-3 border rounded-xl">
            </div>
            <div>
                <label class="block text-sm font-bold mb-1">Timezone</label>
                <select name="timezone" data-current="" class="auto-timezone-select w-full px-4 py-3 border rounded-xl">
                    @foreach(\App\Services\TimezoneService::allTimezones() as $tzKey => $tzLabel)
                        <option value="{{ $tzKey }}">{{ $tzLabel }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-bold mb-2">Weekly Class Schedule (optional)</label>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                @foreach([1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 0 => 'Sunday'] as $dayValue => $dayLabel)
                    <div class="flex items-center gap-2 bg-surface-container-low p-3 rounded-xl border border-outline-variant" x-data="{ on: false }">
                        <input type="checkbox" name="schedule_days[]" value="{{ $dayValue }}" :checked="on" @change="on = $event.target.checked" class="w-4 h-4">
                        <span class="text-sm font-medium w-20">{{ $dayLabel }}</span>
                        <input type="time" name="schedule_times[{{ $dayValue }}]" :disabled="!on" class="text-xs px-2 py-1 border rounded-lg bg-white disabled:opacity-40">
                    </div>
                @endforeach
            </div>
            <p class="text-xs text-outline mt-2">Enter the number of classes and the weekly days/times — all classes are scheduled automatically for the student.</p>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="btn-primary btn-sm">Manual Enroll</button>
        </div>
    </form>

    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl border p-4">
        <form method="GET" action="{{ route('admin.enrollments') }}" class="flex flex-wrap gap-4 items-end">
            <!-- Persist Tab State -->
            <input type="hidden" name="tab" x-model="tab">

            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-bold mb-1">Search Student</label>
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Name or email..." class="w-full px-4 py-2 border rounded-xl">
            </div>

            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-bold mb-1">Filter by Course</label>
                <select name="course_id" class="w-full px-4 py-2 border rounded-xl">
                    <option value="">All Courses</option>
                    @foreach($courses as $c)
                        <option value="{{ $c->id }}" {{ ($courseId ?? '') == $c->id ? 'selected' : '' }}>{{ $c->title }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex-1 min-w-[150px]">
                <label class="block text-sm font-bold mb-1">Status</label>
                <select name="status" class="w-full px-4 py-2 border rounded-xl">
                    <option value="">All Statuses</option>
                    <option value="active" {{ ($status ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="completed" {{ ($status ?? '') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="pending" {{ ($status ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="cancelled" {{ ($status ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="btn-primary py-2 px-4">Apply Filters</button>
                <a href="{{ route('admin.enrollments') }}" class="bg-surface-container-low hover:bg-surface-container text-on-surface py-2 px-4 rounded-xl font-bold transition-colors flex items-center justify-center">Clear</a>
            </div>
        </form>
    </div>

    <!-- Tab Switcher -->
    <div class="flex gap-2 border-b border-outline-variant">
        <button @click="tab = 'enrollments'; $nextTick(() => { document.querySelector('input[name=tab]').value = 'enrollments' })" :class="tab === 'enrollments' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface'" class="px-6 py-3 font-bold border-b-2 transition-colors">
            Full Course Enrollments
        </button>
        <button @click="tab = 'bookings'; $nextTick(() => { document.querySelector('input[name=tab]').value = 'bookings' })" :class="tab === 'bookings' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface'" class="px-6 py-3 font-bold border-b-2 transition-colors">
            Course Bookings & 1:1 Classes
        </button>
        <button @click="tab = 'demos'; $nextTick(() => { document.querySelector('input[name=tab]').value = 'demos' })" :class="tab === 'demos' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface'" class="px-6 py-3 font-bold border-b-2 transition-colors flex items-center gap-2">
            <span>Free Consultations & Trials</span>
            <span class="px-2 py-0.5 rounded-full text-xs font-extrabold bg-warning-container text-warning">{{ $campBookings->total() }}</span>
        </button>
    </div>

    <!-- Enrollments Table -->
    <div x-show="tab === 'enrollments'" class="bg-white rounded-2xl border overflow-hidden">
        <div class="overflow-x-auto w-full"><table class="w-full text-left">
            <thead class="bg-surface-container-low">
                <tr>
                    <th class="p-4 font-bold">Student</th>
                    <th class="p-4 font-bold">Course</th>
                    <th class="p-4 font-bold">Progress</th>
                    <th class="p-4 font-bold">Status</th>
                    <th class="p-4 font-bold">Date</th>
                    <th class="p-4 font-bold text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($enrollments as $e)
                <tr class="border-t">
                    <td class="p-4">{{ $e->user->name ?? '' }}<br><span class="text-xs text-on-surface-variant">{{ $e->user->email ?? '' }}</span></td>
                    <td class="p-4">{{ $e->course->title ?? '' }}</td>
                    <td class="p-4">{{ $e->progress }}%</td>
                    <td class="p-4"><span class="px-2 py-1 rounded-full text-xs font-bold bg-primary-container">{{ ucfirst($e->status) }}</span></td>
                    <td class="p-4 text-sm"><x-local-time :date="$e->created_at" format="date" /></td>
                    <td class="p-4 text-right">
                        <div class="flex flex-col items-end gap-2">
                            <form method="POST" action="{{ route('admin.enrollments.certificate.upload', $e) }}" enctype="multipart/form-data" class="flex items-center gap-2">
                                @csrf
                                <input type="file" name="certificate" accept=".pdf,.jpg,.jpeg,.png" required class="text-xs max-w-[140px]">
                                <button type="submit" class="text-primary text-xs font-bold whitespace-nowrap">Upload Cert</button>
                            </form>
                            @if($e->hasUploadedCertificate())
                                <span class="text-[10px] font-bold uppercase tracking-wider text-on-tertiary-container bg-tertiary-fixed px-2 py-0.5 rounded-full">Certificate uploaded</span>
                            @endif
                            @if($e->status !== 'cancelled')
                            <form method="POST" action="{{ route('admin.enrollments.cancel', $e) }}" class="inline" onsubmit="return confirm('Cancel this enrollment?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-error text-sm font-bold">Cancel</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="p-5 md:p-8 text-center text-on-surface-variant">No enrollments found matching your filters.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        @if($enrollments->hasPages())
        <div class="p-4">{{ $enrollments->links() }}</div>
        @endif
    </div>

    <!-- Course Bookings Table -->
    <div x-show="tab === 'bookings'" style="display: none;" class="bg-white rounded-2xl border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-surface-container-low">
                    <tr>
                        <th class="p-4 font-bold">Student</th>
                        <th class="p-4 font-bold">Course / Type</th>
                        <th class="p-4 font-bold">Schedule</th>
                        <th class="p-4 font-bold">Assign Tutor (Slot Availability)</th>
                        <th class="p-4 font-bold">Zoom Meeting Links</th>
                        <th class="p-4 font-bold">Status</th>
                        <th class="p-4 font-bold">Date Booked</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $b)
                    <tr class="border-t hover:bg-surface-container-low/60 transition-colors">
                        <td class="p-4">
                            <span class="font-bold text-on-surface">{{ $b->student->name ?? 'Student' }}</span><br>
                            <span class="text-xs text-on-surface-variant">{{ $b->student->email ?? '' }}</span>
                        </td>
                        <td class="p-4">
                            <span class="font-medium text-on-surface">{{ $b->course->title ?? 'Live Course' }}</span>
                            <div class="text-[11px] text-on-surface-variant mt-0.5 uppercase font-bold tracking-wider">{{ str_replace('_', ' ', $b->session_type) }}</div>
                        </td>
                        <td class="p-4 text-sm" x-data="{ editSchedule: false }">
                            @php $bs = $bookingsSchedule[$b->id] ?? ['classes' => 1, 'days' => [], 'times' => [], 'start_date' => date('Y-m-d'), 'timezone' => 'UTC']; @endphp
                            <div x-show="!editSchedule">
                                @if($b->scheduled_at)
                                    <div class="font-bold text-on-surface"><x-local-time :date="$b->scheduled_at" format="date" /></div>
                                    <div class="text-xs font-bold text-primary"><x-local-time :date="$b->scheduled_at" format="time" /></div>
                                    <div class="text-[10px] text-outline mt-0.5">Student opted: {{ \Carbon\Carbon::parse($b->scheduled_at)->setTimezone(\App\Services\TimezoneService::sanitizeTimezone($b->timezone))->format('h:i A') }} ({{ \App\Services\TimezoneService::label($b->timezone) }})</div>
                                @else
                                    <span class="text-outline">Not scheduled</span>
                                @endif
                                <div class="mt-1 text-[11px] font-bold text-on-surface">
                                    {{ $bs['classes'] }} {{ \Illuminate\Support\Str::plural('class', $bs['classes']) }}
                                    @if(!empty($bs['days']))
                                        <span class="text-outline">•</span>
                                        {{ collect($bs['days'])->map(fn ($d) => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'][$d])->implode(', ') }}
                                    @endif
                                </div>
                                <button @click="editSchedule = true" class="mt-2 text-[11px] font-bold text-primary hover:underline flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">edit_calendar</span> {{ $b->scheduled_at ? 'Edit' : 'Schedule' }}
                                </button>
                            </div>
                            <form x-show="editSchedule" style="display: none;" method="POST" action="{{ route('admin.bookings.update-schedule', $b->id) }}" class="flex flex-col gap-2 bg-surface-container-low p-3 rounded-xl border border-outline-variant mt-2">
                                @csrf
                                <label class="text-[10px] font-bold uppercase tracking-wide text-on-surface-variant">Number of Classes</label>
                                <input type="number" name="number_of_classes" min="1" max="48" value="{{ $bs['classes'] }}" class="w-full text-xs p-2 border rounded-lg">

                                <label class="text-[10px] font-bold uppercase tracking-wide text-on-surface-variant">Schedule Start Date</label>
                                <input type="date" name="schedule_start_date" value="{{ $bs['start_date'] }}" class="w-full text-xs p-2 border rounded-lg">

                                <label class="text-[10px] font-bold uppercase tracking-wide text-on-surface-variant">Timezone</label>
                                <select name="timezone" required data-current="{{ $bs['timezone'] }}" class="auto-timezone-select w-full text-xs p-2 border rounded-lg">
                                    @unless(\App\Services\TimezoneService::allTimezones()[$bs['timezone']] ?? null)
                                        <option value="{{ $bs['timezone'] }}" selected>{{ \App\Services\TimezoneService::label($bs['timezone']) }}</option>
                                    @endunless
                                    @foreach(\App\Services\TimezoneService::allTimezones() as $tzId => $tzLabel)
                                        <option value="{{ $tzId }}" @selected(($bs['timezone'] ?? 'UTC') === $tzId)>{{ $tzLabel }}</option>
                                    @endforeach
                                </select>

                                <label class="text-[10px] font-bold uppercase tracking-wide text-on-surface-variant">Weekly Days &amp; Times</label>
                                <div class="flex flex-col gap-1">
                                    @foreach([1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 0 => 'Sun'] as $dayValue => $dayLabel)
                                        @php $dayOn = in_array($dayValue, $bs['days'], true); @endphp
                                        <label class="flex items-center gap-2 text-xs" x-data="{ on: {{ $dayOn ? 'true' : 'false' }} }">
                                            <input type="checkbox" name="schedule_days[]" value="{{ $dayValue }}" x-model="on" class="w-3.5 h-3.5">
                                            <span class="w-8 font-bold text-on-surface">{{ $dayLabel }}</span>
                                            <input type="time" name="schedule_times[{{ $dayValue }}]" value="{{ $bs['times'][$dayValue] ?? '17:00' }}" :disabled="!on" class="text-xs p-1 border rounded-md">
                                        </label>
                                    @endforeach
                                </div>
                                <div class="flex gap-1 mt-1">
                                    <button type="submit" class="flex-1 bg-primary text-white text-[11px] py-1 rounded-lg font-bold">Save</button>
                                    <button type="button" @click="editSchedule = false" class="flex-1 bg-surface-container text-on-surface text-[11px] py-1 rounded-lg font-bold">Cancel</button>
                                </div>
                            </form>
                        </td>

                        <!-- Assign Tutor Column with Time-slot Availability -->
                        <td class="p-4">
                            <form method="POST" action="{{ route('admin.bookings.auto-assign-tutor', $b->id) }}" class="mb-2">
                                @csrf
                                <button type="submit" class="w-full text-xs font-bold px-2 py-1.5 rounded-lg bg-tertiary text-white">Auto-assign by skills</button>
                            </form>
                            <form method="POST" action="{{ route('admin.bookings.assign-tutor', $b->id) }}" class="flex flex-col gap-2">
                                @csrf
                                <input type="hidden" name="timezone" class="user-timezone-input" value="UTC">
                                <div class="flex items-center gap-2">
                                    <select name="tutor_id" required class="text-xs p-2.5 border border-outline rounded-xl bg-white font-medium max-w-[290px] outline-none focus:border-primary-fixed0 shadow-xs">
                                        <option value="">— Select Tutor —</option>
                                        @foreach($tutorsAvailability[$b->id] ?? [] as $item)
                                            <option value="{{ $item['tutor']->id }}" {{ $b->tutor_id == $item['tutor']->id ? 'selected' : '' }} class="{{ $item['is_occupied'] ? 'text-warning bg-warning-container font-bold' : 'text-tertiary bg-tertiary-fixed' }}">
                                                @if($item['is_occupied'])
                                                    ❌ [Occupied] {{ $item['tutor']->name }} — Busy with {{ $item['occupied_student'] }} ({{ $item['occupied_course'] }})
                                                @else
                                                    ✅ [Available] {{ $item['tutor']->name }} ({{ $item['tutor']->email }})
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="px-3.5 py-2 bg-primary text-white text-xs font-bold rounded-xl hover:bg-primary-container transition-colors shadow-sm whitespace-nowrap">
                                        {{ $b->tutor_id ? 'Reassign' : 'Assign' }}
                                    </button>
                                </div>
                            </form>
                            @if($b->tutor)
                                <div class="mt-1 text-[11px] text-on-tertiary-container font-bold flex items-center gap-1">
                                    <span class="material-symbols-outlined text-xs">check_circle</span> Assigned to {{ $b->tutor->name }}
                                </div>
                            @else
                                <div class="mt-1 text-[11px] text-warning font-medium">⚠️ No tutor assigned yet</div>
                            @endif
                        </td>
                        <!-- Zoom Links Column -->
                        <td class="p-4 text-xs space-y-1">
                            @if($b->zoom_join_url)
                                <div>
                                    <a href="{{ $b->zoom_join_url }}" target="_blank" class="inline-flex items-center gap-1 text-on-tertiary-container font-bold hover:underline bg-tertiary-fixed px-2 py-1 rounded-lg border border-tertiary-fixed-dim">
                                        <span class="material-symbols-outlined text-xs">videocam</span> Student Join Link
                                    </a>
                                </div>
                            @endif
                            @if($b->zoom_start_url)
                                <div>
                                    <a href="{{ $b->zoom_start_url }}" target="_blank" class="inline-flex items-center gap-1 text-primary font-bold hover:underline bg-primary-container/20 px-2 py-1 rounded-lg border border-primary/20">
                                        <span class="material-symbols-outlined text-xs">play_circle</span> Teacher Host Link
                                    </a>
                                </div>
                            @endif
                            @if(!$b->zoom_join_url && !$b->zoom_start_url)
                                <span class="text-outline italic text-xs">Auto-generates on tutor assignment</span>
                            @endif
                        </td>
                        <td class="p-4">
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $b->status === 'confirmed' ? 'bg-tertiary-fixed text-tertiary' : 'bg-surface-container-low text-on-surface' }}">
                                {{ ucfirst($b->status) }}
                            </span>
                        </td>
                        <td class="p-4 text-xs text-on-surface-variant"><x-local-time :date="$b->created_at" format="date" /></td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="p-5 md:p-8 text-center text-on-surface-variant font-medium">No bookings found matching your filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($bookings->hasPages())
        <div class="p-4">{{ $bookings->links() }}</div>
        @endif
    </div>

    <!-- Demo Classes & Applied Students Table -->
    <div x-show="tab === 'demos'" style="display: none;" class="bg-white rounded-2xl border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-surface-container-low">
                    <tr>
                        <th class="p-4 font-bold">Parent & Student</th>
                        <th class="p-4 font-bold">Consultation / Trial</th>
                        <th class="p-4 font-bold">Schedule</th>
                        <th class="p-4 font-bold">Assign Tutor (Slot Availability)</th>
                        <th class="p-4 font-bold">Zoom Links</th>
                        <th class="p-4 font-bold">Status</th>
                        <th class="p-4 font-bold">Date Applied</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($campBookings as $cb)
                    <tr class="border-t hover:bg-surface-container-low/60 transition-colors">
                        <td class="p-4">
                            <span class="font-bold text-on-surface">{{ $cb->child_name ? $cb->child_name . ' (Student)' : 'Applied Student' }}</span><br>
                            <span class="text-xs text-on-surface font-medium">Parent: {{ $cb->parent_name }}</span><br>
                            <span class="text-xs text-on-surface-variant">{{ $cb->email }} | {{ $cb->phone }}</span>
                        </td>
                        <td class="p-4">
                            <span class="font-bold text-on-surface">{{ $cb->camp_name }}</span>
                            <div class="text-xs text-on-surface-variant mt-0.5">Age: {{ $cb->age ?? 'N/A' }} | {{ $cb->country ?? 'Global' }}</div>
                        </td>
                        <td class="p-4 text-sm" x-data="{ editSchedule: false }">
                            <div x-show="!editSchedule">
                                @if($cb->scheduled_at)
                                    <div class="font-bold text-on-surface"><x-local-time :date="$cb->scheduled_at" format="date" /></div>
                                    <div class="text-xs font-bold text-primary"><x-local-time :date="$cb->scheduled_at" format="time" /></div>
                                    <div class="text-[10px] text-outline mt-0.5">Student opted: {{ \Carbon\Carbon::parse($cb->scheduled_at)->setTimezone(\App\Services\TimezoneService::sanitizeTimezone($cb->timezone))->format('h:i A') }} ({{ \App\Services\TimezoneService::label($cb->timezone) }})</div>
                                @else
                                    <span class="text-outline">Not scheduled</span>
                                @endif
                                <button @click="editSchedule = true" class="mt-2 text-[11px] font-bold text-primary hover:underline flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">edit_calendar</span> {{ $cb->scheduled_at ? 'Edit' : 'Schedule' }}
                                </button>
                            </div>
                            <form x-show="editSchedule" style="display: none;" method="POST" action="{{ route('admin.camp-bookings.update-schedule', $cb->id) }}" class="flex flex-col gap-2 bg-surface-container-low p-2 rounded-xl border border-outline-variant mt-2">
                                @csrf
                                <select name="timezone" required data-current="{{ $cb->timezone ?? '' }}" class="auto-timezone-select w-full text-xs p-2 border rounded-lg">
                                    @if(($cb->timezone ?? '') !== '' && !(\App\Services\TimezoneService::allTimezones()[$cb->timezone] ?? null))
                                        <option value="{{ $cb->timezone }}" selected>{{ \App\Services\TimezoneService::label($cb->timezone) }}</option>
                                    @else
                                        <option value="">— Select Timezone —</option>
                                    @endif
                                    @foreach(\App\Services\TimezoneService::allTimezones() as $tzId => $tzLabel)
                                        <option value="{{ $tzId }}" @selected(($cb->timezone ?? 'UTC') === $tzId)>{{ $tzLabel }}</option>
                                    @endforeach
                                </select>
                                <input type="datetime-local" name="scheduled_at" required class="w-full text-xs p-2 border rounded-lg" value="{{ $cb->scheduled_at ? \Carbon\Carbon::parse($cb->scheduled_at)->setTimezone(\App\Services\TimezoneService::sanitizeTimezone($cb->timezone))->format('Y-m-d\TH:i') : '' }}">
                                <div class="flex gap-1">
                                    <button type="submit" class="flex-1 bg-primary text-white text-[11px] py-1 rounded-lg font-bold">Save</button>
                                    <button type="button" @click="editSchedule = false" class="flex-1 bg-surface-container text-on-surface text-[11px] py-1 rounded-lg font-bold">Cancel</button>
                                </div>
                            </form>
                        </td>
                        <!-- Assign Tutor Column with Slot Availability -->
                        <td class="p-4">
                            <form method="POST" action="{{ route('admin.camp-bookings.assign-tutor', $cb->id) }}" class="flex flex-col gap-2">
                                @csrf
                                <input type="hidden" name="timezone" class="user-timezone-input" value="UTC">
                                <div class="flex items-center gap-2">
                                    <select name="tutor_id" required class="text-xs p-2.5 border border-outline rounded-xl bg-white font-medium max-w-[290px] outline-none focus:border-primary-fixed0 shadow-xs">
                                        <option value="">— Select Tutor —</option>
                                        @foreach($campTutorsAvailability[$cb->id] ?? [] as $item)
                                            <option value="{{ $item['tutor']->id }}" {{ $cb->tutor_id == $item['tutor']->id ? 'selected' : '' }} class="{{ $item['is_occupied'] ? 'text-warning bg-warning-container font-bold' : 'text-tertiary bg-tertiary-fixed' }}">
                                                @if($item['is_occupied'])
                                                    ❌ [Occupied] {{ $item['tutor']->name }} — Busy with {{ $item['occupied_student'] }} ({{ $item['occupied_course'] }})
                                                @else
                                                    ✅ [Available] {{ $item['tutor']->name }} ({{ $item['tutor']->email }})
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="px-3.5 py-2 bg-primary text-white text-xs font-bold rounded-xl hover:bg-primary-container transition-colors shadow-sm whitespace-nowrap">
                                        {{ $cb->tutor_id ? 'Reassign' : 'Assign' }}
                                    </button>
                                </div>
                            </form>
                            @if($cb->tutor)
                                <div class="mt-1 text-[11px] text-on-tertiary-container font-bold flex items-center gap-1">
                                    <span class="material-symbols-outlined text-xs">check_circle</span> Assigned to {{ $cb->tutor->name }}
                                </div>
                            @else
                                <div class="mt-1 text-[11px] text-warning font-medium">⚠️ No tutor assigned yet</div>
                            @endif
                        </td>
                        <!-- Zoom Links -->
                        <td class="p-4 text-xs space-y-1">
                            @if($cb->zoom_join_url)
                                <div>
                                    <a href="{{ $cb->zoom_join_url }}" target="_blank" class="inline-flex items-center gap-1 text-on-tertiary-container font-bold hover:underline bg-tertiary-fixed px-2 py-1 rounded-lg border border-tertiary-fixed-dim">
                                        <span class="material-symbols-outlined text-xs">videocam</span> Student Join Link
                                    </a>
                                </div>
                            @endif
                            @if($cb->zoom_start_url)
                                <div>
                                    <a href="{{ $cb->zoom_start_url }}" target="_blank" class="inline-flex items-center gap-1 text-primary font-bold hover:underline bg-primary-container/20 px-2 py-1 rounded-lg border border-primary/20">
                                        <span class="material-symbols-outlined text-xs">play_circle</span> Teacher Host Link
                                    </a>
                                </div>
                            @endif
                            @if(!$cb->zoom_join_url && !$cb->zoom_start_url)
                                <span class="text-outline italic text-xs">Auto-generates on tutor assignment</span>
                            @endif
                        </td>
                        <td class="p-4">
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ ($cb->payment_status === 'paid' || $cb->tutor_id) ? 'bg-tertiary-fixed text-tertiary' : 'bg-warning-container text-warning' }}">
                                {{ $cb->tutor_id ? 'Assigned' : 'Pending Assignment' }}
                            </span>
                        </td>
                        <td class="p-4 text-xs text-on-surface-variant"><x-local-time :date="$cb->created_at" format="date" /></td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="p-5 md:p-8 text-center text-on-surface-variant font-medium">No demo bookings or applied students found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($campBookings->hasPages())
        <div class="p-4">{{ $campBookings->links() }}</div>
        @endif
    </div>

</div>

<script>
// Convert all scheduled times to admin's local browser timezone
document.addEventListener('DOMContentLoaded', function () {
    const adminTz = Intl.DateTimeFormat().resolvedOptions().timeZone;

    // Populate all user timezone inputs
    document.querySelectorAll('.user-timezone-input').forEach(function (el) {
        el.value = adminTz;
    });

    // Auto-detect the browser timezone wherever no explicit timezone is stored.
    document.querySelectorAll('.auto-timezone-select').forEach(function (el) {
        const current = el.getAttribute('data-current');
        if (!current || current === 'UTC') {
            const match = Array.from(el.options).find(function (o) { return o.value === adminTz; });
            if (match) {
                el.value = adminTz;
            }
        }
    });

    document.querySelectorAll('.admin-local-date[data-utc]').forEach(function (el) {
        const utc = el.getAttribute('data-utc');
        if (!utc) return;
        try {
            const dt = new Date(utc);
            el.textContent = dt.toLocaleDateString('en-GB', {
                year: 'numeric', month: 'short', day: 'numeric',
                timeZone: adminTz
            });
        } catch (e) {}
    });

    document.querySelectorAll('.admin-local-time[data-utc]').forEach(function (el) {
        const utc = el.getAttribute('data-utc');
        if (!utc) return;
        try {
            const dt = new Date(utc);
            const timeStr = dt.toLocaleTimeString('en-US', {
                hour: '2-digit', minute: '2-digit', hour12: true,
                timeZone: adminTz
            });
            el.textContent = timeStr + ' (' + adminTz + ')';
        } catch (e) {}
    });
});
</script>
@endsection
