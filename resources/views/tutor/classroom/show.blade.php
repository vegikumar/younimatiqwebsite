@extends('layouts.dashboard')

@section('title', $course->title . ' - Classroom Hub')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12" x-data="{ activeTab: 'feed', replyParentId: null }">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <span class="px-3 py-1 bg-primary-container text-on-primary-container rounded-full text-xs font-bold uppercase tracking-wider mb-2 inline-block">
                    Dedicated Classroom Hub
                </span>
                <h1 class="font-headline-xl text-headline-xl text-on-surface mb-2">{{ $course->title }}</h1>
                <p class="font-body-lg text-body-lg text-on-surface-variant">Classroom announcements, student discussion feed with @mentions, and post-class recordings.</p>
            </div>
            <a href="{{ route('tutor.manage-courses') }}" class="btn-outline-primary py-2 px-4 text-xs font-bold flex items-center gap-1">
                <span class="material-symbols-outlined text-sm">arrow_back</span> Back to Classes
            </a>
        </div>
    </div>

    
    

    <div class="grid grid-cols-1 lg:grid-cols-1 md:grid-cols-3 gap-5 md:p-8">
        <!-- Post Discussion Form -->
        <div class="clay-card p-6 bg-white h-fit">
            <h2 class="font-headline-sm text-lg font-bold mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">forum</span> Post to Classroom
            </h2>

            <form method="POST" action="{{ route('tutor.classroom.discussion.store', $course) }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                
                <div>
                    <label class="block text-sm font-bold text-on-surface mb-1">Message / Post *</label>
                    <textarea name="content" required rows="4" placeholder="Write message to class... Type @LearnerName to notify family!" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary outline-none text-sm"></textarea>
                    <p class="text-[11px] text-on-surface-variant mt-1">Tip: Use @LearnerName to automatically send an email notification to the student's family.</p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-on-surface mb-1">Attachment (Video up to 1GB, files up to 10MB)</label>
                    <input type="file" name="attachment" class="w-full text-xs text-on-surface-variant file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-primary-container file:text-on-primary-container hover:file:bg-primary/20">
                </div>

                <button type="submit" class="btn-enroll w-full py-3 mt-4">
                    <span class="material-symbols-outlined text-sm">send</span> Post Announcement / Discussion
                </button>
            </form>

            <div class="mt-6 pt-6 border-t border-outline-variant/20">
                <h3 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-3">Enrolled Students in Section</h3>
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    @forelse($enrolledStudents as $student)
                        <div class="flex items-center justify-between text-xs p-2 bg-surface-container-low rounded-lg">
                            <span class="font-bold text-on-surface">{{ $student->name }}</span>
                            <form method="POST" action="{{ route('tutor.messages.start') }}" class="inline">
                                @csrf
                                <input type="hidden" name="student_id" value="{{ $student->id }}">
                                <input type="hidden" name="course_id" value="{{ $course->id }}">
                                <button type="submit" class="text-primary font-bold hover:underline flex items-center gap-0.5">
                                    <span class="material-symbols-outlined text-xs">chat</span> DM
                                </button>
                            </form>
                        </div>
                    @empty
                        <p class="text-xs text-on-surface-variant">No enrolled students yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Main Classroom Stream & Recordings Tabs -->
        <div class="lg:col-span-2 space-y-6">
            <div class="clay-card p-6 bg-white">
                
                <!-- Hub Navigation Tabs -->
                <div class="flex border-b border-outline-variant/20 mb-6">
                    <button @click="activeTab = 'feed'" :class="activeTab === 'feed' ? 'border-primary text-primary font-bold' : 'border-transparent text-on-surface-variant'" class="py-3 px-5 border-b-2 text-sm transition-colors flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">forum</span> Discussion Feed ({{ $discussions->count() }})
                    </button>
                    <button @click="activeTab = 'recordings'" :class="activeTab === 'recordings' ? 'border-primary text-primary font-bold' : 'border-transparent text-on-surface-variant'" class="py-3 px-5 border-b-2 text-sm transition-colors flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">video_library</span> Post-Class Recordings ({{ $recordings->count() }})
                    </button>
                    <button @click="activeTab = 'rewards'" :class="activeTab === 'rewards' ? 'border-primary text-primary font-bold' : 'border-transparent text-on-surface-variant'" class="py-3 px-5 border-b-2 text-sm transition-colors flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">celebration</span> Rewards
                    </button>
                </div>

                <!-- Discussion Feed Tab -->
                <div x-show="activeTab === 'feed'">
                    <div class="space-y-6">
                        @forelse($discussions as $post)
                            <div class="p-6 border border-outline-variant/20 rounded-2xl bg-surface-container-low/30 space-y-4">
                                <div class="flex justify-between items-start">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-primary text-white font-bold flex items-center justify-center text-sm">
                                            {{ substr($post->author->name ?? 'T', 0, 1) }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-sm text-on-surface">{{ $post->author->name ?? 'Tutor' }}</p>
                                            <p class="text-xs text-on-surface-variant"><x-local-time :date="$post->created_at" format="datetime" /></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-sm text-on-surface leading-relaxed whitespace-pre-line">
                                    {!! preg_replace('/@([A-Za-z0-9_\-\.]+)/', '<span class="font-bold text-primary bg-primary/10 px-1 py-0.5 rounded">@$1</span>', e($post->content)) !!}
                                </div>

                                @if($post->attachments && is_array($post->attachments))
                                    <div class="pt-2">
                                        @foreach($post->attachments as $att)
                                            <div class="p-3 bg-white rounded-xl border border-outline-variant/20 inline-flex items-center gap-3 text-xs">
                                                <span class="material-symbols-outlined text-primary">{{ ($att['type'] ?? '') === 'video' ? 'movie' : 'description' }}</span>
                                                <div>
                                                    <a href="{{ $att['url'] }}" target="_blank" class="font-bold text-primary hover:underline">{{ $att['name'] }}</a>
                                                    <p class="text-[10px] text-on-surface-variant">{{ number_format(($att['size'] ?? 0) / 1024 / 1024, 2) }} MB</p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <!-- Nested Comments / Replies -->
                                @if($post->replies->count() > 0)
                                    <div class="pl-6 border-l-2 border-primary/20 space-y-3 pt-3">
                                        @foreach($post->replies as $reply)
                                            <div class="p-3 bg-white rounded-xl border border-outline-variant/10 text-xs space-y-1">
                                                <div class="flex justify-between font-bold text-on-surface">
                                                    <span>{{ $reply->author->name ?? 'User' }}</span>
                                                    <span class="text-on-surface-variant font-normal text-[10px]"><x-local-time :date="$reply->created_at" format="datetime-short" /></span>
                                                </div>
                                                <p class="text-on-surface-variant">{!! preg_replace('/@([A-Za-z0-9_\-\.]+)/', '<span class="font-bold text-primary bg-primary/10 px-1 py-0.5 rounded">@$1</span>', e($reply->content)) !!}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <!-- Reply Form -->
                                <form method="POST" action="{{ route('tutor.classroom.discussion.store', $course) }}" class="flex gap-2 pt-2">
                                    @csrf
                                    <input type="hidden" name="parent_id" value="{{ $post->id }}">
                                    <input type="text" name="content" required placeholder="Write a reply... Use @LearnerName" class="flex-1 px-4 py-2 bg-white border border-outline-variant/30 rounded-xl text-xs outline-none">
                                    <button type="submit" class="btn-primary py-2 px-4 text-xs font-bold">Reply</button>
                                </form>
                            </div>
                        @empty
                            <p class="py-12 text-center text-on-surface-variant">No classroom posts yet. Share your first announcement or discussion above.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Post-Class Recordings Tab -->
                <div x-show="activeTab === 'recordings'" x-cloak class="space-y-6">
                    <!-- Add Recording Form -->
                    <div class="p-5 bg-surface-container-low border border-outline-variant/20 rounded-2xl space-y-3">
                        <h4 class="font-bold text-sm text-on-surface flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">video_call</span> Publish Post-Class Replay Recording
                        </h4>
                        <form method="POST" action="{{ route('tutor.classroom.recording.store', $course) }}" class="space-y-3">
                            @csrf
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <input type="text" name="title" required placeholder="Recording Title (e.g. Session 3 Replay)" class="px-4 py-2 bg-white border border-outline-variant/30 rounded-xl text-xs outline-none">
                                <input type="url" name="recording_url" required placeholder="Video Recording URL (YouTube/Vimeo/Zoom)" class="px-4 py-2 bg-white border border-outline-variant/30 rounded-xl text-xs outline-none">
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" class="btn-enroll py-2 px-5 text-xs font-bold">Publish Recording</button>
                            </div>
                        </form>
                    </div>

                    <!-- Recordings Gallery -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @forelse($recordings as $rec)
                            <div class="p-4 border border-outline-variant/20 rounded-2xl bg-white space-y-3 shadow-sm">
                                <div class="flex justify-between items-start">
                                    <h4 class="font-bold text-sm text-on-surface">{{ $rec->title }}</h4>
                                    <form method="POST" action="{{ route('tutor.classroom.recording.destroy', $rec) }}" onsubmit="return confirm('Remove recording?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-error font-bold hover:underline">Remove</button>
                                    </form>
                                </div>
                                <p class="text-xs text-on-surface-variant">Recorded: {{ $rec->recorded_at ? $rec->recorded_at->format('M d, Y') : $rec->created_at->format('M d, Y') }}</p>
                                <a href="{{ $rec->recording_url }}" target="_blank" class="btn-primary w-full py-2 text-xs flex items-center justify-center gap-1">
                                    <span class="material-symbols-outlined text-sm">play_circle</span> Watch Replay
                                </a>
                            </div>
                        @empty
                            <div class="col-span-2 py-8 text-center text-on-surface-variant">
                                No post-class recordings published for this section yet.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Rewards Tab -->
                <div x-show="activeTab === 'rewards'" x-cloak class="space-y-6">
                    <div class="p-5 bg-gradient-to-r from-primary-container/40 to-tertiary-container/30 border border-outline-variant/20 rounded-2xl">
                        <h4 class="font-bold text-sm text-on-surface flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">celebration</span> Celebrate your learners
                        </h4>
                        <p class="text-xs text-on-surface-variant mt-1">Hand out XP compliments, one level-up tip and badges during or after class. Students are notified instantly.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @forelse($enrolledStudents as $student)
                            <div class="flex items-center justify-between p-3 bg-surface-container-low rounded-2xl border border-outline-variant/20">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-primary text-white font-bold flex items-center justify-center text-sm">{{ substr($student->name, 0, 1) }}</div>
                                    <span class="font-bold text-sm text-on-surface">{{ $student->name }}</span>
                                </div>
                                <button type="button" class="btn-enroll py-2 px-4 text-xs font-bold flex items-center gap-1"
                                    @click="$dispatch('open-reward', { id: {{ $student->id }}, name: @js($student->name), courseId: {{ $course->id }} })">
                                    <span class="material-symbols-outlined text-sm">celebration</span> Celebrate
                                </button>
                            </div>
                        @empty
                            <p class="col-span-2 text-center py-8 text-on-surface-variant">No enrolled students in this course yet.</p>
                        @endforelse
                    </div>

                    @if($recentRewards->count())
                        <div class="pt-4 border-t border-outline-variant/20">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-3">Recent rewards in this course</h4>
                            <div class="space-y-2">
                                @foreach($recentRewards as $reward)
                                    <div class="flex items-center justify-between text-xs p-3 bg-white rounded-xl border border-outline-variant/10">
                                        <div>
                                            <span class="font-bold text-on-surface">{{ $reward->student->name ?? 'Student' }}</span>
                                            @if($reward->badge) <span class="text-primary font-bold">· {{ $reward->badge->title }}</span> @endif
                                            @if($reward->compliments)
                                                <span class="text-on-surface-variant">· {{ collect($reward->compliments)->pluck('label')->filter()->implode(', ') }}</span>
                                            @endif
                                        </div>
                                        <span class="font-bold text-tertiary">+{{ $reward->xp_amount }} XP</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>

    @include('tutor.partials.reward-modal', ['badgeCatalog' => $badgeCatalog])
</div>
@endsection
