<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Order;
use App\Models\Payment;
use Stripe\Stripe;
use Stripe\Refund;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class RefundService
{
    public function refundPayment(Payment $payment): array
    {
        if ($payment->status === 'refunded') {
            return ['success' => false, 'message' => 'Payment already refunded.'];
        }

        if ($payment->status !== 'completed') {
            return ['success' => false, 'message' => 'Only completed payments can be refunded.'];
        }

        $gatewayRefund = $this->processGatewayRefund($payment);

        if (!$gatewayRefund['success']) {
            return $gatewayRefund;
        }

        $payment->update(['status' => 'refunded']);

        Order::where('payment_id', $payment->id)->update(['status' => 'refunded']);

        Enrollment::where('payment_id', $payment->id)->update(['status' => 'cancelled']);

        return ['success' => true, 'message' => $gatewayRefund['message'] ?? 'Refund processed successfully.'];
    }

    private function processGatewayRefund(Payment $payment): array
    {
        if ($payment->gateway === 'manual') {
            return ['success' => true, 'message' => 'Manual refund recorded.'];
        }

        try {
            if ($payment->gateway === 'stripe' && $payment->transaction_id) {
                Stripe::setApiKey(config('services.stripe.secret'));

                if (str_starts_with($payment->transaction_id, 'cs_')) {
                    $session = \Stripe\Checkout\Session::retrieve($payment->transaction_id);
                    $paymentIntentId = $session->payment_intent;
                    if ($paymentIntentId) {
                        Refund::create(['payment_intent' => $paymentIntentId]);
                    }
                } else {
                    Refund::create(['payment_intent' => $payment->transaction_id]);
                }

                return ['success' => true];
            }

            if ($payment->gateway === 'razorpay' && $payment->transaction_id) {
                $api = new \Razorpay\Api\Api(config('services.razorpay.key'), config('services.razorpay.secret'));
                $api->payment->fetch($payment->transaction_id)->refund(['amount' => (int) round($payment->amount * 100)]);
                return ['success' => true];
            }

            if ($payment->gateway === 'paypal' && $payment->transaction_id) {
                $provider = new PayPalClient;
                $provider->setApiCredentials(config('paypal'));
                $provider->getAccessToken();
                $captureId = $this->resolvePayPalCaptureId($payment->transaction_id, $provider);
                if ($captureId) {
                    $refundCurrency = strtoupper((string) ($payment->currency ?: 'USD'));
                    $provider->refundCapturedPayment(
                        $captureId,
                        number_format((float) $payment->amount, 2, '.', ''),
                        $refundCurrency
                    );

                    return ['success' => true];
                }

                return ['success' => false, 'message' => 'Could not find PayPal capture ID for refund.'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Gateway refund failed: ' . $e->getMessage()];
        }

        return ['success' => false, 'message' => 'Unsupported payment gateway for automatic refund.'];
    }

    private function resolvePayPalCaptureId(string $transactionId, PayPalClient $provider): ?string
    {
        if (str_starts_with($transactionId, 'init_')) {
            return null;
        }

        try {
            $order = $provider->showOrderDetails($transactionId);
            $captures = $order['purchase_units'][0]['payments']['captures'] ?? [];
            return $captures[0]['id'] ?? null;
        } catch (\Exception $e) {
            return str_starts_with($transactionId, 'CAPTURE-') || strlen($transactionId) > 10 ? $transactionId : null;
        }
    }
}
