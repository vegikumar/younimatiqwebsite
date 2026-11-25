@extends('layouts.dashboard')
@section('title', 'Consultation Forms')
@section('content')
<div class="p-5 md:p-8 space-y-8">
    <div class="flex justify-between items-center">
        <h1 class="font-headline-lg text-2xl font-bold">Consultation Forms</h1>
    </div>

    <!-- Consultations Table -->
    <div class="bg-white rounded-2xl border overflow-hidden">
        <div class="overflow-x-auto w-full">
            <table class="w-full text-left">
                <thead class="bg-surface-container-low">
                    <tr>
                        <th class="p-4 font-bold">Parent & Contact</th>
                        <th class="p-4 font-bold">Child Details</th>
                        <th class="p-4 font-bold">Program Interest</th>
                        <th class="p-4 font-bold">Scheduled</th>
                        <th class="p-4 font-bold">Zoom</th>
                        <th class="p-4 font-bold">Date Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($consultations as $c)
                    <tr class="border-t hover:bg-surface-container-low/60 transition-colors">
                        <td class="p-4">
                            <span class="font-bold text-on-surface">{{ $c->parent_name }}</span><br>
                            <span class="text-xs text-on-surface-variant">{{ $c->email }}<br>{{ $c->phone }}</span>
                        </td>
                        <td class="p-4">
                            <span class="font-medium text-on-surface">{{ $c->child_name }}</span><br>
                            <span class="text-xs text-on-surface-variant mt-0.5">{{ $c->age }}</span>
                        </td>
                        <td class="p-4">
                            <span class="font-bold text-on-surface">{{ str_replace(['Consultation - ', 'Free Trial - '], '', $c->camp_name) }}</span>
                            <div class="text-xs text-on-surface-variant mt-0.5">Free Consultation</div>
                        </td>
                        <td class="p-4 text-sm text-on-surface-variant">
                            @if($c->scheduled_at)
                                <x-local-time :date="$c->scheduled_at" format="datetime" />
                            @else
                                —
                            @endif
                        </td>
                        <td class="p-4">
                            @if($c->zoom_join_url)
                                <a href="{{ $c->zoom_join_url }}" target="_blank" class="inline-flex items-center gap-1 text-xs font-bold text-white px-3 py-1.5 rounded-lg" style="background:#2D8CFF;">Join Zoom</a>
                            @else
                                <span class="text-xs text-on-surface-variant">Pending</span>
                            @endif
                        </td>
                        <td class="p-4 text-sm text-on-surface-variant"><x-local-time :date="$c->created_at" format="date" /></td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="p-5 md:p-8 text-center text-on-surface-variant">No consultation forms found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($consultations->hasPages())
        <div class="p-4">{{ $consultations->links() }}</div>
        @endif
    </div>
</div>
@endsection
