<?php

namespace Tests\Feature;

use App\Models\CampBooking;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingSlotsTest extends TestCase
{
    use RefreshDatabase;

    public function test_consultation_slots_are_returned_in_the_requested_timezone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-01 00:00:00', 'UTC'));

        $response = $this->getJson('/api/consultation-slots?date=2026-11-02&timezone=Asia/Kolkata');

        $response->assertOk()
            ->assertJsonPath('timezone', 'Asia/Kolkata')
            ->assertJsonPath('windows', '4:30–7:30 AM and 6:30–11:30 PM IST');

        $slots = $response->json('slots');
        $this->assertNotEmpty($slots);
        $this->assertSame('04:30', $slots[0]['local_time']);
        $this->assertTrue(collect($slots)->contains(fn ($slot) => $slot['local_time'] === '18:30'));

        $ny = $this->getJson('/api/consultation-slots?date=2026-11-02&timezone=America/New_York');
        $ny->assertOk();
        $nySlots = collect($ny->json('slots'));
        $this->assertTrue($nySlots->pluck('local_time')->contains('08:00'), '6:30 PM IST should appear as 8:00 AM Eastern');
        $this->assertStringNotContainsString('IST', $nySlots->pluck('label')->implode(' '));

        Carbon::setTestNow();
    }

    public function test_consultation_rejects_times_outside_the_ist_windows(): void
    {
        $response = $this->from('/book-free-session')->post('/book-consultation', [
            'email' => 'parent@example.com',
            'phone' => '9999999999',
            'whatsapp_number' => '9999999999',
            'name_c1' => 'Ada',
            'age_c1' => 10,
            'grade_c1' => '5',
            'board_c1' => 'CBSE',
            'courses_c1' => 'Math',
            'scheduled_date' => now()->addDay()->toDateString(),
            'scheduled_time' => 'custom',
            'custom_slot' => '1',
            'custom_scheduled_time' => '12:00',
            'timezone' => 'Asia/Kolkata',
            'booking_type' => 'consultation',
        ]);

        $response->assertSessionHasErrors('scheduled_time', errorBag: 'consultation');
        $this->assertDatabaseCount('camp_bookings', 0);
    }

    public function test_consultation_accepts_a_preferred_ist_slot(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-01 00:00:00', 'UTC'));
        $utc = Carbon::createFromFormat('Y-m-d H:i', '2026-11-02 18:30', 'Asia/Kolkata')->utc();

        $response = $this->post('/book-consultation', [
            'email' => 'parent@example.com',
            'phone' => '9999999999',
            'whatsapp_number' => '9999999999',
            'name_c1' => 'Ada',
            'age_c1' => 10,
            'grade_c1' => '5',
            'board_c1' => 'CBSE',
            'courses_c1' => 'Math',
            'scheduled_date' => '2026-11-02',
            'scheduled_time' => $utc->toIso8601String(),
            'timezone' => 'Asia/Kolkata',
            'booking_type' => 'consultation',
        ]);

        $booking = CampBooking::first();
        $this->assertNotNull($booking);
        $this->assertTrue($booking->scheduled_at->equalTo($utc));
        $response->assertRedirect(route('booking.success', $booking));

        Carbon::setTestNow();
    }
}
