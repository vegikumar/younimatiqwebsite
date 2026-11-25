@extends('layouts.dashboard')

@section('title', 'Edit · '.$automation->name)

@section('content')
@php
    $selectedDays = collect(old('days_of_week', $automation->days_of_week ?? [1,3,5]))->map(fn($d)=>(int)$d)->all();
    $selectedAudiences = collect(old('audiences', $automation->audiences ?? []))->all();
    $selectedCourses = collect(old('course_ids', $automation->course_ids ?? []))->map(fn($d)=>(int)$d)->all();
    $courseSource = old('course_source', $automation->course_source ?? 'top');
@endphp

<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.email-automations.index') }}" class="text-sm font-bold text-primary hover:underline">← All automations</a>
        <form method="POST" action="{{ route('admin.email-automations.destroy', $automation) }}" onsubmit="return confirm('Delete “{{ $automation->name }}”?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-sm font-bold text-error">Delete this automation</button>
        </form>
    </div>

    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden">
        <h1 class="font-headline-xl text-headline-xl text-on-surface mb-2">{{ $automation->name }}</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant">Edit copy, schedule, audiences, and featured classes for this campaign.</p>
        <div class="mt-4 flex flex-wrap gap-3 text-sm">
            <span class="px-3 py-1 rounded-full font-bold {{ $automation->is_enabled ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container text-on-surface-variant' }}">
                {{ $automation->is_enabled ? 'Automation ON' : 'Automation OFF' }}
            </span>
            <span class="px-3 py-1 rounded-full bg-surface-container-low text-on-surface-variant font-medium">
                Audience size: {{ number_format($recipientCount) }}
            </span>
            @if($nextSendAt)
                <span class="px-3 py-1 rounded-full bg-primary/10 text-primary font-medium">
                    Next auto-send: {{ $nextSendAt->timezone($automation->timezone ?: 'Asia/Kolkata')->format('D, M j · g:i A T') }}
                </span>
            @endif
            @if($automation->last_sent_at)
                <span class="px-3 py-1 rounded-full bg-surface-container-low text-on-surface-variant font-medium">
                    Last send: <x-local-time :date="$automation->last_sent_at" format="datetime-short" />
                    ({{ $automation->last_sent_count }} queued)
                </span>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-secondary-container text-on-secondary-container font-medium">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 rounded-xl bg-error-container text-on-error-container font-medium">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-6 p-4 rounded-xl bg-error-container text-on-error-container">
            <ul class="list-disc pl-5 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-10">
        <div class="xl:col-span-2 space-y-6">
            <form method="POST" action="{{ route('admin.email-automations.update', $automation) }}" enctype="multipart/form-data" class="clay-card p-5 md:p-8 space-y-6">
                @csrf
                @method('PUT')

                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <label class="block text-sm font-bold mb-2">Campaign name</label>
                        <input type="text" name="name" value="{{ old('name', $automation->name) }}" required class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary">
                    </div>
                    <label class="inline-flex items-center gap-2 cursor-pointer select-none mt-8">
                        <input type="checkbox" name="is_enabled" value="1" class="w-5 h-5 accent-primary" {{ old('is_enabled', $automation->is_enabled) ? 'checked' : '' }}>
                        <span class="font-bold text-sm">Enable auto-send</span>
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold mb-2">Email subject</label>
                        <input type="text" name="subject" value="{{ old('subject', $automation->subject) }}" required class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Headline inside email</label>
                        <input type="text" name="heading" value="{{ old('heading', $automation->heading) }}" required class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">From name</label>
                        <input type="text" name="from_name" value="{{ old('from_name', $automation->from_name) }}" required class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" placeholder="Amy from Younimatiq">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold mb-2">Intro line</label>
                        <textarea name="intro_text" rows="2" required class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary">{{ old('intro_text', $automation->intro_text) }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Button text</label>
                        <input type="text" name="cta_text" value="{{ old('cta_text', $automation->cta_text) }}" required class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Button URL</label>
                        <input type="url" name="cta_url" value="{{ old('cta_url', $automation->cta_url) }}" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" placeholder="{{ url('/courses') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Sign-off name</label>
                        <input type="text" name="sign_off_name" value="{{ old('sign_off_name', $automation->sign_off_name) }}" required class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Reply-to email</label>
                        <input type="email" name="reply_to" value="{{ old('reply_to', $automation->reply_to) }}" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" placeholder="support@younimatiq.com">
                    </div>
                </div>

                <hr class="border-outline-variant">

                <div class="space-y-3">
                    <h3 class="font-bold">Custom HTML email theme</h3>
                    <p class="text-xs text-on-surface-variant">Placeholders: <code>@{{subject}}</code>, <code>@{{heading}}</code>, <code>@{{intro_text}}</code>, <code>@{{cta_text}}</code>, <code>@{{cta_url}}</code>, <code>@{{courses_block}}</code>, <code>@{{unsubscribe_url}}</code>, <code>@{{recipient_name}}</code>, <code>@{{sign_off_name}}</code>, <code>@{{logo_url}}</code>.</p>
                    <input type="file" name="theme_file" accept=".html,.htm,text/html" class="w-full text-sm">
                    <textarea name="theme_html" rows="8" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl font-mono text-xs" placeholder="Paste full HTML theme…">{{ old('theme_html', $automation->theme_html) }}</textarea>
                    <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="clear_theme" value="1"> Clear custom theme</label>
                </div>

                <hr class="border-outline-variant">

                <div>
                    <h3 class="font-bold mb-3">Schedule</h3>
                    <div class="flex flex-wrap gap-3 mb-4">
                        @foreach($dayLabels as $value => $label)
                            <label class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-surface-container-low border border-outline-variant cursor-pointer">
                                <input type="checkbox" name="days_of_week[]" value="{{ $value }}" {{ in_array($value, $selectedDays, true) ? 'checked' : '' }}>
                                <span class="text-sm font-medium">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold mb-2">Send time</label>
                            <input type="time" name="send_time" value="{{ old('send_time', $automation->send_time ?: '10:00') }}" required class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-bold mb-2">Timezone</label>
                            <select name="timezone" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary">
                                @foreach(['Asia/Kolkata','America/New_York','America/Los_Angeles','Europe/London','Australia/Sydney','UTC'] as $tz)
                                    <option value="{{ $tz }}" @selected(old('timezone', $automation->timezone) === $tz)>{{ $tz }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <hr class="border-outline-variant">

                <div>
                    <h3 class="font-bold mb-3">Who receives this email</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <label class="flex items-start gap-3 p-3 rounded-xl bg-surface-container-low border border-outline-variant cursor-pointer">
                            <input type="checkbox" name="audiences[]" value="students" class="mt-1" {{ in_array('students', $selectedAudiences, true) ? 'checked' : '' }}>
                            <span><span class="font-bold block text-sm">All student/parent accounts</span><span class="text-xs text-on-surface-variant">Active users with role = student</span></span>
                        </label>
                        <label class="flex items-start gap-3 p-3 rounded-xl bg-surface-container-low border border-outline-variant cursor-pointer">
                            <input type="checkbox" name="audiences[]" value="enrolled" class="mt-1" {{ in_array('enrolled', $selectedAudiences, true) ? 'checked' : '' }}>
                            <span><span class="font-bold block text-sm">Currently enrolled families</span><span class="text-xs text-on-surface-variant">Users with an active enrollment</span></span>
                        </label>
                        <label class="flex items-start gap-3 p-3 rounded-xl bg-surface-container-low border border-outline-variant cursor-pointer">
                            <input type="checkbox" name="audiences[]" value="newsletter" class="mt-1" {{ in_array('newsletter', $selectedAudiences, true) ? 'checked' : '' }}>
                            <span><span class="font-bold block text-sm">Newsletter subscribers</span><span class="text-xs text-on-surface-variant">Site newsletter emails</span></span>
                        </label>
                        <label class="flex items-start gap-3 p-3 rounded-xl bg-surface-container-low border border-outline-variant cursor-pointer">
                            <input type="checkbox" name="audiences[]" value="camp_bookings" class="mt-1" {{ in_array('camp_bookings', $selectedAudiences, true) ? 'checked' : '' }}>
                            <span><span class="font-bold block text-sm">Trial / camp booking emails</span><span class="text-xs text-on-surface-variant">Parent emails from bookings</span></span>
                        </label>
                    </div>
                    <p class="text-xs text-on-surface-variant mt-3">Estimated unique recipients: <strong>{{ number_format($recipientCount) }}</strong></p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold mb-2">Always include these emails</label>
                        <textarea name="include_emails" rows="3" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" placeholder="vip@example.com">{{ old('include_emails', $automation->include_emails) }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Always exclude these emails</label>
                        <textarea name="exclude_emails" rows="3" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" placeholder="one@example.com">{{ old('exclude_emails', $automation->exclude_emails) }}</textarea>
                    </div>
                </div>

                <hr class="border-outline-variant">

                <div>
                    <h3 class="font-bold mb-3">Which classes to feature</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-bold mb-2">Course source</label>
                            <select name="course_source" id="course_source" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary">
                                <option value="top" @selected($courseSource === 'top')>Top / featured courses</option>
                                <option value="recent" @selected($courseSource === 'recent')>Recently published</option>
                                <option value="manual" @selected($courseSource === 'manual')>Manual pick below</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold mb-2">Max classes in email</label>
                            <input type="number" name="max_courses" min="1" max="12" value="{{ old('max_courses', $automation->max_courses) }}" required class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-bold mb-2">Skip people emailed in last N days</label>
                            <input type="number" name="skip_recent_days" min="0" max="30" value="{{ old('skip_recent_days', $automation->skip_recent_days ?? 2) }}" required class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-bold mb-2">UTM campaign tag</label>
                            <input type="text" name="utm_campaign" value="{{ old('utm_campaign', $automation->utm_campaign ?: 'email_campaign') }}" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary">
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-4 mb-4">
                        <label class="inline-flex items-center gap-2 text-sm font-medium">
                            <input type="checkbox" name="exclude_camps" value="1" {{ old('exclude_camps', $automation->exclude_camps ?? true) ? 'checked' : '' }}>
                            Exclude camp-only courses
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm font-medium">
                            <input type="checkbox" name="notify_admin_on_send" value="1" {{ old('notify_admin_on_send', $automation->notify_admin_on_send ?? true) ? 'checked' : '' }}>
                            Email admin when a full send is queued
                        </label>
                    </div>
                    <div id="manual_courses_wrap" class="{{ $courseSource === 'manual' ? '' : 'hidden' }}">
                        <label class="block text-sm font-bold mb-2">Pick courses</label>
                        <select name="course_ids[]" multiple size="10" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary">
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" {{ in_array($course->id, $selectedCourses, true) ? 'selected' : '' }}>
                                    {{ $course->title }}{{ $course->is_top_course ? ' ★' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 pt-2">
                    <button type="submit" class="btn-primary">Save settings</button>
                    <a href="{{ route('admin.email-automations.preview', $automation) }}" target="_blank" class="px-4 py-2 rounded-xl border border-outline-variant font-bold text-sm hover:border-primary">Preview email</a>
                </div>
            </form>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <form method="POST" action="{{ route('admin.email-automations.test', $automation) }}" class="clay-card p-5 md:p-8 space-y-4">
                    @csrf
                    <h2 class="font-headline-md font-bold">Send test email</h2>
                    <p class="text-sm text-on-surface-variant">Uses saved settings. Does not mark the scheduled send as done.</p>
                    <input type="email" name="test_email" required placeholder="younimatiq@gmail.com" value="younimatiq@gmail.com" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary">
                    <button type="submit" class="btn-primary btn-block">Send test</button>
                </form>

                <form method="POST" action="{{ route('admin.email-automations.send-now', $automation) }}" class="clay-card p-5 md:p-8 space-y-4" onsubmit="return confirm('Queue this campaign to {{ number_format($recipientCount) }} recipients now?');">
                    @csrf
                    <h2 class="font-headline-md font-bold">Send now</h2>
                    <p class="text-sm text-on-surface-variant">Immediately queues this digest to its audiences.</p>
                    <button type="submit" class="w-full px-4 py-3 rounded-xl bg-primary text-white font-bold">Queue full send</button>
                </form>
            </div>

            <div class="clay-card p-5 md:p-8 space-y-4" x-data="{
                subject: @js(old('subject', '')),
                body: @js(old('body', '')),
                preview() {
                    const q = new URLSearchParams({ subject: this.subject || '', body: this.body || '' });
                    window.open(@js(route('admin.email-automations.custom.preview', $automation)) + '?' + q.toString(), '_blank');
                }
            }">
                <h2 class="font-headline-md font-bold flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">edit_note</span>
                    Custom one-off email
                </h2>
                <p class="text-sm text-on-surface-variant">Uses this automation’s audiences and include/exclude lists.</p>
                <form method="POST" action="{{ route('admin.email-automations.custom.send', $automation) }}" class="space-y-4"
                      @submit="if ($event.submitter?.value === 'audience') return confirm('Queue this custom email to {{ number_format($recipientCount) }} recipients now?');">
                    @csrf
                    <div>
                        <label class="block text-sm font-bold mb-2">Subject</label>
                        <input type="text" name="subject" x-model="subject" required maxlength="180" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Message body</label>
                        <textarea name="body" x-model="body" rows="8" required maxlength="50000" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary font-mono text-sm" placeholder="Plain text or HTML"></textarea>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-end">
                        <div class="flex-1">
                            <label class="block text-sm font-bold mb-2">Test inbox</label>
                            <input type="email" name="test_email" value="younimatiq@gmail.com" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary">
                        </div>
                        <button type="button" @click="preview()" class="px-4 py-3 rounded-xl border border-outline-variant font-bold text-sm">Preview</button>
                        <button type="submit" name="mode" value="test" class="px-4 py-3 rounded-xl border border-outline-variant font-bold text-sm">Send test</button>
                        <button type="submit" name="mode" value="audience" class="btn-primary">Send to audiences</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="space-y-6">
            <div class="clay-card p-5 md:p-8">
                <h2 class="font-headline-md font-bold mb-4">Featured in next send</h2>
                <ul class="space-y-3">
                    @forelse($previewCourses as $course)
                        <li class="border-b border-outline-variant/50 pb-3">
                            <a href="{{ $course['url'] }}" target="_blank" class="font-bold text-primary text-sm">{{ $course['title'] }}</a>
                            <div class="text-xs text-on-surface-variant">{{ $course['age_label'] }}</div>
                        </li>
                    @empty
                        <li class="text-sm text-on-surface-variant">No published active courses found.</li>
                    @endforelse
                </ul>
            </div>

            <div class="clay-card p-5 md:p-8">
                <h2 class="font-headline-md font-bold mb-4">Recent runs</h2>
                <div class="space-y-2 max-h-72 overflow-y-auto text-sm">
                    @forelse($recentRuns as $run)
                        <div class="p-3 rounded-xl bg-surface-container-low">
                            <div class="flex justify-between gap-2">
                                <span class="font-bold">{{ ucfirst($run->trigger) }} · {{ ucfirst($run->status) }}</span>
                                <span class="text-xs text-on-surface-variant"><x-local-time :date="$run->created_at" format="datetime-short" /></span>
                            </div>
                            <div class="text-xs text-on-surface-variant mt-1">
                                Recipients {{ $run->recipient_count }} · Queued {{ $run->sent_count }}
                            </div>
                        </div>
                    @empty
                        <p class="text-on-surface-variant">No sends yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('course_source')?.addEventListener('change', function () {
    document.getElementById('manual_courses_wrap')?.classList.toggle('hidden', this.value !== 'manual');
});
</script>
@endsection
