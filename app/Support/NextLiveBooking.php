<?php

namespace App\Support;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class NextLiveBooking
{
    /**
     * Prefer a real upcoming scheduled_class over enrollment placeholders.
     */
    public static function pick(Collection $bookings): ?Booking
    {
        $open = $bookings
            ->filter(fn (Booking $b) => ! in_array((string) $b->status, ['completed', 'cancelled'], true))
            ->values();

        $upcomingClass = $open
            ->filter(function (Booking $b) {
                if ((string) $b->session_type === 'enrollment') {
                    return false;
                }
                if (! $b->scheduled_at) {
                    return false;
                }

                return Carbon::parse($b->scheduled_at)->copy()->addMinutes(60)->isFuture();
            })
            ->sortBy(fn (Booking $b) => Carbon::parse($b->scheduled_at)->timestamp)
            ->first();

        if ($upcomingClass) {
            return $upcomingClass;
        }

        $anyUpcoming = $open
            ->filter(function (Booking $b) {
                if (! $b->scheduled_at) {
                    return false;
                }

                return Carbon::parse($b->scheduled_at)->copy()->addMinutes(60)->isFuture();
            })
            ->sortBy(fn (Booking $b) => Carbon::parse($b->scheduled_at)->timestamp)
            ->first();

        if ($anyUpcoming) {
            return $anyUpcoming;
        }

        // Fall back to enrollment placeholder / latest row so the card still shows tutor status.
        return $open->firstWhere('session_type', 'enrollment')
            ?? $open->sortByDesc('id')->first()
            ?? $bookings->sortByDesc('id')->first();
    }
}
