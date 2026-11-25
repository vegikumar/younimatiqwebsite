<?php

namespace Tests\Feature;

use App\Models\CampBooking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampBookingPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_camp_booking_creation_and_checkout_page(): void
    {
        $booking = CampBooking::create([
            'parent_name' => 'John Doe',
            'child_name' => 'Jane Doe',
            'camp_name' => 'Robotics Summer Camp',
            'email' => 'john@example.com',
            'phone' => '1234567890',
            'age' => '8-10',
            'state' => 'California',
            'country' => 'United States',
            'payment_status' => 'pending',
            'amount' => 1.00,
            'currency' => 'USD',
        ]);

        $response = $this->get(route('booking.checkout', $booking));
        $response->assertStatus(200);
        $response->assertSee('PayPal');
        $response->assertSee('Stripe');
        $response->assertSee('Razorpay');
    }

    public function test_payment_via_paypal(): void
    {
        $booking = CampBooking::create([
            'parent_name' => 'John Doe',
            'child_name' => 'Jane Doe',
            'camp_name' => 'Robotics Summer Camp',
            'email' => 'john@example.com',
            'phone' => '1234567890',
            'age' => '8-10',
            'state' => 'California',
            'payment_status' => 'pending',
            'amount' => 1.00,
            'currency' => 'USD',
        ]);

        $response = $this->post(route('booking.pay', $booking), [
            'payment_method' => 'paypal',
        ]);

        $response->assertRedirect(route('booking.success', $booking));
        $this->assertDatabaseHas('camp_bookings', [
            'id' => $booking->id,
            'payment_method' => 'paypal',
            'payment_status' => 'paid',
        ]);
    }

    public function test_invalid_payment_method_rejected(): void
    {
        $booking = CampBooking::create([
            'parent_name' => 'John Doe',
            'child_name' => 'Jane Doe',
            'camp_name' => 'Robotics Summer Camp',
            'email' => 'john@example.com',
            'phone' => '1234567890',
            'age' => '8-10',
            'state' => 'California',
            'payment_status' => 'pending',
            'amount' => 1.00,
            'currency' => 'USD',
        ]);

        $response = $this->post(route('booking.pay', $booking), [
            'payment_method' => 'invalid_gateway',
        ]);

        $response->assertSessionHasErrors(['payment_method']);
    }
}
