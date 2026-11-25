<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCampBooking;
use App\Models\CampBooking;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\EnrollmentService;
use App\Services\PaymentVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RazorpayWebhookController extends Controller
{
    /**
     * Razorpay sends payment.captured / payment.failed events with an
     * HMAC-SHA256 signature over the raw payload, keyed by the webhook
     * secret saved in Admin → Settings.
     */
    public function handle(Request $request, EnrollmentService $enrollmentService, PaymentVerificationService $paymentVerification)
    {
        $settings = Cache::rememberForever('global_settings', function () {
            return Setting::pluck('value', 'key')->toArray();
        });
        $secret = $settings['razorpay_webhook_secret'] ?? null;

        if ($secret) {
            $signature = $request->header('X-Razorpay-Signature');
            $expected = hash_hmac('sha256', $request->getContent(), $secret);

            if (! hash_equals($expected, (string) $signature)) {
                Log::warning('Razorpay webhook signature verification failed', [
                    'event' => $request->header('X-Razorpay-Event'),
                ]);

                return response()->json(['error' => 'Invalid signature'], 400);
            }
        }

        $event = json_decode($request->getContent());
        $eventName = $request->header('X-Razorpay-Event') ?? ($event->event ?? '');

        if ($eventName !== 'payment.captured' && $eventName !== 'payment.failed') {
            return response()->json(['received' => true]);
        }

        $paymentEntity = $event->payment->entity ?? null;
        if (! $paymentEntity) {
            return response()->json(['received' => true]);
        }

        $orderId = $paymentEntity->order_id ?? null;
        $rzpPaymentId = $paymentEntity->id ?? null;

        if ($eventName === 'payment.failed') {
            if ($orderId) {
                Payment::where('gateway', 'razorpay')
                    ->where('transaction_id', $orderId)
                    ->where('status', 'pending')
                    ->update(['status' => 'failed']);
            }

            return response()->json(['received' => true]);
        }

        // payment.captured
        if (! $orderId) {
            return response()->json(['received' => true]);
        }

        // Course-enrollment payment (transaction_id stores the Razorpay order id).
        $payment = Payment::where('gateway', 'razorpay')
            ->where('transaction_id', $orderId)
            ->where('status', 'pending')
            ->first();

        if ($payment) {
            $paymentVerification->completePayment($payment, (string) $rzpPaymentId, $enrollmentService);

            return response()->json(['received' => true]);
        }

        // Camp demo booking (razorpay_order_id column).
        $booking = CampBooking::where('razorpay_order_id', $orderId)->first();
        if ($booking && $booking->payment_status !== 'paid') {
            $booking->update([
                'payment_status' => 'paid',
                'razorpay_payment_id' => $rzpPaymentId,
            ]);
            ProcessCampBooking::dispatch($booking, 'parent');
        }

        return response()->json(['received' => true]);
    }
}
