<?php

namespace App\Jobs;

use App\Models\CampBooking;
use App\Services\EmailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CampFollowUpJob implements ShouldQueue
{
    use Queueable;

    public function handle(EmailService $emailService): void
    {
        $bookings = CampBooking::where('payment_status', 'paid')
            ->where('created_at', '<=', now()->subDay())
            ->where('created_at', '>=', now()->subDay()->subHour())
            ->get();

        foreach ($bookings as $booking) {
            $emailService->sendTemplate('camp_follow_up', $booking->email, [
                'parent_name' => $booking->parent_name,
                'child_name' => $booking->child_name,
                'camp_name' => $booking->camp_name,
            ]);
        }
    }
}
