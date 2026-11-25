@extends('layouts.dashboard')
@section('title', 'Students')
@section('content')
@php
    $labels = [
        'pending' => 'Pending',
        'active_trial' => 'Active (on trial)',
        'active_enrolled' => 'Active (enrolled/paid)',
        'trial_expiring' => 'Trial expiring (7d)',
        'trial_expired' => 'Trial expired',
        'suspended' => 'Suspended',
    ];
    $droppable = ['pending' => 'pending', 'active_trial' => 'active', 'active_enrolled' => 'active', 'suspended' => 'suspended'];
@endphp
<div class="p-5 md:p-8 space-y-6" x-data="studentKanban()">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-headline-lg text-2xl font-bold">Students</h1>
            <p class="text-on-surface-variant text-sm">Kanban by account & trial status</p>
        </div>
        <form method="GET" class="flex gap-2">
            <input type="search" name="search" value="{{ $search }}" placeholder="Search students..." class="p-2 rounded-xl border border-outline-variant bg-white">
            <button class="px-4 py-2 bg-primary text-white rounded-xl font-bold">Search</button>
        </form>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
        <div class="bg-white rounded-2xl border p-4"><p class="text-xs text-on-surface-variant">Total</p><p class="text-2xl font-bold">{{ $stats['total'] }}</p></div>
        <div class="bg-white rounded-2xl border p-4"><p class="text-xs text-on-surface-variant">Active</p><p class="text-2xl font-bold">{{ $stats['active'] }}</p></div>
        <div class="bg-white rounded-2xl border p-4"><p class="text-xs text-on-surface-variant">Pending</p><p class="text-2xl font-bold">{{ $stats['pending'] }}</p></div>
        <div class="bg-white rounded-2xl border p-4"><p class="text-xs text-on-surface-variant">Trial expiring</p><p class="text-2xl font-bold">{{ $stats['trial_expiring'] }}</p></div>
        <div class="bg-white rounded-2xl border p-4"><p class="text-xs text-on-surface-variant">Suspended</p><p class="text-2xl font-bold">{{ $stats['suspended'] }}</p></div>
    </div>

    <details class="bg-white rounded-2xl border p-4">
        <summary class="font-bold cursor-pointer">Add student</summary>
        <form method="POST" action="{{ route('admin.students.store') }}" class="grid md:grid-cols-2 gap-3 mt-4">
            @csrf
            <input name="name" required placeholder="Name" class="p-3 rounded-xl border bg-surface-container-low">
            <input type="email" name="email" required placeholder="Email" class="p-3 rounded-xl border bg-surface-container-low">
            <input type="password" name="password" required placeholder="Password" class="p-3 rounded-xl border bg-surface-container-low">
            <input type="password" name="password_confirmation" required placeholder="Confirm password" class="p-3 rounded-xl border bg-surface-container-low">
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="send_email" value="1"> Send credentials email</label>
            <button class="px-4 py-2 bg-primary text-white rounded-xl font-bold">Create</button>
        </form>
    </details>

    @if(session('success'))
        <div class="p-3 rounded-xl bg-secondary-container text-on-secondary-container font-bold">{{ session('success') }}</div>
    @endif

    <div class="overflow-x-auto pb-4">
        <div class="flex gap-4 min-w-max">
            @foreach($labels as $key => $label)
                <div class="w-72 bg-surface-container-low rounded-2xl border border-outline-variant/40 flex flex-col max-h-[70vh]"
                     @dragover.prevent
                     @drop.prevent="onDrop($event, '{{ $key }}')">
                    <div class="p-3 font-bold border-b border-outline-variant/40 flex justify-between">
                        <span class="text-sm">{{ $label }}</span>
                        <span class="text-xs bg-white px-2 py-0.5 rounded-full">{{ $columns[$key]->count() }}</span>
                    </div>
                    <div class="p-2 space-y-2 overflow-y-auto flex-1">
                        @foreach($columns[$key] as $student)
                            <div class="bg-white rounded-xl border p-3 shadow-sm cursor-grab"
                                 draggable="true"
                                 @dragstart="onDragStart($event, {{ $student->id }})">
                                <div class="font-bold text-sm truncate">{{ $student->name }}</div>
                                <div class="text-[11px] text-on-surface-variant truncate">{{ $student->email }}</div>
                                @if($student->trial_ends_at)
                                    <div class="text-[10px] mt-1 text-on-surface-variant">Trial: {{ $student->trial_ends_at->format('M j, Y') }}</div>
                                @endif
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <a href="{{ route('admin.impersonate', $student) }}" class="text-[11px] font-bold text-primary" onclick="event.stopPropagation()">Login as</a>
                                    <form method="POST" action="{{ route('admin.users.extend-trial', $student) }}" class="inline" onclick="event.stopPropagation()">
                                        @csrf
                                        <input type="hidden" name="days" value="7">
                                        <input type="hidden" name="reactivate" value="1">
                                        <button class="text-[11px] font-bold text-secondary">+7d trial</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    <p class="text-xs text-on-surface-variant">Drag into Pending / Active columns / Suspended to change account status. Trial columns are computed from trial dates & payments.</p>
</div>
<script>
function studentKanban() {
    const map = @json($droppable);
    return {
        dragId: null,
        onDragStart(e, id) { this.dragId = id; e.dataTransfer.setData('text/plain', id); },
        async onDrop(e, column) {
            const id = this.dragId || e.dataTransfer.getData('text/plain');
            const status = map[column];
            if (!id || !status) {
                if (!status) alert('That column is computed from trial/payment data and cannot be dropped into.');
                return;
            }
            const token = document.querySelector('meta[name=csrf-token]')?.content;
            await fetch(`/admin/students/${id}/status`, {
                method: 'POST',
                headers: {'Content-Type':'application/json','X-CSRF-TOKEN': token, 'Accept':'application/json'},
                body: JSON.stringify({status})
            });
            window.location.reload();
        }
    }
}
</script>
@endsection
