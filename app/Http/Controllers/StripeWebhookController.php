<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Setting;
use App\Services\EnrollmentService;
use App\Services\PaymentVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function handle(Request $request, EnrollmentService $enrollmentService, PaymentVerificationService $paymentVerification)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $settings = Cache::rememberForever('global_settings', function () {
            return Setting::pluck('value', 'key')->toArray();
        });
        $secret = $settings['stripe_webhook_secret'] ?? config('services.stripe.webhook_secret');

        try {
            if ($secret) {
                $event = Webhook::constructEvent($payload, $sigHeader, $secret);
            } else {
                $event = json_decode($payload);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        if (($event->type ?? '') === 'checkout.session.completed') {
            $session = $event->data->object;
            $paymentId = $session->metadata->payment_id ?? null;
            $payment = $paymentId
                ? Payment::find($paymentId)
                : Payment::where('gateway', 'stripe')->where('status', 'pending')->latest()->first();

            if ($payment && ($session->payment_status ?? '') === 'paid') {
                $paymentVerification->completePayment(
                    $payment,
                    $session->payment_intent ?? $session->id,
                    $enrollmentService
                );
            }
        }

        return response()->json(['received' => true]);
    }
}
