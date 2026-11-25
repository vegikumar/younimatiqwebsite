<?php

namespace App\Http\Controllers\Api\V1\Guest;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\CampBookingController;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessCampBooking;
use App\Models\CampBooking;
use App\Models\Setting;
use App\Services\CurrencyService;
use App\Services\EmailService;
use App\Services\PaymentVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;

class CampBookingCheckoutController extends Controller
{
    use RespondsWithJson;

    public function bookClass(Request $request, CampBookingController $camp)
    {
        $request->headers->set('Accept', 'application/json');

        return $camp->store($request);
    }

    public function checkout(CampBooking $booking)
    {
        if ($booking->payment_status === 'paid') {
            return $this->success([
                'booking' => $this->formatBooking($booking),
                'already_paid' => true,
            ], 'Booking is already paid.');
        }

        $settings = Cache::rememberForever('global_settings', function () {
            return Setting::pluck('value', 'key')->toArray();
        });
        $demoPrice = (float) ($settings['demo_class_price'] ?? 0);
        $currency = strtoupper($booking->currency ?: 'USD');
        $amount = (float) ($booking->amount ?: CurrencyService::convert($demoPrice, $currency));

        return $this->success([
            'booking' => $this->formatBooking($booking),
            'amount' => $amount,
            'currency' => $currency,
            'display_amount' => CurrencyService::format($demoPrice > 0 ? $demoPrice : $amount, $currency),
            'enable_stripe' => (bool) ($settings['enable_stripe'] ?? 1),
            'enable_paypal' => (bool) ($settings['enable_paypal'] ?? 1),
            'enable_razorpay' => (bool) ($settings['enable_razorpay'] ?? 1),
            'razorpay_key' => config('services.razorpay.key'),
            'already_paid' => false,
        ]);
    }

    public function createRazorpayOrder(CampBooking $booking, CampBookingController $camp)
    {
        if ($booking->payment_status === 'paid') {
            return $this->success([
                'free' => true,
                'booking_id' => $booking->id,
                'payment_status' => 'paid',
            ], 'Already paid.');
        }

        $raw = $camp->createRazorpayOrder($booking);
        $payload = $raw->getData(true);

        if ($raw->getStatusCode() >= 400) {
            return $this->error($payload['error'] ?? 'Unable to create Razorpay order.', $raw->getStatusCode());
        }

        if (($payload['status'] ?? null) === 'success') {
            return $this->success([
                'free' => true,
                'booking_id' => $payload['booking_id'] ?? $booking->id,
                'payment_status' => 'paid',
            ]);
        }

        return $this->success([
            'order_id' => $payload['order_id'],
            'amount_subunit' => $payload['amount'],
            'amount' => isset($payload['amount']) ? ((int) $payload['amount']) / 100 : null,
            'currency' => $payload['currency'],
            'razorpay_key' => $payload['key'],
            'name' => $payload['name'] ?? config('app.name'),
            'description' => $payload['description'] ?? null,
            'prefill' => $payload['prefill'] ?? [],
        ]);
    }

    public function verifyRazorpay(Request $request, CampBooking $booking, CampBookingController $camp, EmailService $emailService)
    {
        $request->headers->set('Accept', 'application/json');

        return $camp->verifyRazorpay($request, $booking, $emailService);
    }

    public function createStripeSession(CampBooking $booking)
    {
        if ($booking->payment_status === 'paid') {
            return $this->success([
                'booking_id' => $booking->id,
                'payment_status' => 'paid',
            ], 'Already paid.');
        }

        if (! config('services.stripe.secret')) {
            return $this->error('Stripe is not configured.', 422);
        }

        $booking->update(['payment_method' => 'stripe']);

        Stripe::setApiKey(config('services.stripe.secret'));

        $settings = Cache::rememberForever('global_settings', function () {
            return Setting::pluck('value', 'key')->toArray();
        });
        $demoPrice = (float) ($settings['demo_class_price'] ?? 0);
        $currencyCode = strtoupper($booking->currency ?: 'USD');
        $amountMajor = (float) ($booking->amount ?: ($demoPrice > 0 ? CurrencyService::convert($demoPrice, $currencyCode) : 0));
        $stripeSupported = ['USD', 'INR', 'EUR', 'GBP', 'AED', 'SGD', 'AUD', 'CAD'];
        if (! in_array($currencyCode, $stripeSupported, true)) {
            $rates = CurrencyService::getRates();
            $rate = $rates[$currencyCode] ?? 1.0;
            if ($rate > 0) {
                $amountMajor = round($amountMajor / $rate, 2);
            }
            $currencyCode = 'USD';
        }
        if ($amountMajor <= 0) {
            $booking->update(['payment_status' => 'paid', 'payment_method' => 'stripe', 'amount' => 0, 'currency' => $currencyCode]);
            ProcessCampBooking::dispatch($booking, 'parent');

            return $this->success([
                'booking_id' => $booking->id,
                'payment_status' => 'paid',
                'free' => true,
                'deep_links' => ['success' => 'younimatiq://camp-checkout/'.$booking->id.'?status=success'],
            ], 'No payment required.');
        }

        $unitAmount = (int) max(50, round($amountMajor * 100));

        $session = StripeSession::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($currencyCode),
                    'unit_amount' => $unitAmount,
                    'product_data' => ['name' => $booking->camp_name.' - Demo Trial'],
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => url('/api/v1/guest/booking/'.$booking->id.'/stripe-return').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => 'younimatiq://camp-checkout/'.$booking->id.'?cancelled=1',
            'customer_email' => $booking->email,
        ]);

        $booking->update([
            'amount' => $amountMajor,
            'currency' => $currencyCode,
            'payment_method' => 'stripe',
        ]);

        return $this->success([
            'checkout_url' => $session->url,
            'session_id' => $session->id,
            'booking_id' => $booking->id,
            'amount' => $amountMajor,
            'currency' => $currencyCode,
        ]);
    }

    public function payPaypal(CampBooking $booking, CampBookingController $camp)
    {
        if ($booking->payment_status === 'paid') {
            return $this->success([
                'booking_id' => $booking->id,
                'payment_status' => 'paid',
            ], 'Already paid.');
        }

        $returnUrl = url('/api/v1/guest/booking/'.$booking->id.'/paypal-return').'?status=success';
        $cancelUrl = url('/api/v1/guest/booking/'.$booking->id.'/paypal-return').'?status=cancel';
        $order = $camp->buildPaypalOrder($booking, $returnUrl, $cancelUrl);

        if (isset($order['error'])) {
            return $this->error($order['error'], $order['status'] ?? 500);
        }

        if (! empty($order['free'])) {
            return $this->success([
                'booking_id' => $booking->id,
                'payment_status' => 'paid',
                'free' => true,
                'booking' => $this->formatBooking($booking->fresh()),
                'deep_links' => [
                    'success' => 'younimatiq://camp-checkout/'.$booking->id.'?status=success',
                ],
            ], 'No payment required.');
        }

        return $this->success([
            'booking_id' => $booking->id,
            'paypal_order_id' => $order['paypal_order_id'],
            'approval_url' => $order['approval_url'],
            'checkout_url' => $order['approval_url'],
            'amount' => $order['amount'],
            'currency' => $order['currency'],
            'deep_links' => [
                'success' => 'younimatiq://camp-checkout/'.$booking->id.'?status=success',
                'cancel' => 'younimatiq://camp-checkout/'.$booking->id.'?status=cancel',
            ],
        ], 'PayPal order created.');
    }

    public function paypalReturn(Request $request, CampBooking $booking, PaymentVerificationService $paymentVerification)
    {
        if ($request->query('status') === 'cancel') {
            return redirect('younimatiq://camp-checkout/'.$booking->id.'?status=cancel');
        }

        $orderId = $request->query('token')
            ?? $request->query('paypal_order_id')
            ?? $booking->paypal_order_id;

        if ($booking->payment_status !== 'paid') {
            if (! $orderId || ! $paymentVerification->verifyPayPal($orderId)) {
                return redirect('younimatiq://camp-checkout/'.$booking->id.'?status=cancel');
            }

            $booking->update([
                'payment_method' => 'paypal',
                'payment_status' => 'paid',
                'paypal_order_id' => $orderId,
            ]);
            ProcessCampBooking::dispatch($booking, 'parent');
        }

        return redirect('younimatiq://camp-checkout/'.$booking->id.'?status=success');
    }

    public function stripeReturn(Request $request, CampBooking $booking)
    {
        if ($request->query('session_id') && config('services.stripe.secret')) {
            Stripe::setApiKey(config('services.stripe.secret'));
            $session = StripeSession::retrieve($request->query('session_id'));
            if (($session->payment_status ?? null) !== 'paid') {
                return redirect('younimatiq://camp-checkout/'.$booking->id.'?status=cancel');
            }
        }

        if ($booking->payment_status !== 'paid') {
            $booking->update([
                'payment_method' => 'stripe',
                'payment_status' => 'paid',
            ]);
            ProcessCampBooking::dispatch($booking, 'parent');
        }

        return redirect('younimatiq://camp-checkout/'.$booking->id.'?status=success');
    }

    public function show(CampBooking $booking)
    {
        return $this->success([
            'booking' => $this->formatBooking($booking),
        ]);
    }

    private function formatBooking(CampBooking $booking): array
    {
        return [
            'id' => $booking->id,
            'camp_name' => $booking->camp_name,
            'parent_name' => $booking->parent_name,
            'child_name' => $booking->child_name,
            'email' => $booking->email,
            'payment_status' => $booking->payment_status,
            'amount' => (float) ($booking->amount ?? 0),
            'currency' => $booking->currency,
            'scheduled_at' => $booking->scheduled_at?->toIso8601String(),
            'timezone' => $booking->timezone,
            'zoom_join_url' => $booking->zoom_join_url,
        ];
    }
}
