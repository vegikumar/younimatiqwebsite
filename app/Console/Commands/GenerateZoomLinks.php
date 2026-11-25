<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use App\Models\CampBooking;
use App\Services\ZoomService;
use App\Services\EmailService;
use Carbon\Carbon;

class GenerateZoomLinks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zoom:generate-links';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Zoom links for upcoming classes starting in exactly 4 hours.';

    /**
     * Execute the console command.
     */
    public function handle(ZoomService $zoomService, EmailService $emailService)
    {
        $targetTimeStart = Carbon::now()->addHours(4)->startOfMinute();
        $targetTimeEnd = Carbon::now()->addHours(4)->addMinutes(5)->endOfMinute();

        $bookings = Booking::with(['course', 'student', 'tutor'])
            ->whereNotNull('scheduled_at')
            ->whereNull('zoom_join_url')
            ->whereBetween('scheduled_at', [$targetTimeStart, $targetTimeEnd])
            ->get();

        $campBookings = CampBooking::query()
            ->whereNotNull('scheduled_at')
            ->whereNull('zoom_join_url')
            ->whereBetween('scheduled_at', [$targetTimeStart, $targetTimeEnd])
            ->get();

        if ($bookings->isEmpty() && $campBookings->isEmpty()) {
            $this->info('No bookings found starting in exactly 4 hours without Zoom links.');
            return;
        }

        foreach ($bookings as $booking) {
            $this->info("Processing booking ID: {$booking->id}");

            try {
                $topic = "{$booking->course->title} - 1:1 Class";
                $meeting = $zoomService->createMeeting(
                    $topic,
                    $booking->scheduled_at,
                    $booking->course->duration_minutes ?? 45,
                    'UTC'
                );

                $booking->update(ZoomService::bookingFields($meeting));

                // Email Student
                if ($booking->student) {
                    $emailService->sendTemplate('class_link_student', $booking->student->email, [
                        'student_name' => $booking->student->name,
                        'course_title' => $booking->course->title,
                        'start_time' => $booking->scheduled_at->format('M d, Y h:i A') . ' UTC',
                        'join_url' => $meeting['join_url']
                    ]);
                }

                // Email Tutor
                if ($booking->tutor) {
                    $emailService->sendTemplate('class_link_tutor', $booking->tutor->email, [
                        'tutor_name' => $booking->tutor->name,
                        'course_title' => $booking->course->title,
                        'start_time' => $booking->scheduled_at->format('M d, Y h:i A') . ' UTC',
                        'start_url' => $meeting['start_url']
                    ]);
                }

                $this->info("Successfully generated link and queued emails for booking ID: {$booking->id}");
            } catch (\Exception $e) {
                $this->error("Failed to generate link for booking ID {$booking->id}: " . $e->getMessage());
            }
        }

        foreach ($campBookings as $campBooking) {
            $this->info("Processing camp booking ID: {$campBooking->id}");
            try {
                $meeting = $zoomService->createMeeting(
                    $campBooking->camp_name ?: 'Younimatiq Session',
                    $campBooking->scheduled_at,
                    45,
                    $campBooking->timezone ?: 'UTC'
                );
                $campBooking->update(ZoomService::bookingFields($meeting));
                $this->info("Successfully generated Zoom for camp booking ID: {$campBooking->id}");
            } catch (\Exception $e) {
                $this->error("Failed camp booking {$campBooking->id}: ".$e->getMessage());
            }
        }
    }
}
