@extends('layouts.dashboard')

@section('title', 'Direct Messages')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="font-headline-xl text-headline-xl text-on-surface mb-2">Direct Messages Hub</h1>
                <p class="font-body-lg text-body-lg text-on-surface-variant">Private 1-on-1 communication with enrolled learners and families (separate from public classroom feeds).</p>
            </div>
        </div>
    </div>

    
    

    <div class="grid grid-cols-1 lg:grid-cols-1 md:grid-cols-3 gap-5 md:p-8">
        <!-- Inbox / Conversations List -->
        <div class="clay-card p-6 bg-white h-fit space-y-6">
            <h2 class="font-headline-sm text-lg font-bold flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">chat</span> Private 1:1 Inbox
            </h2>

            <div class="space-y-3">
                @forelse($conversations as $conv)
                    <a href="{{ route('tutor.messages.show', $conv) }}" class="block p-4 rounded-2xl border border-outline-variant/20 hover:border-primary/50 transition-all bg-surface-container-low/50">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-primary/20 text-primary font-bold flex items-center justify-center text-sm shrink-0">
                                {{ substr($conv->student->name ?? 'S', 0, 1) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-baseline">
                                    <h4 class="font-bold text-sm text-on-surface truncate">{{ $conv->student->name ?? 'Student' }}</h4>
                                    <span class="text-[10px] text-on-surface-variant">{{ $conv->last_message_at ? $conv->last_message_at->diffForHumans() : '' }}</span>
                                </div>
                                <p class="text-xs text-on-surface-variant truncate">{{ $conv->latestMessage->message ?? 'No messages yet.' }}</p>
                            </div>
                        </div>
                    </a>
                @empty
                    <p class="py-8 text-center text-xs text-on-surface-variant">No active direct message conversations yet.</p>
                @endforelse
            </div>

            <!-- Start New Direct Message Form -->
            <div class="pt-6 border-t border-outline-variant/20">
                <h3 class="font-bold text-sm text-on-surface mb-3 flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm text-primary">person_add</span> Start 1:1 Conversation
                </h3>
                <form method="POST" action="{{ route('tutor.messages.start') }}" class="space-y-3">
                    @csrf
                    <div>
                        <select name="student_id" required class="w-full px-4 py-2 bg-white border border-outline-variant/30 rounded-xl text-xs outline-none">
                            <option value="">Select Enrolled Student / Family</option>
                            @foreach($myCourses as $course)
                                @foreach($course->enrollments as $enrollment)
                                    @if($enrollment->user)
                                        <option value="{{ $enrollment->user->id }}">{{ $enrollment->user->name }} ({{ $course->title }})</option>
                                    @endif
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn-primary w-full py-2 text-xs font-bold">Start Private Thread</button>
                </form>
            </div>
        </div>

        <!-- Thread Workspace Placeholder -->
        <div class="lg:col-span-2">
            <div class="clay-card p-6 md:p-12 bg-white text-center flex flex-col items-center justify-center min-h-[400px]">
                <span class="material-symbols-outlined text-5xl text-on-surface-variant mb-3">mark_chat_read</span>
                <h3 class="font-bold text-lg text-on-surface mb-1">Select a conversation</h3>
                <p class="text-xs text-on-surface-variant max-w-sm">Choose a student/family from the inbox list on the left to view private direct messages and attachments.</p>
            </div>
        </div>
    </div>
</div>
@endsection
