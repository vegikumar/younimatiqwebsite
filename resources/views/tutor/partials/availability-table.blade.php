<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="border-b border-outline-variant/20 text-sm">
                <th class="py-3 px-4 font-bold">Class / Target</th>
                <th class="py-3 px-4 font-bold">Format</th>
                <th class="py-3 px-4 font-bold">Day / Date</th>
                <th class="py-3 px-4 font-bold">Time Window</th>
                <th class="py-3 px-4 font-bold">Type</th>
                <th class="py-3 px-4 font-bold">Status</th>
                <th class="py-3 px-4 font-bold">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($slots as $slot)
                @php
                    $days = [0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'];
                @endphp
                <tr class="border-b border-outline-variant/10 text-sm hover:bg-surface-container-low">
                    <td class="py-3 px-4 font-medium">{{ $slot->course->title ?? 'All Classes' }}</td>
                    <td class="py-3 px-4">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $slot->format_type === 'tutoring_1on1' ? 'bg-warning-container text-warning' : 'bg-primary-container text-on-primary-container' }}">
                            {{ $slot->format_type === 'tutoring_1on1' ? '1:1 Tutoring' : 'Group Class' }}
                        </span>
                    </td>
                    <td class="py-3 px-4 font-bold">
                        @if($slot->day_of_week !== null)
                            Every {{ $days[$slot->day_of_week] ?? '' }}
                        @elseif($slot->specific_date)
                            {{ $slot->specific_date->format('M d, Y') }}
                        @else
                            Flexible
                        @endif
                    </td>
                    <td class="py-3 px-4">
                        <x-local-time :date="$slot->start_time" format="time" /> - <x-local-time :date="$slot->end_time" format="time" />
                    </td>
                    <td class="py-3 px-4">
                        <span class="px-2 py-0.5 rounded text-xs font-bold {{ $slot->is_recurring ? 'bg-secondary-container text-on-secondary-container' : 'bg-warning-container text-warning' }}">
                            {{ $slot->is_recurring ? 'Recurring' : 'One-off' }}
                        </span>
                    </td>
                    <td class="py-3 px-4">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $slot->is_booked ? 'bg-error-container text-error' : 'bg-tertiary-fixed text-tertiary' }}">
                            {{ $slot->is_booked ? 'Booked' : 'Open' }}
                        </span>
                    </td>
                    <td class="py-3 px-4">
                        <form method="POST" action="{{ route('tutor.availability.destroy', $slot) }}" onsubmit="return confirm('Remove this slot?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs font-bold text-error hover:underline">Remove</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="py-8 text-center text-on-surface-variant">{{ $emptyLabel ?? 'No slots configured.' }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
