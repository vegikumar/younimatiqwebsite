<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Http\Request;
use Razorpay\Api\Api as RazorpayApi;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PaymentVerificationService
{
    public function verifyStripe(string $sessionId): bool
    {
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
        $session = \Stripe\Checkout\Session::retrieve($sessionId);

        return $session->payment_status === 'paid';
    }

    public function verifyRazorpay(Request $request): bool
    {
        $signature = $request->input('razorpay_signature');
        $paymentId = $request->input('razorpay_payment_id');
        $orderId = $request->input('razorpay_order_id');

        if (!$signature || !$paymentId || !$orderId) {
            return false;
        }

        try {
            $api = new RazorpayApi(config('services.razorpay.key'), config('services.razorpay.secret'));
            $api->utility->verifyPaymentSignature([
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $signature,
            ]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function verifyPayPal(string $orderId): bool
    {
        try {
            $provider = new PayPalClient;
            $provider->setApiCredentials(config('paypal'));
            $provider->getAccessToken();
            $response = $provider->showOrderDetails($orderId);

            $status = $response['status'] ?? '';
            if ($status === 'COMPLETED') {
                return true;
            }

            if ($status === 'APPROVED') {
                $capture = $provider->capturePaymentOrder($orderId);
                $captureStatus = $capture['status'] ?? '';

                return in_array($captureStatus, ['COMPLETED', 'APPROVED'], true);
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function completePayment(Payment $payment, string $transactionId, EnrollmentService $enrollmentService): void
    {
        if ($payment->status === 'completed') {
            return;
        }

        $payment->update([
            'status' => 'completed',
            'transaction_id' => $transactionId,
        ]);

        $enrollmentService->enrollFromPayment($payment);

        \App\Models\Booking::where('payment_id', $payment->id)
            ->where('status', 'pending')
            ->update(['status' => 'confirmed']);
    }
}
