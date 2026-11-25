@extends('layouts.dashboard')

@section('title', 'Announcements & Welcome Posts')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12" x-data="{ activeTab: 'published', duplicateModalOpen: false, activePostId: null }">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-secondary relative overflow-hidden">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="font-headline-xl text-headline-xl text-on-surface mb-2">Announcements & Templated Posts</h1>
                <p class="font-body-lg text-body-lg text-on-surface-variant">Publish instant updates, schedule announcements in advance, duplicate posts across class sections, and manage welcome messages.</p>
            </div>
        </div>
    </div>

    

    <div class="grid grid-cols-1 lg:grid-cols-1 md:grid-cols-3 gap-5 md:p-8">
        <!-- Create Announcement Form -->
        <div class="clay-card p-6 bg-white h-fit" x-data="{ publishOption: 'now' }">
            <h2 class="font-headline-sm text-lg font-bold mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-secondary">campaign</span> Create Announcement
            </h2>

            <form method="POST" action="{{ route('tutor.announcements.store') }}" class="space-y-4">
                @csrf
                
                <div>
                    <label class="block text-sm font-bold text-on-surface mb-1">Target Class / Section *</label>
                    <select name="course_id" required class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary outline-none text-sm">
                        <option value="">Select Class Section</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}">{{ $course->title }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-bold text-on-surface mb-1">Title / Headline *</label>
                    <input type="text" name="title" required placeholder="e.g. Welcome to Week 1 or Preparation Notes" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary outline-none text-sm">
                </div>

                <div>
                    <label class="block text-sm font-bold text-on-surface mb-1">Announcement Body *</label>
                    <textarea name="content" required rows="4" placeholder="Write message to students & families..." class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary outline-none text-sm"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-bold text-on-surface mb-1">Video Attachment URL (Optional)</label>
                    <input type="url" name="video_url" placeholder="https://youtube.com/..." class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary outline-none text-sm">
                </div>

                <div>
                    <label class="block text-sm font-bold text-on-surface mb-1">Publish Timing</label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer text-sm font-bold">
                            <input type="radio" name="publish_option" value="now" x-model="publishOption" class="text-primary"> Publish Immediately
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-sm font-bold">
                            <input type="radio" name="publish_option" value="schedule" x-model="publishOption" class="text-primary"> Schedule for Later
                        </label>
                    </div>
                </div>

                <div x-show="publishOption === 'schedule'" x-cloak>
                    <label class="block text-sm font-bold text-on-surface mb-1">Scheduled Date & Time *</label>
                    <input type="datetime-local" name="scheduled_at" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary outline-none text-sm">
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" name="is_welcome_post" value="1" id="is_welcome_post" class="w-4 h-4 text-primary rounded border-outline-variant/30">
                    <label for="is_welcome_post" class="text-sm font-bold text-on-surface">Mark as Welcome Post for new enrollments</label>
                </div>

                <button type="submit" class="btn-enroll w-full py-3 mt-4">
                    <span class="material-symbols-outlined text-sm">send</span> Submit Post
                </button>
            </form>
        </div>

        <!-- Announcements Tabs & Feed -->
        <div class="lg:col-span-2 space-y-6">
            <div class="clay-card p-6 bg-white">
                <div class="flex border-b border-outline-variant/20 mb-6">
                    <button @click="activeTab = 'published'" :class="activeTab === 'published' ? 'border-primary text-primary font-bold' : 'border-transparent text-on-surface-variant'" class="py-3 px-6 border-b-2 text-sm transition-colors">
                        Published Posts ({{ $publishedAnnouncements->count() }})
                    </button>
                    <button @click="activeTab = 'scheduled'" :class="activeTab === 'scheduled' ? 'border-primary text-primary font-bold' : 'border-transparent text-on-surface-variant'" class="py-3 px-6 border-b-2 text-sm transition-colors">
                        Scheduled Posts ({{ $scheduledAnnouncements->count() }})
                    </button>
                    <button @click="activeTab = 'welcome'" :class="activeTab === 'welcome' ? 'border-primary text-primary font-bold' : 'border-transparent text-on-surface-variant'" class="py-3 px-6 border-b-2 text-sm transition-colors">
                        Welcome Posts ({{ $welcomePosts->count() }})
                    </button>
                </div>

                <!-- Published Tab -->
                <div x-show="activeTab === 'published'" class="space-y-4">
                    @forelse($publishedAnnouncements as $post)
                        <div class="p-5 border border-outline-variant/20 rounded-2xl bg-surface-container-low/50 hover:bg-surface-container-low transition-colors">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-primary-container text-on-primary-container">
                                        {{ $post->course->title }}
                                    </span>
                                    @if($post->is_welcome_post)
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-warning-container text-warning ml-2">Welcome Post</span>
                                    @endif
                                    <h3 class="font-bold text-base mt-2 text-on-surface">{{ $post->title }}</h3>
                                </div>
                                <span class="text-xs text-on-surface-variant">{{ $post->published_at ? $post->published_at->format('M d, Y g:i A') : $post->created_at->format('M d, Y') }}</span>
                            </div>
                            <p class="text-sm text-on-surface-variant mb-4 whitespace-pre-line">{{ $post->content }}</p>
                            @if($post->video_url)
                                <a href="{{ $post->video_url }}" target="_blank" class="inline-flex items-center gap-1 text-xs font-bold text-primary hover:underline mb-4">
                                    <span class="material-symbols-outlined text-sm">play_circle</span> Watch Video Attachment
                                </a>
                            @endif
                            <div class="flex items-center gap-4 pt-3 border-t border-outline-variant/10 text-xs">
                                <button type="button" @click="duplicateModalOpen = true; activePostId = {{ $post->id }}" class="font-bold text-secondary hover:underline flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">content_copy</span> Duplicate across sections
                                </button>
                                <form method="POST" action="{{ route('tutor.announcements.destroy', $post) }}" onsubmit="return confirm('Delete this announcement?');" class="ml-auto">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-bold text-error hover:underline">Delete</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="py-8 text-center text-on-surface-variant">No published announcements yet.</p>
                    @endforelse
                </div>

                <!-- Scheduled Tab -->
                <div x-show="activeTab === 'scheduled'" x-cloak class="space-y-4">
                    @forelse($scheduledAnnouncements as $post)
                        <div class="p-5 border border-outline-variant/20 rounded-2xl bg-warning-container/50">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-warning-container text-warning">
                                        Scheduled for <x-local-time :date="$post->scheduled_at" format="datetime" />
                                    </span>
                                    <h3 class="font-bold text-base mt-2 text-on-surface">{{ $post->title }}</h3>
                                    <p class="text-xs text-on-surface-variant">Course: {{ $post->course->title }}</p>
                                </div>
                            </div>
                            <p class="text-sm text-on-surface-variant mb-4 whitespace-pre-line">{{ $post->content }}</p>
                            <div class="flex justify-end pt-3 border-t border-outline-variant/10 text-xs">
                                <form method="POST" action="{{ route('tutor.announcements.destroy', $post) }}" onsubmit="return confirm('Cancel scheduled announcement?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-bold text-error hover:underline">Cancel & Delete</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="py-8 text-center text-on-surface-variant">No scheduled announcements.</p>
                    @endforelse
                </div>

                <!-- Welcome Posts Tab -->
                <div x-show="activeTab === 'welcome'" x-cloak class="space-y-4">
                    @forelse($welcomePosts as $post)
                        <div class="p-5 border border-outline-variant/20 rounded-2xl bg-secondary-container/50">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-secondary-container text-on-secondary-container">
                                        Auto-Welcome Post
                                    </span>
                                    <h3 class="font-bold text-base mt-2 text-on-surface">{{ $post->title }}</h3>
                                    <p class="text-xs text-on-surface-variant">Class: {{ $post->course->title }}</p>
                                </div>
                            </div>
                            <p class="text-sm text-on-surface-variant mb-4 whitespace-pre-line">{{ $post->content }}</p>
                            @if($post->video_url)
                                <a href="{{ $post->video_url }}" target="_blank" class="inline-flex items-center gap-1 text-xs font-bold text-primary hover:underline">
                                    <span class="material-symbols-outlined text-sm">play_circle</span> {{ $post->video_url }}
                                </a>
                            @endif
                        </div>
                    @empty
                        <p class="py-8 text-center text-on-surface-variant">No welcome posts configured yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Duplicate Modal -->
    <div x-show="duplicateModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-on-background/40 backdrop-blur-sm">
        <div class="bg-white rounded-3xl p-5 md:p-8 max-w-md w-full shadow-2xl" @click.away="duplicateModalOpen = false">
            <h3 class="font-headline-sm text-lg font-bold mb-2">Duplicate Announcement</h3>
            <p class="text-xs text-on-surface-variant mb-6">Select the course sections you want to copy this announcement to:</p>
            
            <template x-if="activePostId">
                <form :action="'{{ url('/tutor/announcements') }}/' + activePostId + '/duplicate'" method="POST" class="space-y-4">
                    @csrf
                    <div class="space-y-2 max-h-48 overflow-y-auto">
                        @foreach($courses as $course)
                            <label class="flex items-center gap-3 p-3 border border-outline-variant/20 rounded-xl cursor-pointer hover:bg-surface-container-low">
                                <input type="checkbox" name="target_course_ids[]" value="{{ $course->id }}" class="w-4 h-4 text-primary rounded">
                                <span class="text-sm font-bold text-on-surface">{{ $course->title }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="duplicateModalOpen = false" class="btn-outline-primary w-full py-2.5 text-sm">Cancel</button>
                        <button type="submit" class="btn-enroll w-full py-2.5 text-sm">Duplicate Now</button>
                    </div>
                </form>
            </template>
        </div>
    </div>
</div>
@endsection
