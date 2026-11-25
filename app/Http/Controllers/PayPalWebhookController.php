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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayPalWebhookController extends Controller
{
    /**
     * PayPal sends CHECKOUT.ORDER.APPROVED / PAYMENT.CAPTURE.COMPLETED events.
     * We verify the transmission signature via PayPal's verify-webhook-signature
     * API using the webhook ID saved in Admin → Settings.
     */
    public function handle(Request $request, EnrollmentService $enrollmentService, PaymentVerificationService $paymentVerification)
    {
        $settings = Cache::rememberForever('global_settings', function () {
            return Setting::pluck('value', 'key')->toArray();
        });
        $webhookId = $settings['paypal_webhook_id'] ?? null;

        // No webhook ID configured → accept (browser-return remains the primary path).
        // Webhook ID set → signature must verify; fail closed if credentials/verify fail.
        if ($webhookId && ! $this->verifySignature($request, $webhookId)) {
            Log::warning('PayPal webhook signature verification failed', [
                'transmission_id' => $request->header('PayPal-Transmission-Id'),
            ]);

            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $event = json_decode($request->getContent());
        $type = $event->event_type ?? '';

        if (! in_array($type, ['PAYMENT.CAPTURE.COMPLETED', 'CHECKOUT.ORDER.APPROVED', 'PAYMENT.CAPTURE.DENIED'], true)) {
            return response()->json(['received' => true]);
        }

        $resource = $event->resource ?? null;
        if (! $resource) {
            return response()->json(['received' => true]);
        }

        // Capture events carry the order id in supplementary_data or purchase_units links;
        // parent_id is present on many capture payloads; order events use id.
        $orderId = $resource->supplementary_data->related_ids->order_id
            ?? $resource->parent_id
            ?? $resource->id
            ?? null;

        if ($type === 'PAYMENT.CAPTURE.DENIED') {
            if ($orderId) {
                Payment::where('gateway', 'paypal')
                    ->where('transaction_id', $orderId)
                    ->where('status', 'pending')
                    ->update(['status' => 'failed']);

                CampBooking::where('paypal_order_id', $orderId)
                    ->where('payment_status', 'pending')
                    ->update(['payment_status' => 'failed']);
            }

            return response()->json(['received' => true]);
        }

        if ($type === 'CHECKOUT.ORDER.APPROVED' && $orderId) {
            // Capture the order server-side (browser may have closed before redirect).
            $captured = $paymentVerification->verifyPayPal($orderId);
            if (! $captured) {
                return response()->json(['received' => true]);
            }
        }

        if ($orderId) {
            $this->completeForOrderId($orderId, $resource, $enrollmentService, $paymentVerification);
        }

        return response()->json(['received' => true]);
    }

    private function completeForOrderId(string $orderId, object $resource, EnrollmentService $enrollmentService, PaymentVerificationService $paymentVerification): void
    {
        // Course-enrollment payment (Student checkout flow).
        $payment = Payment::where('gateway', 'paypal')
            ->where('transaction_id', $orderId)
            ->where('status', 'pending')
            ->first();

        if ($payment) {
            $captureId = $resource->id ?? $orderId;
            $paymentVerification->completePayment($payment, (string) $captureId, $enrollmentService);

            return;
        }

        // Camp / demo booking paid with PayPal.
        $booking = CampBooking::where('paypal_order_id', $orderId)->first();

        if (! $booking) {
            $bookingId = $resource->custom_id
                ?? ($resource->purchase_units[0]->custom_id ?? null)
                ?? ($resource->metadata->booking_id ?? null)
                ?? null;
            if ($bookingId) {
                $booking = CampBooking::find($bookingId);
            }
        }

        if ($booking && $booking->payment_status !== 'paid') {
            $booking->update([
                'payment_status' => 'paid',
                'payment_method' => 'paypal',
                'paypal_order_id' => $orderId,
            ]);
            ProcessCampBooking::dispatch($booking, 'parent');
        }
    }

    private function verifySignature(Request $request, string $webhookId): bool
    {
        $clientId = config('services.paypal.client_id');
        $secret = config('services.paypal.secret');

        if (! $clientId || ! $secret) {
            Log::warning('PayPal webhook ID is set but API credentials are missing; rejecting event.');

            return false;
        }

        try {
            $base = config('paypal.mode') === 'sandbox'
                ? 'https://api-m.sandbox.paypal.com'
                : 'https://api-m.paypal.com';

            $tokenRes = Http::withBasicAuth($clientId, $secret)
                ->asForm()
                ->post($base.'/v1/oauth2/token', ['grant_type' => 'client_credentials']);

            $token = $tokenRes->json('access_token');
            if (! $token) {
                Log::warning('PayPal webhook OAuth failed; rejecting event.');

                return false;
            }

            $res = Http::withToken($token)->post(
                $base.'/v1/notifications/verify-webhook-signature',
                [
                    'auth_algo' => $request->header('PayPal-Auth-Algo'),
                    'cert_url' => $request->header('PayPal-Cert-Url'),
                    'transmission_id' => $request->header('PayPal-Transmission-Id'),
                    'transmission_sig' => $request->header('PayPal-Transmission-Sig'),
                    'transmission_time' => $request->header('PayPal-Transmission-Time'),
                    'webhook_id' => $webhookId,
                    'webhook_event' => json_decode($request->getContent(), true),
                ]
            );

            return ($res->json('verification_status') ?? '') === 'SUCCESS';
        } catch (\Throwable $e) {
            Log::error('PayPal webhook verification error: '.$e->getMessage());

            return false;
        }
    }
}
