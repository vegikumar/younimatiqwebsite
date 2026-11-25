<?php

namespace Tests\Unit;

use App\Services\BookingWindowService;
use Carbon\Carbon;
use Tests\TestCase;

class BookingWindowServiceTest extends TestCase
{
    private BookingWindowService $windows;

    protected function setUp(): void
    {
        parent::setUp();
        $this->windows = new BookingWindowService;
    }

    public function test_preferred_starts_are_one_hour_slots_inside_ist_windows(): void
    {
        $starts = $this->windows->preferredIstStarts();

        $this->assertSame(['04:30', '05:30', '06:30', '18:30', '19:30', '20:30', '21:30', '22:30'], $starts);
        $this->assertNotContains('07:00', $starts);
        $this->assertNotContains('07:30', $starts);
        $this->assertNotContains('23:00', $starts);
        $this->assertNotContains('23:30', $starts);
    }

    public function test_window_includes_ist_evening_and_morning(): void
    {
        $evening = Carbon::createFromFormat('Y-m-d H:i', '2026-11-02 18:30', 'Asia/Kolkata')->utc();
        $morning = Carbon::createFromFormat('Y-m-d H:i', '2026-11-02 04:30', 'Asia/Kolkata')->utc();
        $midday = Carbon::createFromFormat('Y-m-d H:i', '2026-11-02 12:00', 'Asia/Kolkata')->utc();

        $this->assertTrue($this->windows->isWithinWindow($evening));
        $this->assertTrue($this->windows->isWithinWindow($morning));
        $this->assertFalse($this->windows->isWithinWindow($midday));
    }

    public function test_ist_slots_are_converted_to_the_visitors_timezone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-01 00:00:00', 'UTC'));

        $slots = $this->windows->candidateSlotsForLocalDate('2026-11-02', 'Asia/Kolkata', skipPast: false);
        $times = array_column($slots, 'local_time');

        $this->assertContains('04:30', $times);
        $this->assertContains('18:30', $times);
        $this->assertContains('22:30', $times);
        $this->assertNotContains('23:00', $times);

        $nySlots = $this->windows->candidateSlotsForLocalDate('2026-11-02', 'America/New_York', skipPast: false);
        $this->assertNotEmpty($nySlots);
        foreach ($nySlots as $slot) {
            $utc = Carbon::parse($slot['utc_iso']);
            $this->assertTrue($this->windows->isWithinWindow($utc), $slot['utc_iso'].' should stay in IST class hours');
            $this->assertSame('2026-11-02', Carbon::parse($slot['utc_iso'])->timezone('America/New_York')->toDateString());
        }

        Carbon::setTestNow();
    }

    public function test_custom_local_time_is_parsed_in_parent_timezone(): void
    {
        $utc = $this->windows->resolveFromRequest(
            '2026-11-02',
            'custom',
            'Asia/Kolkata',
            true,
            '19:00'
        );

        $this->assertSame('13:30', $utc->format('H:i'));
        $this->assertTrue($this->windows->isWithinWindow($utc));
    }
}
