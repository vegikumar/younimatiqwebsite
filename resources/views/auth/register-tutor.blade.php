@extends('layouts.app')

@section('title', 'Tutor Registration')

@section('content')
@php
    $verticals = ['Coding','Robotics/Arduino','AI','Game Design','Art','Math','English/ELA','Science'];
    $ageBands = ['5-7','8-10','11-13','14-16'];
    $days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
    $oldSchedule = collect(old('preferred_schedule', []));
    $parsedDays = collect($days)->map(function ($day) use ($oldSchedule) {
        $match = $oldSchedule->first(fn ($slot) => str_starts_with((string) $slot, $day.' '));
        $from = '09:00';
        $to = '17:00';
        $enabled = false;
        if (is_string($match) && preg_match('/^'.preg_quote($day, '/').'\s+(\d{2}:\d{2})-(\d{2}:\d{2})$/', $match, $m)) {
            $enabled = true;
            $from = $m[1];
            $to = $m[2];
        }

        return [
            'day' => $day,
            'enabled' => $enabled,
            'from' => $from,
            'to' => $to,
        ];
    })->values();
@endphp
<div class="min-h-[80vh] bg-surface py-10 px-4" x-data="tutorRegister()">
    <div class="max-w-4xl mx-auto bg-white rounded-[2rem] border border-outline-variant/30 shadow-xl p-5 md:p-10">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-extrabold text-on-surface">Tutor Registration</h1>
            <p class="text-on-surface-variant mt-2">Complete all steps so we can review and approve your profile.</p>
            <div class="flex justify-center gap-2 mt-6">
                <template x-for="s in [1,2,3]" :key="s">
                    <div class="h-2 w-16 rounded-full" :class="step >= s ? 'bg-primary' : 'bg-surface-container-high'"></div>
                </template>
            </div>
            <p class="text-xs text-on-surface-variant mt-2">Step <span x-text="step"></span> of 3</p>
        </div>

        @if($errors->any())
            <div class="bg-error-container text-on-error-container p-4 rounded-xl text-sm font-bold mb-6">
                <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form action="{{ route('register.tutor') }}" method="POST" enctype="multipart/form-data"
              @submit="if (step === 3 && !validateAvailability()) { $event.preventDefault(); submitting = false; return; } submitting = true"
              class="space-y-6">
            @csrf
            <input type="hidden" name="timezone" x-model="timezone">
            <template x-for="slot in selectedSlots" :key="slot">
                <input type="hidden" name="preferred_schedule[]" :value="slot">
            </template>

            {{-- Step 1: basic info + video/credentials/compensation (former step 4) --}}
            <div x-show="step===1" class="space-y-6">
                <h2 class="font-bold text-xl">Basic info, credentials & compensation</h2>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold mb-1">Full name *</label>
                        <input name="name" required value="{{ old('name') }}" class="w-full p-3 rounded-xl bg-surface-container-low border border-outline-variant">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">Email *</label>
                        <input type="email" name="email" required value="{{ old('email') }}" class="w-full p-3 rounded-xl bg-surface-container-low border border-outline-variant">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">Phone (with country code) *</label>
                        <input name="phone" required placeholder="+1..." value="{{ old('phone') }}" class="w-full p-3 rounded-xl bg-surface-container-low border border-outline-variant">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">City / Country *</label>
                        <input name="city_country" required x-model="cityCountry" class="w-full p-3 rounded-xl bg-surface-container-low border border-outline-variant">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">Password *</label>
                        <input type="password" name="password" required minlength="8" class="w-full p-3 rounded-xl bg-surface-container-low border border-outline-variant">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">Confirm password *</label>
                        <input type="password" name="password_confirmation" required class="w-full p-3 rounded-xl bg-surface-container-low border border-outline-variant">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">Profile photo *</label>
                        <input type="file" name="profile_photo" accept="image/*" required class="w-full text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">LinkedIn (optional)</label>
                        <input type="url" name="linkedin_url" value="{{ old('linkedin_url') }}" class="w-full p-3 rounded-xl bg-surface-container-low border border-outline-variant">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold mb-1">How did you hear about us?</label>
                        <input name="source" value="{{ old('source') }}" class="w-full p-3 rounded-xl bg-surface-container-low border border-outline-variant">
                    </div>
                </div>

                <div class="space-y-2 text-sm">
                    <label class="flex gap-2 items-start"><input type="checkbox" name="is_18_plus" value="1" required> I am 18 years or older *</label>
                    <label class="flex gap-2 items-start"><input type="checkbox" name="background_check_consent" value="1" required> I consent to a background check *</label>
                    <label class="flex gap-2 items-start"><input type="checkbox" name="child_safety_consent" value="1" required> I agree to child safety policies *</label>
                </div>

                <hr class="border-outline-variant/40">

                <div class="space-y-4">
                    <h3 class="font-bold text-lg">Video, credentials & compensation</h3>
                    <div>
                        <label class="block text-sm font-bold mb-1">Intro video (max 90s, MP4/WebM/MOV, 50MB) *</label>
                        <input type="file" name="intro_video" accept="video/mp4,video/webm,video/quicktime" required class="w-full text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">Certifications / degree (at least 1) *</label>
                        <input type="file" name="certifications[]" accept=".pdf,image/*" multiple required class="w-full text-sm">
                    </div>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold mb-1">Currency *</label>
                            <select name="currency" required class="w-full p-3 rounded-xl bg-surface-container-low border border-outline-variant" x-model="currency">
                                <option>USD</option><option>EUR</option><option>GBP</option><option>CAD</option><option>AUD</option><option>INR</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold mb-1">Expected hourly rate *</label>
                            <input name="expected_hourly_rate" required placeholder="15-20/hour" value="{{ old('expected_hourly_rate') }}" class="w-full p-3 rounded-xl bg-surface-container-low border border-outline-variant">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold mb-1">References (optional)</label>
                            <textarea name="references" rows="3" class="w-full p-3 rounded-xl bg-surface-container-low border border-outline-variant">{{ old('references') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Step 2 --}}
            <div x-show="step===2" class="space-y-4">
                <h2 class="font-bold text-xl">Teaching profile & communication</h2>
                <div>
                    <p class="text-sm font-bold mb-2">Subject verticals * (select at least 1)</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($verticals as $v)
                            <label class="px-3 py-2 rounded-full border border-outline-variant text-sm cursor-pointer has-[:checked]:bg-primary has-[:checked]:text-white">
                                <input type="checkbox" name="subject_verticals[]" value="{{ $v }}" class="sr-only" @checked(collect(old('subject_verticals',[]))->contains($v))> {{ $v }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <p class="text-sm font-bold mb-2">Preferred age bands *</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($ageBands as $b)
                            <label class="px-3 py-2 rounded-full border border-outline-variant text-sm cursor-pointer has-[:checked]:bg-secondary has-[:checked]:text-white">
                                <input type="checkbox" name="preferred_age_bands[]" value="{{ $b }}" class="sr-only" @checked(collect(old('preferred_age_bands',[]))->contains($b))> {{ $b }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold mb-1">Teaching experience *</label>
                        <input name="teaching_experience" required value="{{ old('teaching_experience') }}" class="w-full p-3 rounded-xl bg-surface-container-low border border-outline-variant">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">Countries taught</label>
                        <input name="countries_taught" value="{{ old('countries_taught') }}" class="w-full p-3 rounded-xl bg-surface-container-low border border-outline-variant">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">Platforms taught</label>
                        <input name="platforms_taught" value="{{ old('platforms_taught') }}" class="w-full p-3 rounded-xl bg-surface-container-low border border-outline-variant">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">Higher education *</label>
                        <input name="higher_education" required value="{{ old('higher_education') }}" class="w-full p-3 rounded-xl bg-surface-container-low border border-outline-variant">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">Institution *</label>
                        <input name="higher_institution" required value="{{ old('higher_institution') }}" class="w-full p-3 rounded-xl bg-surface-container-low border border-outline-variant">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">English fluency *</label>
                        <select name="english_fluency" required class="w-full p-3 rounded-xl bg-surface-container-low border border-outline-variant">
                            <option value="5" @selected(old('english_fluency') == '5')>5 — Native/Bilingual</option>
                            <option value="4" @selected(old('english_fluency') == '4')>4 — Full Professional</option>
                            <option value="3" @selected(old('english_fluency', '3') == '3')>3 — Professional Working</option>
                            <option value="2" @selected(old('english_fluency') == '2')>2 — Limited Working</option>
                            <option value="1" @selected(old('english_fluency') == '1')>1 — Elementary</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">Additional languages</label>
                        <input name="additional_languages" value="{{ old('additional_languages') }}" class="w-full p-3 rounded-xl bg-surface-container-low border border-outline-variant">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold mb-1">About yourself *</label>
                        <textarea name="about_yourself" required minlength="10" rows="4" class="w-full p-3 rounded-xl bg-surface-container-low border border-outline-variant">{{ old('about_yourself') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Step 3 --}}
            <div x-show="step===3" class="space-y-4">
                <h2 class="font-bold text-xl">Availability & tech setup</h2>
                <div>
                    <label class="block text-sm font-bold mb-1">Weekly commitment *</label>
                    <select name="availability_commitment" required class="w-full p-3 rounded-xl bg-surface-container-low border border-outline-variant">
                        <option @selected(old('availability_commitment') === 'Under 10 hrs/wk')>Under 10 hrs/wk</option>
                        <option @selected(old('availability_commitment', '10-20 hrs/wk') === '10-20 hrs/wk')>10-20 hrs/wk</option>
                        <option @selected(old('availability_commitment') === '20-40 hrs/wk')>20-40 hrs/wk</option>
                    </select>
                </div>

                <div>
                    <p class="text-sm font-bold mb-2">Weekly availability * (set From → To for each day you can teach)</p>
                    <p class="text-xs text-on-surface-variant mb-3">Times are in your local timezone (<span x-text="timezone"></span>). Enable a day, then pick start and end time.</p>
                    <p class="text-xs text-on-surface-variant mb-3"><span x-text="selectedSlots.length"></span> day(s) selected</p>
                    <div class="space-y-3">
                        @foreach($parsedDays as $index => $row)
                            <div class="grid grid-cols-1 sm:grid-cols-[110px_1fr_1fr] gap-3 items-center p-3 rounded-xl border border-outline-variant bg-surface-container-low/40">
                                <label class="inline-flex items-center gap-2 font-bold text-sm">
                                    <input type="checkbox" class="accent-primary" x-model="dayRows[{{ $index }}].enabled">
                                    <span>{{ $row['day'] }}</span>
                                </label>
                                <div>
                                    <label class="block text-xs font-bold mb-1 text-on-surface-variant">From</label>
                                    <input type="time" class="w-full p-2.5 rounded-xl bg-white border border-outline-variant"
                                           x-model="dayRows[{{ $index }}].from"
                                           :disabled="!dayRows[{{ $index }}].enabled">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold mb-1 text-on-surface-variant">To</label>
                                    <input type="time" class="w-full p-2.5 rounded-xl bg-white border border-outline-variant"
                                           x-model="dayRows[{{ $index }}].to"
                                           :disabled="!dayRows[{{ $index }}].enabled">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold mb-1">Earliest start date *</label>
                    <input type="date" name="start_date" required min="{{ now()->toDateString() }}" value="{{ old('start_date', now()->toDateString()) }}" class="w-full p-3 rounded-xl bg-surface-container-low border border-outline-variant">
                </div>
                <div class="space-y-2 text-sm">
                    <label class="flex gap-2"><input type="checkbox" name="has_laptop_camera" value="1" required> I have a laptop with camera *</label>
                    <label class="flex gap-2"><input type="checkbox" name="has_stable_internet" value="1" required> I have stable internet *</label>
                    <label class="flex gap-2"><input type="checkbox" name="has_quiet_workspace" value="1" required> Quiet, well-lit workspace *</label>
                    <label class="flex gap-2"><input type="checkbox" name="comfort_with_zoom" value="1" required> Comfortable with Zoom/Meet screen-share *</label>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-1">Internet speed screenshot *</label>
                    <input type="file" name="internet_speed_screenshot" accept="image/*,application/pdf" required class="w-full text-sm">
                </div>
            </div>

            <div class="flex justify-between pt-4 border-t border-outline-variant/30">
                <button type="button" class="btn-secondary px-5 py-2 rounded-xl font-bold" x-show="step>1" @click="step--" :disabled="submitting">Back</button>
                <div class="ml-auto flex gap-3">
                    <button type="button" class="btn-primary px-5 py-2 rounded-xl font-bold text-white bg-primary" x-show="step<3" @click="nextStep()">Continue</button>
                    <button type="submit" class="btn-primary px-5 py-2 rounded-xl font-bold text-white bg-primary" x-show="step===3" :disabled="submitting">
                        <span x-text="submitting ? 'Uploading… Please wait' : 'Submit registration'"></span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
function tutorRegister() {
    return {
        step: 1,
        submitting: false,
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC',
        cityCountry: @json(old('city_country', '')),
        currency: @json(old('currency', 'USD')),
        dayRows: @json($parsedDays),
        get selectedSlots() {
            return this.dayRows
                .filter((row) => row.enabled && row.from && row.to && row.from < row.to)
                .map((row) => `${row.day} ${row.from}-${row.to}`);
        },
        validateAvailability() {
            const valid = this.dayRows.filter((row) => row.enabled);
            if (valid.length < 1) {
                alert('Please enable at least one day and set From / To times.');
                return false;
            }
            for (const row of valid) {
                if (!row.from || !row.to) {
                    alert(`Please set From and To times for ${row.day}.`);
                    return false;
                }
                if (row.from >= row.to) {
                    alert(`For ${row.day}, From time must be earlier than To time.`);
                    return false;
                }
            }
            return true;
        },
        nextStep() {
            if (this.step === 3 && !this.validateAvailability()) {
                return;
            }
            this.step++;
        },
        init() {
            fetch('https://ipapi.co/json/')
                .then(r => r.json())
                .then(d => {
                    if (!this.cityCountry && d.city && d.country_name) {
                        this.cityCountry = d.city + ', ' + d.country_name;
                    }
                    if (d.timezone) this.timezone = d.timezone;
                    if (d.currency && !@json(old('currency'))) this.currency = d.currency;
                }).catch(() => {});
        }
    }
}
</script>
@endsection
