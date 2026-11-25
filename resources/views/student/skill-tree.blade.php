@extends('layouts.app')

@section('title', $treeData['course_title'] . ' - Skill Tree & Portfolio')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12" x-data="{ activeTab: 'tree' }">
    
    <!-- Skill Tree Header -->
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden bg-white">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
            <div>
                <span class="px-3 py-1 bg-primary-container text-on-primary-container rounded-full text-xs font-bold uppercase tracking-wider mb-2 inline-block">
                    Visual Mastery Progress
                </span>
                <h1 class="font-headline-xl text-3xl font-extrabold text-on-surface mb-1">{{ $treeData['current_level_label'] }}</h1>
                <p class="font-body-lg text-sm text-on-surface-variant">Master core concepts, unlock skill nodes across 4 levels, and showcase completed projects.</p>
            </div>
            
            <div class="w-full md:w-64 space-y-2">
                <div class="flex justify-between text-xs font-bold text-on-surface">
                    <span>Overall Mastery</span>
                    <span>{{ $treeData['completion_percentage'] }}%</span>
                </div>
                <div class="w-full bg-surface-container-high h-3 rounded-full overflow-hidden">
                    <div class="bg-primary h-full rounded-full transition-all duration-500" style="width: {{ $treeData['completion_percentage'] }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex border-b border-outline-variant/20 mb-8 bg-white rounded-2xl px-6 pt-2">
        <button @click="activeTab = 'tree'" :class="activeTab === 'tree' ? 'border-primary text-primary font-bold' : 'border-transparent text-on-surface-variant'" class="py-3 px-6 border-b-2 text-sm transition-colors flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">account_tree</span> Skill Tree Nodes ({{ $treeData['mastered_nodes'] }}/{{ $treeData['total_nodes'] }})
        </button>
        <button @click="activeTab = 'portfolio'" :class="activeTab === 'portfolio' ? 'border-primary text-primary font-bold' : 'border-transparent text-on-surface-variant'" class="py-3 px-6 border-b-2 text-sm transition-colors flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">folder_special</span> Project Portfolio ({{ count($treeData['portfolios']) }})
        </button>
        <button @click="activeTab = 'badges'" :class="activeTab === 'badges' ? 'border-primary text-primary font-bold' : 'border-transparent text-on-surface-variant'" class="py-3 px-6 border-b-2 text-sm transition-colors flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">military_tech</span> Badges ({{ $badges->count() }})
        </button>
    </div>

    <!-- Tab 1: Visual Skill Tree Map -->
    <div x-show="activeTab === 'tree'" class="space-y-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($treeData['nodes'] as $node)
                <div class="p-6 rounded-3xl border transition-all shadow-sm bg-white {{ $node['status'] === 'mastered' ? 'border-tertiary bg-tertiary-fixed/30' : ($node['status'] === 'in_progress' ? 'border-primary bg-primary/5' : 'border-outline-variant/20 opacity-60') }}">
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-12 h-12 rounded-2xl font-bold flex items-center justify-center {{ $node['status'] === 'mastered' ? 'bg-tertiary text-white' : ($node['status'] === 'in_progress' ? 'bg-primary text-white' : 'bg-surface-container text-on-surface-variant') }}">
                            <span class="material-symbols-outlined">{{ $node['icon'] }}</span>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $node['status'] === 'mastered' ? 'bg-tertiary-fixed text-tertiary' : ($node['status'] === 'in_progress' ? 'bg-primary-container text-on-primary-container' : 'bg-surface-container-low text-on-surface') }}">
                            {{ $node['status'] }}
                        </span>
                    </div>

                    <h4 class="font-bold text-base text-on-surface mb-1">{{ $node['title'] }}</h4>
                    <p class="text-xs text-on-surface-variant">Level {{ $node['level_number'] }} Node</p>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Tab 2: Project Portfolio Showcase Gallery -->
    <div x-show="activeTab === 'portfolio'" x-cloak class="space-y-8">
        
        <!-- Add Portfolio Project Form -->
        <div class="clay-card p-6 bg-white space-y-4 max-w-2xl">
            <h3 class="font-bold text-base text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">add_to_photos</span> Add Project to Showcase
            </h3>
            <form method="POST" action="{{ route('student.courses.portfolio.store', $course) }}" class="space-y-3">
                @csrf
                <input type="text" name="project_name" required placeholder="Project Name (e.g. Space Shooter Game)" class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/30 rounded-xl text-xs outline-none">
                <input type="url" name="project_url" placeholder="Project Link (Scratch URL or Replit link)" class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/30 rounded-xl text-xs outline-none">
                <button type="submit" class="btn-enroll py-2 px-6 text-xs font-bold">Add Project</button>
            </form>
        </div>

        <!-- Portfolio Items Gallery -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @forelse($treeData['portfolios'] as $item)
                <div class="p-6 border border-outline-variant/20 rounded-3xl bg-white space-y-3 shadow-sm">
                    <div class="flex justify-between items-start">
                        <h4 class="font-bold text-base text-on-surface">{{ $item->project_name }}</h4>
                        <span class="px-2.5 py-0.5 bg-warning-container text-warning rounded-full text-[10px] font-bold">Project Showcase</span>
                    </div>
                    @if($item->project_url)
                        <a href="{{ $item->project_url }}" target="_blank" class="text-xs text-primary font-bold hover:underline flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs">open_in_new</span> View Project Code / Output
                        </a>
                    @endif
                    @if($item->tutor_feedback)
                        <div class="p-3 bg-surface-container-low rounded-xl text-xs text-on-surface-variant font-medium">
                            <strong class="text-on-surface">Teacher Feedback:</strong> {{ $item->tutor_feedback }}
                        </div>
                    @endif
                </div>
            @empty
                <p class="col-span-2 py-8 text-center text-xs text-on-surface-variant">No portfolio projects submitted yet.</p>
            @endforelse
        </div>
    </div>

    <!-- Tab 3: Class Rewards & Badges -->
    <div x-show="activeTab === 'badges'" x-cloak class="space-y-8">
        @if($badges->count())
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($badges as $badge)
                    <div class="p-6 rounded-3xl border border-warning-container bg-gradient-to-br from-warning to-white shadow-sm flex items-start gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-warning text-white flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-3xl">{{ $badge->badge_icon }}</span>
                        </div>
                        <div>
                            <h4 class="font-bold text-base text-on-surface">{{ $badge->title }}</h4>
                            <p class="text-xs text-on-surface-variant mt-1">{{ $badge->description }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="py-16 text-center text-on-surface-variant">
                <span class="material-symbols-outlined text-5xl mb-3">workspace_premium</span>
                <p class="font-bold">No badges yet</p>
                <p class="text-xs mt-1">Your tutor can award badges during class for great work!</p>
            </div>
        @endif
    </div>

</div>
@endsection
