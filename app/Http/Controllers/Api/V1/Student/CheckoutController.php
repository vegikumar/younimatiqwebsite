<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\BundlePricingService;
use App\Services\CurrencyService;
use App\Services\ReEngagementService;
use App\Services\SiblingPricingService;
use App\Services\StudentApiService;
use App\Services\TimezoneService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api as RazorpayApi;
use Srmklive\PayPal\Services\PayPal;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;

class CheckoutController extends Controller
{
    use RespondsWithJson;

    public function show(Request $request, Course $course)
    {
        $enableStripe = (bool) (Setting::where('key', 'enable_stripe')->value('value') ?? 1);
        $enablePaypal = (bool) (Setting::where('key', 'enable_paypal')->value('value') ?? 1);
        $enableRazorpay = (bool) (Setting::where('key', 'enable_razorpay')->value('value') ?? 1);

        $currency = strtoupper($request->input('currency', 'USD'));
        $pricingModel = $request->input('pricing_model', 'full');
        $baseUsd = (float) $course->price;
        if ($pricingModel === 'per_class' && (float) ($course->per_class_price ?? 0) > 0) {
            $baseUsd = (float) $course->per_class_price;
        }

        $originalUsd = $baseUsd;
        $discountUsd = 0;
        $finalUsd = $originalUsd;
        $couponCode = $request->query('coupon') ?? $request->query('coupon_code');
        $couponError = null;

        if ($couponCode) {
            $coupon = Coupon::where('code', strtoupper($couponCode))->first();
            if ($coupon && $coupon->isValid($originalUsd)) {
                $finalUsd = $coupon->applyDiscount($originalUsd);
                $discountUsd = $originalUsd - $finalUsd;
            } else {
                $couponError = 'Invalid or expired coupon code.';
            }
        }

        $student = $request->user();
        $sibling = app(SiblingPricingService::class)->forStudent($student, $finalUsd);
        if ($sibling['has_sibling_discount']) {
            $finalUsd = (float) $sibling['final_price'];
            $discountUsd = $originalUsd - $finalUsd;
        }
        $bundle = app(BundlePricingService::class)->forStudentCourse($student, $course, $finalUsd);
        if ($bundle['has_bundle_discount']) {
            $finalUsd = (float) $bundle['final_price'];
            $discountUsd = $originalUsd - $finalUsd;
        }

        try {
            if ($student) {
                app(ReEngagementService::class)->trackInterest($student->id, $course->id);
            }
        } catch (\Throwable $e) {
            Log::warning('trackInterest failed: '.$e->getMessage());
        }

        $originalPrice = CurrencyService::convert($originalUsd, $currency);
        $finalPrice = CurrencyService::convert($finalUsd, $currency);
        $discountAmount = max(0, $originalPrice - $finalPrice);
        $perClassPrice = (float) ($course->per_class_price ?? 0) > 0
            ? CurrencyService::convert((float) $course->per_class_price, $currency)
            : 0.0;

        $deliveryModes = $course->availableDeliveryModes();
        $selectedDeliveryMode = (string) $request->input('delivery_mode', '');
        if (! in_array($selectedDeliveryMode, $deliveryModes, true)) {
            $selectedDeliveryMode = $deliveryModes[0] ?? 'live';
        }

        return $this->success([
            'course' => app(StudentApiService::class)->formatCourse($course),
            'pricing' => [
                'original_price' => $originalPrice,
                'final_price' => $finalPrice,
                'discount_amount' => $discountAmount,
                'currency' => $currency,
                'pricing_model' => $pricingModel,
                'per_class_price' => $perClassPrice,
                'amount' => $finalPrice,
                'sibling_discount' => $sibling,
                'bundle_discount' => $bundle,
            ],
            'delivery_modes' => $deliveryModes,
            'selected_delivery_mode' => $selectedDeliveryMode,
            'number_of_classes' => max(1, (int) ($course->number_of_classes ?? 1)),
            'schedule' => [
                'default_start_date' => now()->addDay()->toDateString(),
                'default_time' => '17:00',
                'default_timezone' => $request->user()->timezone ?? TimezoneService::sanitizeTimezone(null),
                'timezones' => collect(TimezoneService::commonTimezones())
                    ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                    ->values(),
                'days' => [
                    ['value' => 1, 'label' => 'Monday'],
                    ['value' => 2, 'label' => 'Tuesday'],
                    ['value' => 3, 'label' => 'Wednesday'],
                    ['value' => 4, 'label' => 'Thursday'],
                    ['value' => 5, 'label' => 'Friday'],
                    ['value' => 6, 'label' => 'Saturday'],
                    ['value' => 0, 'label' => 'Sunday'],
                ],
            ],
            'gateways' => [
                'stripe' => $enableStripe,
                'paypal' => $enablePaypal,
                'razorpay' => $enableRazorpay,
            ],
            'paypal_client_id' => config('services.paypal.client_id'),
            'razorpay_key' => config('services.razorpay.key'),
            'timezones' => TimezoneService::commonTimezones(),
            'coupon_code' => $couponCode,
            'coupon_error' => $couponError,
        ]);
    }

    public function createRazorpayOrder(Request $request, Course $course)
    {
        $enableRazorpay = (bool) (Setting::where('key', 'enable_razorpay')->value('value') ?? 1);
        if (! $enableRazorpay) {
            return $this->error('Razorpay is disabled.', 400);
        }

        // Razorpay India accounts charge INR only (USD/AED go through Stripe).
        $currency = \App\Support\PaymentGatewayCurrency::forRazorpay();
        $amount = $this->getFinalAmount($request, $course, $currency);
        $amountSubunit = (int) round($amount * 100);

        try {
            $api = new RazorpayApi(config('services.razorpay.key'), config('services.razorpay.secret'));
            $order = $api->order->create([
                'receipt' => 'course_'.$course->id.'_'.time(),
                'amount' => $amountSubunit,
                'currency' => $currency,
            ]);

            $payment = Payment::create([
                'student_id' => $request->user()->id,
                'course_id' => $course->id,
                'amount' => $amount,
                'currency' => $currency,
                'status' => 'pending',
                'gateway' => 'razorpay',
                'transaction_id' => $order->id,
                'pricing_model' => $request->input('pricing_model', 'full'),
                'delivery_mode' => $this->resolveDeliveryMode($request, $course),
                'schedule' => $this->buildSchedule($request, $course),
            ]);

            return $this->success([
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'amount' => $amount,
                'amount_subunit' => $amountSubunit,
                'currency' => $currency,
                'razorpay_key' => config('services.razorpay.key'),
                'order' => [
                    'id' => $order->id,
                    'amount' => $amountSubunit,
                    'currency' => $currency,
                ],
                'deep_links' => [
                    'success' => 'younimatiq://payment/success?payment_id='.$payment->id,
                    'cancel' => 'younimatiq://payment/cancel?payment_id='.$payment->id,
                ],
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function createStripeSession(Request $request, Course $course)
    {
        $enableStripe = (bool) (Setting::where('key', 'enable_stripe')->value('value') ?? 1);
        if (! $enableStripe) {
            return $this->error('Stripe is disabled.', 400);
        }

        $currency = \App\Support\PaymentGatewayCurrency::forStripe($request->input('currency', 'USD'));
        $amount = $this->getFinalAmount($request, $course, $currency);

        $payment = Payment::create([
            'student_id' => $request->user()->id,
            'course_id' => $course->id,
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'pending',
            'gateway' => 'stripe',
            'transaction_id' => 'init_'.uniqid(),
            'pricing_model' => $request->input('pricing_model', 'full'),
            'delivery_mode' => $this->resolveDeliveryMode($request, $course),
            'schedule' => $this->buildSchedule($request, $course),
        ]);

        Stripe::setApiKey(config('services.stripe.secret'));

        $unitAmount = (int) max(
            \App\Support\PaymentGatewayCurrency::stripeMinMinor($currency),
            round($amount * 100)
        );
        $session = StripeSession::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($currency),
                    'unit_amount' => $unitAmount,
                    'product_data' => ['name' => $course->title],
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'metadata' => ['payment_id' => (string) $payment->id, 'display_currency' => $currency],
            'success_url' => url('/api/v1/student/payments/stripe-return?status=success&payment_id='.$payment->id.'&session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url' => url('/api/v1/student/payments/stripe-return?status=cancel&payment_id='.$payment->id),
        ]);

        return $this->success([
            'payment_id' => $payment->id,
            'checkout_url' => $session->url,
            'session_id' => $session->id,
            'deep_links' => [
                'success' => 'younimatiq://payment/success?payment_id='.$payment->id,
                'cancel' => 'younimatiq://payment/cancel?payment_id='.$payment->id,
            ],
        ]);
    }

    public function createPaypalOrder(Request $request, Course $course)
    {
        $enablePaypal = (bool) (Setting::where('key', 'enable_paypal')->value('value') ?? 1);
        if (! $enablePaypal) {
            return $this->error('PayPal is disabled.', 400);
        }

        $currency = strtoupper($request->input('currency', 'USD'));
        $paypalSupported = ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 'SGD', 'NZD', 'CHF', 'HKD'];
        if (! in_array($currency, $paypalSupported, true)) {
            $currency = 'USD';
        }

        $amount = $this->getFinalAmount($request, $course, $currency);
        $formattedAmount = number_format((float) $amount, 2, '.', '');

        $payment = Payment::create([
            'student_id' => $request->user()->id,
            'course_id' => $course->id,
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'pending',
            'gateway' => 'paypal',
            'transaction_id' => 'init_'.uniqid(),
            'pricing_model' => $request->input('pricing_model', 'full'),
            'delivery_mode' => $this->resolveDeliveryMode($request, $course),
            'schedule' => $this->buildSchedule($request, $course),
        ]);

        try {
            $provider = new PayPal;
            $provider->setApiCredentials(config('paypal'));
            $provider->getAccessToken();

            $order = $provider->createOrder([
                'intent' => 'CAPTURE',
                'application_context' => [
                    'brand_name' => 'Younimatiq',
                    'user_action' => 'PAY_NOW',
                    'return_url' => url('/api/v1/student/payments/paypal-return?status=success&payment_id='.$payment->id),
                    'cancel_url' => url('/api/v1/student/payments/paypal-return?status=cancel&payment_id='.$payment->id),
                ],
                'purchase_units' => [[
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => $formattedAmount,
                    ],
                    'description' => substr($course->title, 0, 120),
                    'custom_id' => (string) $payment->id,
                ]],
            ]);

            if (empty($order['id'])) {
                $payment->update(['status' => 'failed']);
                $errorMsg = $order['message'] ?? ($order['error_description'] ?? 'Failed to create PayPal order');

                return $this->error($errorMsg, 500);
            }

            $paypalOrderId = $order['id'];
            $payment->update(['transaction_id' => $paypalOrderId]);

            $approvalUrl = null;
            foreach ($order['links'] ?? [] as $link) {
                if (($link['rel'] ?? '') === 'approve') {
                    $approvalUrl = $link['href'] ?? null;
                    break;
                }
            }

            if (! $approvalUrl) {
                $payment->update(['status' => 'failed']);

                return $this->error('PayPal approval URL missing.', 500);
            }

            return $this->success([
                'payment_id' => $payment->id,
                'paypal_order_id' => $paypalOrderId,
                'amount' => $formattedAmount,
                'currency' => $currency,
                'approval_url' => $approvalUrl,
                'checkout_url' => $approvalUrl,
                'deep_links' => [
                    'success' => 'younimatiq://payment/success?payment_id='.$payment->id.'&gateway=paypal&paypal_order_id='.urlencode($paypalOrderId),
                    'cancel' => 'younimatiq://payment/cancel?payment_id='.$payment->id.'&gateway=paypal',
                ],
            ]);
        } catch (\Exception $e) {
            $payment->update(['status' => 'failed']);
            Log::error('API PayPal Order creation error: '.$e->getMessage());

            return $this->error('PayPal error: '.$e->getMessage(), 500);
        }
    }

    private function resolveDeliveryMode(Request $request, Course $course): string
    {
        $modes = $course->availableDeliveryModes();
        $mode = (string) $request->input('delivery_mode', '');

        return in_array($mode, $modes, true) ? $mode : ($modes[0] ?? 'live');
    }

    private function getFinalAmount(Request $request, Course $course, string $currency): float
    {
        $numberOfClasses = max(1, (int) ($course->number_of_classes ?? 1));
        $classesCount = (int) $request->input('classes', 0);
        $isCustomClasses = $classesCount > 0 && $classesCount !== $numberOfClasses;

        if ($isCustomClasses) {
            $classesCount = min($classesCount, 48);
            $perClass = (float) ($course->per_class_price ?? 0);
            if ($perClass <= 0) {
                $perClass = (float) $course->price > 0 ? (float) $course->price / $numberOfClasses : 0.0;
            }
            $amount = $perClass * $classesCount;
        } else {
            $amount = (float) $course->price;
            if ($request->input('pricing_model') === 'per_class' && $course->per_class_price > 0) {
                $amount = (float) $course->per_class_price;
            }
        }

        if ($request->filled('coupon_code')) {
            $coupon = Coupon::where('code', strtoupper($request->coupon_code))->first();
            if ($coupon && $coupon->isValid($amount)) {
                $amount = $coupon->applyDiscount($amount);
                $coupon->increment('used_count');
            }
        }

        $student = $request->user();
        if ($student) {
            $sibling = app(SiblingPricingService::class)->forStudent($student, $amount);
            $amount = (float) $sibling['final_price'];
            $bundle = app(BundlePricingService::class)->forStudentCourse($student, $course, $amount);
            $amount = (float) $bundle['final_price'];
        }

        return CurrencyService::convert(max(0, $amount), $currency);
    }

    /**
     * Build the payment schedule payload from API checkout input.
     */
    private function buildSchedule(Request $request, Course $course): ?array
    {
        $numberOfClasses = max(1, (int) ($course->number_of_classes ?? 1));
        $classes = (int) $request->input('classes', 0);
        $pricingModel = (string) $request->input('pricing_model', 'full');

        if ($classes <= 0) {
            $classes = $pricingModel === 'per_class' ? 1 : $numberOfClasses;
        } elseif ($pricingModel === 'per_class' && $classes === $numberOfClasses) {
            $classes = 1;
        }
        $classes = min($classes, 48);

        $days = $request->input('schedule_days', []);
        if (! is_array($days)) {
            $days = [];
        }
        $days = array_values(array_unique(array_map('intval', $days)));
        $days = array_values(array_filter($days, fn ($d) => $d >= 0 && $d <= 6));

        $times = $request->input('schedule_times', []);
        if (! is_array($times)) {
            $times = [];
        }
        $cleanTimes = [];
        foreach ($times as $day => $time) {
            if ($time !== null && $time !== '') {
                $cleanTimes[(int) $day] = (string) $time;
            }
        }

        return [
            'classes' => $classes,
            'start_date' => $request->input('schedule_start_date') ?: null,
            'days' => $days,
            'times' => $cleanTimes,
            'timezone' => TimezoneService::sanitizeTimezone($request->input('timezone')),
        ];
    }
}
