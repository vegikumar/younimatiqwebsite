@extends('layouts.dashboard')

@section('title', 'AI Teaching Assistant')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12" x-data="{ toolTab: 'note' }">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <span class="px-3 py-1 bg-primary-container text-on-primary-container rounded-full text-xs font-bold uppercase tracking-wider mb-2 inline-block">
                    AI-Powered Teaching Suite
                </span>
                <h1 class="font-headline-xl text-headline-xl text-on-surface mb-2">AI Teaching Assistant</h1>
                <p class="font-body-lg text-body-lg text-on-surface-variant">Draft polished parent updates from 2–3 bullet points, review 4-hour window Zoom session recaps, and generate AI progress notes for Scratch/Python/Arduino projects.</p>
            </div>
        </div>
    </div>

    

    <div class="clay-card bg-white p-6 md:p-8 space-y-6">
        <!-- Suite Tool Tabs -->
        <div class="flex border-b border-outline-variant/20">
            <button @click="toolTab = 'note'" :class="toolTab === 'note' ? 'border-primary text-primary font-bold' : 'border-transparent text-on-surface-variant'" class="py-3 px-5 border-b-2 text-sm transition-colors flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">edit_note</span> AI Parent Note & HW Drafter
            </button>
            <button @click="toolTab = 'recap'" :class="toolTab === 'recap' ? 'border-primary text-primary font-bold' : 'border-transparent text-on-surface-variant'" class="py-3 px-5 border-b-2 text-sm transition-colors flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">subtitles</span> 4-Hour Zoom Session Recaps ({{ $recaps->where('status', 'pending_review')->count() }})
            </button>
            <button @click="toolTab = 'project'" :class="toolTab === 'project' ? 'border-primary text-primary font-bold' : 'border-transparent text-on-surface-variant'" class="py-3 px-5 border-b-2 text-sm transition-colors flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">code</span> AI Code & Project Reviewer
            </button>
        </div>

        <!-- Tool 1: AI Parent Note & HW Drafter -->
        <div x-show="toolTab === 'note'">
            <div class="grid grid-cols-1 lg:grid-cols-1 md:grid-cols-2 gap-5 md:p-8">
                <div class="space-y-4">
                    <h3 class="font-bold text-base text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">auto_fix_high</span> Jot 2–3 Quick Bullets
                    </h3>
                    <form method="POST" action="{{ route('tutor.ai-assistant.parent-note') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-on-surface mb-1">Class *</label>
                            <select name="course_id" required class="w-full px-4 py-2 bg-surface-container-low border border-outline-variant/30 rounded-xl text-xs outline-none">
                                <option value="">— Select Class —</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}">{{ $course->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-on-surface mb-1">Student / Family *</label>
                            <select name="student_id" required class="w-full px-4 py-2 bg-surface-container-low border border-outline-variant/30 rounded-xl text-xs outline-none">
                                <option value="">— Select Student —</option>
                                @foreach($courses as $course)
                                    @foreach($course->enrollments as $enrollment)
                                        @if($enrollment->user)
                                            <option value="{{ $enrollment->user->id }}">{{ $enrollment->user->name }} ({{ $course->title }})</option>
                                        @endif
                                    @endforeach
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-on-surface mb-1">Quick Bullet Points *</label>
                            <textarea name="raw_bullets" required rows="4" placeholder="e.g.&#10;• Completed Python loops&#10;• Mastered while loop syntax&#10;• Assigned exercise 3 for homework" class="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl text-xs outline-none resize-none"></textarea>
                        </div>
                        <button type="submit" class="btn-enroll w-full py-3 text-xs font-bold flex items-center justify-center gap-1">
                            <span class="material-symbols-outlined text-sm">auto_awesome</span> Draft Polished Update & Homework
                        </button>
                    </form>
                </div>

                <!-- Saved Notes History -->
                <div class="space-y-4">
                    <h3 class="font-bold text-base text-on-surface">Recent Saved AI Notes</h3>
                    <div class="space-y-3 max-h-[400px] overflow-y-auto">
                        @forelse($recentNotes as $note)
                            <div class="p-4 border border-outline-variant/20 rounded-2xl bg-surface-container-low/30 space-y-2 text-xs">
                                <div class="flex justify-between items-baseline font-bold text-on-surface">
                                    <span>{{ $note->student->name ?? 'Student' }} ({{ $note->course->title ?? 'Class' }})</span>
                                    <span class="text-[10px] text-on-surface-variant"><x-local-time :date="$note->created_at" format="datetime-short" /></span>
                                </div>
                                <div class="p-3 bg-white rounded-xl border border-outline-variant/10 text-on-surface leading-relaxed whitespace-pre-line">
                                    {{ $note->ai_generated_note }}
                                </div>
                                @if($note->homework_assignment)
                                    <div class="p-2 bg-warning-container text-warning rounded-lg font-bold">
                                        {{ $note->homework_assignment }}
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="text-xs text-on-surface-variant py-6 text-center">No AI notes generated yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Tool 2: 4-Hour Edit Window Session Recaps -->
        <div x-show="toolTab === 'recap'" x-cloak class="space-y-6">
            <h3 class="font-bold text-base text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">schedule</span> Automatic Zoom Session Recaps (4-Hour Edit Window)
            </h3>
            <p class="text-xs text-on-surface-variant">The AI automatically summarizes your portion of the Zoom call transcript. You have a 4-hour window to review, edit, skip, or let it auto-send to families.</p>

            <div class="space-y-4">
                @forelse($recaps as $recap)
                    <div class="p-5 border border-outline-variant/20 rounded-2xl bg-surface-container-low/30 space-y-3">
                        <div class="flex justify-between items-center">
                            <div>
                                <h4 class="font-bold text-sm text-on-surface">{{ $recap->course->title ?? 'Live Class Session' }}</h4>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $recap->status === 'sent' ? 'bg-tertiary-fixed text-tertiary' : ($recap->status === 'skipped' ? 'bg-surface-container-low text-on-surface' : 'bg-warning-container text-warning') }}">
                                    {{ $recap->status }}
                                </span>
                            </div>
                            @if($recap->status === 'pending_review')
                                <span class="text-xs text-error font-bold flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">timer</span> Auto-sends in {{ $recap->auto_send_at->diffForHumans() }}
                                </span>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('tutor.ai-assistant.recap', $recap) }}" class="space-y-3">
                            @csrf
                            <textarea name="edited_summary" rows="3" class="w-full px-4 py-2.5 bg-white border border-outline-variant/30 rounded-xl text-xs outline-none">{{ $recap->edited_summary ?? $recap->transcript_summary }}</textarea>
                            
                            @if($recap->status === 'pending_review')
                                <div class="flex gap-2 justify-end">
                                    <button type="submit" name="action" value="skip" class="btn-outline-primary py-1.5 px-4 text-xs font-bold">Skip</button>
                                    <button type="submit" name="action" value="save" class="btn-primary py-1.5 px-4 text-xs font-bold">Save Edits</button>
                                    <button type="submit" name="action" value="send" class="btn-enroll py-1.5 px-4 text-xs font-bold">Approve & Send Now</button>
                                </div>
                            @endif
                        </form>
                    </div>
                @empty
                    <p class="text-xs text-on-surface-variant py-8 text-center">No Zoom session recaps pending review.</p>
                @endforelse
            </div>
        </div>

        <!-- Tool 3: AI Code & Project Reviewer -->
        <div x-show="toolTab === 'project'" x-cloak class="space-y-6">
            <h3 class="font-bold text-base text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">developer_board</span> AI Code & Project Reviewer
            </h3>
            <p class="text-xs text-on-surface-variant">Paste a student's Scratch block, Python, or Arduino code snippet to auto-generate a parent-friendly progress report evaluating key achievements.</p>

            <form method="POST" action="{{ route('tutor.ai-assistant.project-review') }}" class="space-y-4 max-w-2xl">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-on-surface mb-1">Class *</label>
                        <select name="course_id" required class="w-full px-4 py-2 bg-surface-container-low border border-outline-variant/30 rounded-xl text-xs outline-none">
                            <option value="">— Select Class —</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}">{{ $course->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface mb-1">Student *</label>
                        <select name="student_id" required class="w-full px-4 py-2 bg-surface-container-low border border-outline-variant/30 rounded-xl text-xs outline-none">
                            <option value="">— Select Student —</option>
                            @foreach($courses as $course)
                                @foreach($course->enrollments as $enrollment)
                                    @if($enrollment->user)
                                        <option value="{{ $enrollment->user->id }}">{{ $enrollment->user->name }}</option>
                                    @endif
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Project Title *</label>
                    <input type="text" name="project_title" required placeholder="e.g. Scratch Maze Game or Arduino LED Blinker" class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/30 rounded-xl text-xs outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Paste Code Snippet / Output Description</label>
                    <textarea name="code_content" rows="4" placeholder="def start_game():&#10;    score = 100&#10;    print('Game Started!')" class="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl text-xs font-mono outline-none resize-none"></textarea>
                </div>

                <button type="submit" class="btn-enroll w-full py-3 text-xs font-bold">
                    <span class="material-symbols-outlined text-sm">auto_awesome</span> Generate Parent Progress Note
                </button>
            </form>
        </div>

    </div>
</div>
@endsection
