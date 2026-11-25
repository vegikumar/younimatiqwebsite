@extends('layouts.dashboard')

@section('title', 'Availability')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="clay-card p-6 bg-white max-w-3xl">
        <h1 class="font-headline-sm text-lg font-bold mb-4">My Availability</h1>
        <p class="text-sm text-on-surface-variant mb-4">Select your weekly availability and commitment.</p>
        <form method="POST" action="{{ route('tutor.availability.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-bold text-on-surface mb-1">Availability Commitment *</label>
                <select name="availability_commitment" required class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary outline-none text-sm">
                    <option value="flexible">Flexible</option>
                    <option value="part_time">Part-time</option>
                    <option value="full_time">Full-time</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-on-surface mb-1">Slots (day => hours)</label>
                <p class="text-xs text-on-surface-variant">Use the grid below; hours 0-23.</p>
            </div>
            <button type="submit" class="btn-primary py-2 px-4 text-sm font-bold">Save Availability</button>
        </form>

        @if(!empty($grid))
        <div class="mt-6">
            <h2 class="font-bold mb-2">Current Slots</h2>
            <table class="w-full text-sm">
                <thead><tr><th class="text-left">Day</th><th class="text-left">Hours</th><th></th></tr></thead>
                <tbody>
                @foreach($grid as $day => $hours)
                    <tr>
                        <td>{{ $day }}</td>
                        <td>{{ implode(', ', $hours) }}</td>
                        <td></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
