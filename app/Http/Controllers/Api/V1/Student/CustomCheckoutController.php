<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\BundlePricingService;
use App\Services\ClassScheduleService;
use App\Services\CurrencyService;
use App\Services\SiblingPricingService;
use App\Services\StudentApiService;
use App\Services\TimezoneService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Razorpay\Api\Api as RazorpayApi;
use Srmklive\PayPal\Services\PayPal;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;

/**
 * Custom-price checkout using the same signed query params as web /custom-enroll/{course}.
 */
class CustomCheckoutController extends Controller
{
    use RespondsWithJson;

    public function show(Request $request, Course $course)
    {
        if (! $this->hasValidCustomSignature($request, $course)) {
            return $this->error('This custom enrollment link is invalid or expired.', 403);
        }

        $customPrice = (float) $request->query('price');
        $currency = strtoupper((string) $request->query('currency', 'USD'));
        $finalPrice = $customPrice;
        $discountAmount = 0;
        $couponCode = $request->query('coupon') ?? $request->query('coupon_code');
        $couponError = null;

        if ($couponCode) {
            $coupon = Coupon::where('code', strtoupper($couponCode))->first();
            if ($coupon && $coupon->isValid($customPrice)) {
                $finalPrice = $coupon->applyDiscount($customPrice);
                $discountAmount = $customPrice - $finalPrice;
            } else {
                $couponError = 'Invalid or expired coupon code.';
            }
        }

        $student = $request->user();
        $sibling = app(SiblingPricingService::class)->forStudent($student, $finalPrice);
        if ($sibling['has_sibling_discount']) {
            $finalPrice = (float) $sibling['final_price'];
            $discountAmount = $customPrice - $finalPrice;
        }
        $bundle = app(BundlePricingService::class)->forStudentCourse($student, $course, $finalPrice);
        if ($bundle['has_bundle_discount']) {
            $finalPrice = (float) $bundle['final_price'];
            $discountAmount = $customPrice - $finalPrice;
        }

        return $this->success([
            'course' => app(StudentApiService::class)->formatCourse($course),
            'custom' => true,
            'pricing' => [
                'original_price' => $customPrice,
                'final_price' => $finalPrice,
                'discount_amount' => $discountAmount,
                'currency' => $currency,
                'pricing_model' => 'custom',
                'per_class_price' => 0,
                'amount' => $finalPrice,
                'sibling_discount' => $sibling,
                'bundle_discount' => $bundle,
            ],
            'signed' => [
                'price' => $request->query('price'),
                'currency' => $request->query('currency'),
                'expires' => $request->query('expires'),
                'signature' => $request->query('signature'),
            ],
            'gateways' => [
                'stripe' => (bool) (Setting::where('key', 'enable_stripe')->value('value') ?? 1),
                'paypal' => (bool) (Setting::where('key', 'enable_paypal')->value('value') ?? 1),
                'razorpay' => (bool) (Setting::where('key', 'enable_razorpay')->value('value') ?? 1),
            ],
            'paypal_client_id' => config('services.paypal.client_id'),
            'razorpay_key' => config('services.razorpay.key'),
            'timezones' => collect(TimezoneService::commonTimezones())
                ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                ->values(),
            'number_of_classes' => (int) ($request->query('classes') ?? $course->number_of_classes ?? 1),
            'schedule' => [
                'default_start_date' => now()->addDay()->toDateString(),
                'default_time' => '17:00',
                'default_timezone' => $request->user()->timezone ?? TimezoneService::sanitizeTimezone(null),
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
            'coupon_code' => $couponCode,
            'coupon_error' => $couponError,
        ]);
    }

    public function createRazorpayOrder(Request $request, Course $course)
    {
        if (! $this->hasValidCustomSignature($request, $course)) {
            return $this->error('This custom enrollment link is invalid or expired.', 403);
        }
        if (! (bool) (Setting::where('key', 'enable_razorpay')->value('value') ?? 1)) {
            return $this->error('Razorpay is disabled.', 400);
        }

        $offerCurrency = strtoupper((string) ($request->input('currency') ?: $request->query('currency', 'USD')));
        $amount = $this->resolveCustomAmount($request, $course);
        $currency = \App\Support\PaymentGatewayCurrency::forRazorpay();
        if ($offerCurrency !== $currency) {
            $amount = \App\Services\CurrencyService::convertBetween($amount, $offerCurrency, $currency);
        }
        $amount = max(1, $amount);
        $amountSubunit = (int) round($amount * 100);

        try {
            $api = new RazorpayApi(config('services.razorpay.key'), config('services.razorpay.secret'));
            $order = $api->order->create([
                'receipt' => 'custom_'.$course->id.'_'.time(),
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
                'pricing_model' => 'custom',
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
        if (! $this->hasValidCustomSignature($request, $course)) {
            return $this->error('This custom enrollment link is invalid or expired.', 403);
        }
        if (! (bool) (Setting::where('key', 'enable_stripe')->value('value') ?? 1)) {
            return $this->error('Stripe is disabled.', 400);
        }

        $currency = \App\Support\PaymentGatewayCurrency::forStripe(
            $request->input('currency') ?: $request->query('currency', 'USD')
        );
        $amount = $this->resolveCustomAmount($request, $course);

        $payment = Payment::create([
            'student_id' => $request->user()->id,
            'course_id' => $course->id,
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'pending',
            'gateway' => 'stripe',
            'transaction_id' => 'init_'.uniqid(),
            'pricing_model' => 'custom',
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
        if (! $this->hasValidCustomSignature($request, $course)) {
            return $this->error('This custom enrollment link is invalid or expired.', 403);
        }
        if (! (bool) (Setting::where('key', 'enable_paypal')->value('value') ?? 1)) {
            return $this->error('PayPal is disabled.', 400);
        }

        $originalCurrency = strtoupper((string) ($request->input('currency') ?: $request->query('currency', 'USD')));
        $paypalSupported = ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 'SGD', 'NZD', 'CHF', 'HKD'];
        $currency = $originalCurrency;

        $amount = $this->resolveCustomAmount($request, $course);

        if (! in_array($currency, $paypalSupported, true)) {
            $rates = CurrencyService::getRates();
            $rate = $rates[$originalCurrency] ?? 1.0;
            if ($rate > 0) {
                $amount = round($amount / $rate, 2);
            }
            $currency = 'USD';
        }

        $formattedAmount = number_format((float) $amount, 2, '.', '');

        $payment = Payment::create([
            'student_id' => $request->user()->id,
            'course_id' => $course->id,
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'pending',
            'gateway' => 'paypal',
            'transaction_id' => 'init_'.uniqid(),
            'pricing_model' => 'custom',
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

                return $this->error($order['message'] ?? 'Failed to create PayPal order', 500);
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

            return $this->error('PayPal error: '.$e->getMessage(), 500);
        }
    }

    private function resolveCustomAmount(Request $request, ?Course $course = null): float
    {
        $amount = (float) ($request->query('price') ?? $request->input('price', 0));
        if ($request->filled('coupon_code')) {
            $coupon = Coupon::where('code', strtoupper($request->coupon_code))->first();
            if ($coupon && $coupon->isValid($amount)) {
                $amount = $coupon->applyDiscount($amount);
                $coupon->increment('used_count');
            }
        }

        $student = $request->user();
        if ($student) {
            $amount = (float) app(SiblingPricingService::class)->forStudent($student, $amount)['final_price'];
            if ($course) {
                $amount = (float) app(BundlePricingService::class)->forStudentCourse($student, $course, $amount)['final_price'];
            }
        }

        return max(0, $amount);
    }

    private function resolveDeliveryMode(Request $request, Course $course): string
    {
        $modes = $course->availableDeliveryModes();
        $mode = (string) $request->input('delivery_mode', '');

        return in_array($mode, $modes, true) ? $mode : ($modes[0] ?? 'live');
    }

    /**
     * Build the schedule payload from custom-checkout input. Returns null when
     * no recurring days/start date were supplied (falls back to ad-hoc booking).
     */
    private function buildSchedule(Request $request, Course $course): ?array
    {
        $classes = (int) ($request->input('classes') ?? $request->query('classes') ?? 0);
        if ($classes <= 0) {
            $classes = max(1, (int) ($course->number_of_classes ?? 1));
        }
        $classes = min($classes, 48);

        return app(ClassScheduleService::class)->normalize([
            'classes' => $classes,
            'start_date' => $request->input('schedule_start_date') ?? $request->input('start_date'),
            'days' => $request->input('schedule_days', []),
            'times' => $request->input('schedule_times', []),
            'timezone' => $request->input('timezone'),
        ]);
    }

    /**
     * Verify the admin-generated web signed URL params against /custom-enroll/{course}.
     */
    private function hasValidCustomSignature(Request $request, Course $course): bool
    {
        $expires = $request->query('expires') ?? $request->input('expires');
        $signature = $request->query('signature') ?? $request->input('signature');
        $price = $request->query('price') ?? $request->input('price');
        $currency = $request->query('currency') ?? $request->input('currency');

        if (! $expires || ! $signature || $price === null || $currency === null) {
            return false;
        }

        $checkUrl = url('/custom-enroll/'.$course->getRouteKey()).'?'.http_build_query([
            'currency' => $currency,
            'expires' => $expires,
            'price' => $price,
            'signature' => $signature,
        ]);

        return URL::hasValidSignature(Request::create($checkUrl));
    }
}
