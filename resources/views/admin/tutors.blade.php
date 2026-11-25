@extends('layouts.dashboard')
@section('title', 'Tutors Pipeline')
@section('content')
@php
    $labels = [
        'NEW' => 'New',
        'UNDER_REVIEW' => 'Under Review',
        'VIDEO_REVIEWED' => 'Video Reviewed',
        'INTERVIEW' => 'Interview',
        'APPROVED' => 'Approved',
        'REJECTED' => 'Rejected',
    ];
@endphp
<div class="p-5 md:p-8 space-y-6" x-data="tutorKanban()">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-headline-lg text-2xl font-bold">Tutors</h1>
            <p class="text-on-surface-variant text-sm">Register → review on kanban → approve → assign training & skills</p>
        </div>
        <form method="GET" class="flex gap-2">
            <input type="search" name="search" value="{{ $search }}" placeholder="Search tutors..." class="p-2 rounded-xl border border-outline-variant bg-white">
            <button class="px-4 py-2 bg-primary text-white rounded-xl font-bold">Search</button>
        </form>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-white rounded-2xl border p-4"><p class="text-xs text-on-surface-variant">Total</p><p class="text-2xl font-bold">{{ $stats['total'] }}</p></div>
        <div class="bg-white rounded-2xl border p-4"><p class="text-xs text-on-surface-variant">Active</p><p class="text-2xl font-bold">{{ $stats['active'] }}</p></div>
        <div class="bg-white rounded-2xl border p-4"><p class="text-xs text-on-surface-variant">Pending</p><p class="text-2xl font-bold">{{ $stats['pending'] }}</p></div>
        <div class="bg-white rounded-2xl border p-4"><p class="text-xs text-on-surface-variant">Suspended</p><p class="text-2xl font-bold">{{ $stats['suspended'] }}</p></div>
    </div>

    @if(session('success'))
        <div class="p-3 rounded-xl bg-secondary-container text-on-secondary-container font-bold">{{ session('success') }}</div>
    @endif

    <div class="overflow-x-auto pb-4">
        <div class="flex gap-4 min-w-max">
            @foreach($labels as $key => $label)
                <div class="w-72 bg-surface-container-low rounded-2xl border border-outline-variant/40 flex flex-col max-h-[70vh]"
                     data-column="{{ $key }}"
                     @dragover.prevent
                     @drop.prevent="onDrop($event, '{{ $key }}')">
                    <div class="p-3 font-bold border-b border-outline-variant/40 sticky top-0 bg-surface-container-low rounded-t-2xl flex justify-between">
                        <span>{{ $label }}</span>
                        <span class="text-xs bg-white px-2 py-0.5 rounded-full">{{ $columns[$key]->count() }}</span>
                    </div>
                    <div class="p-2 space-y-2 overflow-y-auto flex-1">
                        @foreach($columns[$key] as $tutor)
                            <div class="bg-white rounded-xl border border-outline-variant/50 p-3 shadow-sm cursor-grab active:cursor-grabbing"
                                 draggable="true"
                                 data-tutor-id="{{ $tutor->id }}"
                                 @dragstart="onDragStart($event, {{ $tutor->id }})"
                                 @click="openTutor({{ $tutor->id }})">
                                <div class="flex items-center gap-2">
                                    <div class="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center font-bold text-primary overflow-hidden">
                                        @if($tutor->tutorProfile?->profile_photo_path)
                                            <img src="{{ asset('storage/'.$tutor->tutorProfile->profile_photo_path) }}" class="w-full h-full object-cover" alt="">
                                        @else
                                            {{ strtoupper(substr($tutor->name,0,1)) }}
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-sm truncate">{{ $tutor->name }}</div>
                                        <div class="text-[11px] text-on-surface-variant truncate">{{ $tutor->email }}</div>
                                    </div>
                                </div>
                                @php $subs = $tutor->tutorProfile?->subject_verticals ?? []; @endphp
                                @if(!empty($subs))
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-tertiary-fixed">{{ $subs[0] }}</span>
                                    </div>
                                @endif
                                <div class="mt-2 text-[10px] text-on-surface-variant">{{ $tutor->tutorProfile?->city_country ?? $tutor->timezone }} · {{ $tutor->created_at?->format('M j') }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Detail modal --}}
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex justify-end bg-black/40" @keydown.escape.window="modalOpen=false">
        <div class="w-full max-w-lg h-full bg-white shadow-2xl overflow-y-auto p-6 space-y-4" @click.outside="modalOpen=false">
            <div class="flex justify-between items-start">
                <div>
                    <h2 class="text-xl font-bold" x-text="detail?.name"></h2>
                    <p class="text-sm text-on-surface-variant" x-text="detail?.email"></p>
                </div>
                <button @click="modalOpen=false" class="material-symbols-outlined">close</button>
            </div>
            <template x-if="detail?.video_url">
                <video :src="detail.video_url" controls class="w-full rounded-xl bg-black"></video>
            </template>
            <div class="text-sm space-y-1" x-show="detail?.profile">
                <p><strong>Phone:</strong> <span x-text="detail?.profile?.phone"></span></p>
                <p><strong>Location:</strong> <span x-text="detail?.profile?.city_country"></span></p>
                <p><strong>Experience:</strong> <span x-text="detail?.profile?.teaching_experience"></span></p>
                <p><strong>Education:</strong> <span x-text="detail?.profile?.higher_education"></span> — <span x-text="detail?.profile?.higher_institution"></span></p>
                <p><strong>Rate:</strong> <span x-text="detail?.profile?.expected_hourly_rate"></span> (<span x-text="detail?.profile?.currency"></span>)</p>
            </div>

            <form method="POST" :action="`/admin/tutors/${detail?.id}/pipeline`" class="space-y-2 border-t pt-4">
                @csrf
                <label class="block text-sm font-bold">Pipeline status</label>
                <select name="pipeline_status" class="w-full p-2 rounded-xl border" x-model="detailPipeline">
                    @foreach($labels as $k=>$l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
                </select>
                <label class="block text-sm font-bold">Reviewer notes</label>
                <textarea name="reviewer_notes" rows="3" class="w-full p-2 rounded-xl border" x-text="detail?.profile?.reviewer_notes"></textarea>
                <button class="w-full py-2 bg-primary text-white rounded-xl font-bold">Save pipeline</button>
            </form>

            <form method="POST" :action="`/admin/tutors/${detail?.id}/assign-training`" class="space-y-2 border-t pt-4">
                @csrf
                <label class="block text-sm font-bold">Assign training modules</label>
                @forelse($modules as $m)
                    <label class="flex gap-2 text-sm"><input type="checkbox" name="module_ids[]" value="{{ $m->id }}"> {{ $m->title }}</label>
                @empty
                    <p class="text-xs text-on-surface-variant">No modules yet — create them under Tutor Training.</p>
                @endforelse
                <button class="w-full py-2 bg-secondary text-white rounded-xl font-bold" @if($modules->isEmpty()) disabled @endif>Assign training</button>
            </form>

            <form method="POST" :action="`/admin/tutors/${detail?.id}/assign-skills`" class="space-y-2 border-t pt-4">
                @csrf
                <label class="block text-sm font-bold">Assign skills (verified)</label>
                <input type="hidden" name="verified" value="1">
                @foreach($skills as $skill)
                    <label class="flex gap-2 text-sm"><input type="checkbox" name="skill_ids[]" value="{{ $skill->id }}"> {{ $skill->name }}</label>
                @endforeach
                <button class="w-full py-2 bg-tertiary text-white rounded-xl font-bold">Assign skills</button>
            </form>

            <a :href="`{{ url('/admin/impersonate') }}/${detail?.id}`" class="block text-center text-sm font-bold text-primary">Login as tutor</a>
        </div>
    </div>
</div>
<script>
function tutorKanban() {
    return {
        dragId: null,
        modalOpen: false,
        detail: null,
        detailPipeline: 'NEW',
        onDragStart(e, id) { this.dragId = id; e.dataTransfer.setData('text/plain', id); },
        async onDrop(e, status) {
            const id = this.dragId || e.dataTransfer.getData('text/plain');
            if (!id) return;
            const token = document.querySelector('meta[name=csrf-token]')?.content;
            await fetch(`/admin/tutors/${id}/pipeline`, {
                method: 'POST',
                headers: {'Content-Type':'application/json','X-CSRF-TOKEN': token, 'Accept':'application/json'},
                body: JSON.stringify({pipeline_status: status})
            });
            window.location.reload();
        },
        async openTutor(id) {
            const res = await fetch(`/admin/tutors/${id}/json`);
            this.detail = await res.json();
            this.detailPipeline = this.detail?.profile?.pipeline_status || 'NEW';
            this.modalOpen = true;
        }
    }
}
</script>
@endsection
