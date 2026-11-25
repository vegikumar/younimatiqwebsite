@extends('layouts.app')

@section('content')
@php
    $firstModule = $course->modules->first();
@endphp
<div class="bg-on-surface-variant min-h-screen text-outline" x-data="{
    selectedId: {{ $firstModule?->id ?? 'null' }},
    modules: {{ $course->modules->map(fn($m) => [
        'id' => $m->id,
        'title' => $m->title,
        'content_type' => $m->content_type,
        'content_url' => $m->content_url,
        'content_text' => $m->content_text,
        'embed_url' => $m->content_type === 'video' && $m->content_url ? \App\Helpers\MediaHelper::embedUrl($m->content_url) : null,
        'is_direct_video' => $m->content_type === 'video' && $m->content_url ? \App\Helpers\MediaHelper::isDirectVideo($m->content_url) : false,
        'practical_video_url' => $m->practical_video_url,
    ])->values()->toJson() }},
    get selected() { return this.modules.find(m => m.id === this.selectedId) || null; }
}">
    <div class="px-4 py-3 sm:px-6 lg:px-8 sticky top-0 z-50 flex items-center justify-between bg-on-surface-variant border-b border-outline">
        <div class="flex items-center">
            <a href="{{ route('student.courses') }}" class="inline-flex items-center text-outline hover:text-white mr-6 transition-colors">
                <span class="material-symbols-outlined text-sm mr-1">arrow_back</span> My Courses
            </a>
            <h1 class="text-xl font-bold text-white hidden md:block">{{ $course->title }}</h1>
        </div>
        <div class="flex items-center gap-4">
            @if($enrollment && $enrollment->hasUploadedCertificate())
                <a href="{{ route('student.courses.certificate.download', $course) }}" class="text-xs font-bold px-3 py-1.5 rounded-lg bg-secondary text-on-surface hover:bg-secondary/90 inline-flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">download</span> Certificate
                </a>
            @elseif($enrollment && $enrollment->status === 'completed')
                <a href="{{ route('student.courses.certificate', $course) }}" target="_blank" class="text-xs font-bold px-3 py-1.5 rounded-lg bg-secondary text-on-surface hover:bg-secondary/90">
                    Certificate
                </a>
            @endif
            <span class="text-sm text-outline">Instructor: {{ $course->tutor->name ?? 'Younimatiq' }}</span>
        </div>
    </div>

    

    <div class="flex flex-col lg:flex-row h-[calc(100vh-65px)]">
        <div class="flex-1 flex flex-col bg-black relative min-h-[50vh] lg:min-h-0">
            <template x-if="selected && selected.practical_video_url">
                <div class="absolute top-4 right-4 z-20 flex flex-col items-end gap-2">
                    <a :href="selected.practical_video_url" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-secondary text-on-surface font-bold text-sm hover:bg-secondary/90 shadow-lg">
                        <span class="material-symbols-outlined text-base">smart_display</span> Practical Video
                    </a>
                </div>
            </template>
            <template x-if="selected && selected.is_direct_video && selected.content_url">
                <video :src="selected.content_url" class="w-full h-full flex-1 bg-black" controls playsinline></video>
            </template>
            <template x-if="selected && selected.embed_url">
                <iframe :src="selected.embed_url" class="w-full h-full flex-1" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </template>
            <template x-if="selected && selected.content_type === 'text'">
                <div class="flex-1 overflow-y-auto p-5 md:p-8 lg:p-6 md:p-12">
                    <h2 class="text-2xl font-bold text-white mb-6" x-text="selected.title"></h2>
                    <div class="prose prose-invert max-w-none text-outline" x-html="selected.content_text || '<p>No text content for this module.</p>'"></div>
                </div>
            </template>
            <template x-if="selected && selected.content_type === 'zoom' && selected.content_url">
                <div class="flex-1 flex items-center justify-center p-5 md:p-8">
                    <div class="text-center">
                        <span class="material-symbols-outlined text-6xl text-error mb-4">videocam</span>
                        <h2 class="text-2xl font-bold text-white mb-2" x-text="selected.title"></h2>
                        <p class="text-outline mb-6">Join the live Zoom session for this module.</p>
                        <a :href="selected.content_url" target="_blank" class="inline-flex items-center px-6 py-3 bg-error text-white font-bold rounded-xl hover:bg-error">
                            Join Meeting
                        </a>
                    </div>
                </div>
            </template>
            <template x-if="selected && selected.content_type === 'video' && !selected.embed_url && !selected.is_direct_video && selected.content_url">
                <div class="flex-1 flex items-center justify-center p-5 md:p-8">
                    <div class="text-center">
                        <span class="material-symbols-outlined text-6xl text-on-surface mb-4">play_circle</span>
                        <h2 class="text-2xl font-bold text-white mb-2" x-text="selected.title"></h2>
                        <a :href="selected.content_url" target="_blank" class="btn-primary btn-sm mt-4">
                            Open Video
                        </a>
                    </div>
                </div>
            </template>
            <template x-if="!selected">
                <div class="flex-1 flex items-center justify-center p-5 md:p-8">
                    <div class="text-center max-w-lg">
                        <span class="material-symbols-outlined text-6xl text-on-surface mb-4">play_circle</span>
                        <h2 class="text-2xl font-bold text-white mb-2">Course Player</h2>
                        <p class="text-outline">Select a module from the curriculum sidebar.</p>
                        @if($enrollment)
                            <p class="text-primary font-bold mt-4">{{ round($enrollment->progress ?? 0) }}% Complete</p>
                        @endif
                    </div>
                </div>
            </template>
        </div>

        <aside class="lg:w-96 bg-on-surface-variant border-l border-outline flex flex-col shrink-0 max-h-[50vh] lg:max-h-none">
            <div class="p-6 border-b border-outline">
                <h3 class="text-lg font-bold text-white mb-3">Curriculum</h3>
                @php
                    $completedCount = $course->modules->filter(fn($m) => ($progress[$m->id]->completed ?? false))->count();
                    $totalModules = $course->modules->count();
                    $pct = $totalModules > 0 ? round(($completedCount / $totalModules) * 100) : 0;
                @endphp
                <div class="flex justify-between text-xs text-outline mb-1">
                    <span>{{ $pct }}% Completed</span>
                    <span>{{ $completedCount }} / {{ $totalModules }}</span>
                </div>
                <div class="w-full bg-on-surface-variant rounded-full h-1.5">
                    <div class="bg-primary h-1.5 rounded-full" style="width: {{ $pct }}%"></div>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto divide-y divide-outline/50">
                @forelse($course->modules as $index => $module)
                    @php $isComplete = $progress[$module->id]->completed ?? false; @endphp
                    <div class="p-5 hover:bg-on-surface-variant/50 transition-colors cursor-pointer"
                         :class="{ 'bg-on-surface-variant/70': selectedId === {{ $module->id }} }"
                         @click="selectedId = {{ $module->id }}">
                        <div class="flex items-start gap-3">
                            <div class="w-6 h-6 rounded-full border-2 {{ $isComplete ? 'bg-primary border-primary' : 'border-outline' }} flex-shrink-0 flex items-center justify-center mt-0.5">
                                @if($isComplete)<span class="material-symbols-outlined text-white text-xs">check</span>@endif
                            </div>
                            <div class="flex-1">
                                <h4 class="text-sm font-medium text-outline-variant mb-1">{{ $index + 1 }}. {{ $module->title }}</h4>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-on-surface-variant uppercase font-semibold">{{ $module->content_type ?? 'content' }}</span>
                                    @if($module->practical_video_url)
                                        <a href="{{ $module->practical_video_url }}" target="_blank" rel="noopener" class="text-xs font-bold text-secondary hover:underline inline-flex items-center gap-0.5">
                                            <span class="material-symbols-outlined text-[14px]">smart_display</span> Practical
                                        </a>
                                    @endif
                                </div>
                                <div class="mt-3 flex flex-wrap gap-2" @click.stop>
                                    @if(!$isComplete)
                                        <form method="POST" action="{{ route('student.courses.module.complete', [$course, $module->id]) }}">
                                            @csrf
                                            <button type="submit" class="btn-primary btn-sm text-xs">Mark Complete</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-5 md:p-8 text-center text-outline text-sm">No modules added yet.</div>
                @endforelse
            </div>
        </aside>
    </div>
</div>
@endsection
