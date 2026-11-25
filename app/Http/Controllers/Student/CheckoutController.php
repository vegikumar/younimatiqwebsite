<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use App\Models\CourseBundle;
use App\Services\BundlePricingService;
use App\Services\EmailService;
use App\Services\ReEngagementService;
use App\Services\SiblingPricingService;
use App\Services\TimezoneService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Razorpay\Api\Api as RazorpayApi;
use Srmklive\PayPal\Services\PayPal;
use Stripe\Checkout\Session as StripeSession;
use App\Support\StudentInertiaProps;
use Inertia\Inertia;
use Stripe\Stripe;

class CheckoutController extends Controller
{
    public function show(Request $request, Course $course)
    {
        $settings = Cache::rememberForever('global_settings', function () {
            return Setting::pluck('value', 'key')->toArray();
        });
        $enableStripe = (bool) ($settings['enable_stripe'] ?? 1);
        $enablePaypal = (bool) ($settings['enable_paypal'] ?? 1);
        $enableRazorpay = (bool) ($settings['enable_razorpay'] ?? 1);

        $paypalClientId = config('services.paypal.client_id');
        $razorpayKey = config('services.razorpay.key');
        $timezones = TimezoneService::commonTimezones();

        $sessionCurrency = strtoupper($request->query('currency', session('display_currency', 'USD')));
        if (! in_array($sessionCurrency, ['USD', 'AED', 'INR'])) {
            $sessionCurrency = 'USD';
        }
        $paypalSupportedCurrencies = ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 'SGD', 'NZD', 'CHF', 'HKD'];
        $paypalCurrency = in_array($sessionCurrency, $paypalSupportedCurrencies) ? $sessionCurrency : 'USD';

        $numberOfClasses = max(1, (int) ($course->number_of_classes ?? 1));
        $classesCount = (int) $request->query('classes', 0);
        if ($classesCount > 48) {
            $classesCount = 48;
        }
        // Payment links always pass an explicit class count — charge per-class × classes.
        $isCustomClasses = $classesCount > 0;

        if ($sessionCurrency === 'AED') {
            $perClassPriceLocal = $course->price_aed ?? 30;
        } elseif ($sessionCurrency === 'INR') {
            $perClassPriceLocal = $course->price_inr ?? 600;
        } else {
            $perClassPriceLocal = $course->price_usd ?? $course->per_class_price ?? 18;
        }

        if ($isCustomClasses) {
            $originalPrice = $perClassPriceLocal * $classesCount;
            $fullCoursePrice = $originalPrice;
        } else {
            $pricingModel = request('pricing_model', 'full');
            $fullCoursePrice = $perClassPriceLocal * $numberOfClasses;

            if ($pricingModel === 'per_class' && $course->per_class_price > 0) {
                $originalPrice = $perClassPriceLocal;
            } else {
                $originalPrice = $fullCoursePrice;
            }
        }

        $discountAmount = 0;
        $couponCode = $request->query('coupon');
        $couponError = null;
        $priceAfterCoupon = $originalPrice;

        if ($couponCode) {
            $coupon = Coupon::where('code', strtoupper($couponCode))->first();
            if ($coupon && $coupon->isValid($originalPrice)) {
                $priceAfterCoupon = $coupon->applyDiscount($originalPrice);
                $discountAmount = $originalPrice - $priceAfterCoupon;
            } else {
                $couponError = 'Invalid or expired coupon code.';
            }
        }

        $student = Auth::guard('web')->user();
        $siblingPricing = app(SiblingPricingService::class)->forStudent($student, $priceAfterCoupon);
        $priceAfterSibling = (float) $siblingPricing['final_price'];
        $siblingDiscountAmount = (float) ($siblingPricing['discount_amount'] ?? 0);
        if ($siblingDiscountAmount > 0) {
            $discountAmount += $siblingDiscountAmount;
        }

        $bundlePricing = app(BundlePricingService::class)->forStudentCourse($student, $course, $priceAfterSibling);
        if ($bundlePricing['has_bundle_discount']) {
            $discountAmount += (float) $bundlePricing['discount_amount'];
        }

        try {
            if ($student) {
                app(ReEngagementService::class)->trackInterest($student->id, $course->id);
            }
        } catch (\Throwable $e) {
            Log::warning('trackInterest failed: '.$e->getMessage());
        }

        $bundles = CourseBundle::active()
            ->whereHas('courses', fn ($q) => $q->where('courses.id', $course->id))
            ->with(['courses:id,title,slug'])
            ->get()
            ->map(fn (CourseBundle $b) => [
                'id' => $b->id,
                'name' => $b->name,
                'description' => $b->description,
                'discount_percent' => $b->discount_percent,
                'courses' => $b->courses->map(fn ($c) => [
                    'id' => $c->id,
                    'title' => $c->title,
                    'slug' => $c->slug,
                ])->values()->all(),
            ])
            ->values()
            ->all();

        $deliveryModes = $course->availableDeliveryModes();
        $selectedDeliveryMode = (string) $request->query('delivery_mode', '');
        if (! in_array($selectedDeliveryMode, $deliveryModes, true)) {
            $selectedDeliveryMode = $deliveryModes[0] ?? 'live';
        }

        $daysOfWeek = [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            0 => 'Sunday',
        ];
        $defaultStartDate = now()->addDay()->toDateString();

        return \App\Support\InertiaPage::react('Student/Checkout', [
            'course' => StudentInertiaProps::checkoutCourse($course),
            'enableStripe' => $enableStripe,
            'enablePaypal' => $enablePaypal,
            'enableRazorpay' => $enableRazorpay,
            'paypalClientId' => $paypalClientId,
            'razorpayKey' => $razorpayKey,
            'paypalCurrency' => $paypalCurrency,
            'timezones' => $timezones,
            'couponCode' => $couponCode,
            'discountAmount' => $discountAmount,
            'couponError' => $couponError,
            'siblingDiscount' => $siblingPricing,
            'bundleDiscount' => $bundlePricing,
            'bundles' => $bundles,
            'originalPrice' => $originalPrice,
            'numberOfClasses' => $numberOfClasses,
            'daysOfWeek' => $daysOfWeek,
            'defaultStartDate' => $defaultStartDate,
            'classesCount' => $classesCount,
            'isCustomClasses' => $isCustomClasses,
            'sessionCurrency' => $sessionCurrency,
            'fullCoursePrice' => $fullCoursePrice,
            'perClassPriceLocal' => $perClassPriceLocal,
            'deliveryModes' => $deliveryModes,
            'selectedDeliveryMode' => $selectedDeliveryMode,
        ]);
    }

    public function createRazorpayOrder(Request $request, Course $course)
    {
        $settings = Cache::rememberForever('global_settings', function () {
            return Setting::pluck('value', 'key')->toArray();
        });
        $enableRazorpay = (bool) ($settings['enable_razorpay'] ?? 1);
        if (! $enableRazorpay) {
            return response()->json(['error' => 'Razorpay payment gateway is currently disabled.'], 400);
        }

        // Razorpay India merchant accounts settle in INR — always charge catalog INR price.
        $currency = \App\Support\PaymentGatewayCurrency::forRazorpay();
        $amount = $this->getFinalAmount($request, $course, $currency);
        if ($amount <= 0) {
            return response()->json(['error' => 'Invalid payment amount for Razorpay.'], 400);
        }

        $key = (string) config('services.razorpay.key');
        $secret = (string) config('services.razorpay.secret');
        if ($key === '' || $secret === '' || str_contains($key, '12345') || str_contains($secret, 'test_secret')) {
            return response()->json(['error' => 'Razorpay keys are not configured. Set them in Admin → Gateway.'], 400);
        }

        $user = $this->resolveUser($request);

        try {
            $api = new RazorpayApi($key, $secret);
            $order = $api->order->create([
                'receipt' => 'course_'.$course->id.'_'.time(),
                'amount' => (int) round($amount * 100),
                'currency' => $currency,
            ]);

            $payment = Payment::create([
                'student_id' => $user->id,
                'course_id' => $course->id,
                'amount' => $amount,
                'currency' => $currency,
                'status' => 'pending',
                'gateway' => 'razorpay',
                'transaction_id' => $order->id,
                'pricing_model' => $request->input('pricing_model'),
                'delivery_mode' => $this->resolveDeliveryMode($request, $course),
                'schedule' => $this->buildSchedule($request, $course),
            ]);

            if ($request->filled('pricing_model')) {
                session()->put("payment_{$payment->id}_pricing_model", $request->pricing_model);
            }
            session()->put("payment_{$payment->id}_delivery_mode", $payment->delivery_mode);

            return response()->json([
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'amount' => (int) round($amount * 100),
                'amount_major' => $amount,
                'currency' => $currency,
                'key' => $key,
            ]);
        } catch (\Exception $e) {
            Log::error('Razorpay order create failed: '.$e->getMessage());

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function createPaypalOrder(Request $request, Course $course)
    {
        $settings = Cache::rememberForever('global_settings', function () {
            return Setting::pluck('value', 'key')->toArray();
        });
        $enablePaypal = (bool) ($settings['enable_paypal'] ?? 1);
        if (! $enablePaypal) {
            return response()->json(['error' => 'PayPal payment gateway is currently disabled.'], 400);
        }

        $paypalCurrency = \App\Support\PaymentGatewayCurrency::forPaypal(
            $request->input('currency', session('display_currency', 'USD'))
        );

        $user = $this->resolveUser($request);
        $amount = $this->getFinalAmount($request, $course, $paypalCurrency);
        $formattedAmount = number_format((float) $amount, 2, '.', '');

        $payment = Payment::create([
            'student_id' => $user->id,
            'course_id' => $course->id,
            'amount' => $amount,
            'currency' => $paypalCurrency,
            'status' => 'pending',
            'gateway' => 'paypal',
            'transaction_id' => 'init_'.uniqid(),
            'pricing_model' => $request->input('pricing_model'),
            'delivery_mode' => $this->resolveDeliveryMode($request, $course),
            'schedule' => $this->buildSchedule($request, $course),
        ]);

        if ($request->filled('pricing_model')) {
            session()->put("payment_{$payment->id}_pricing_model", $request->pricing_model);
        }
        session()->put("payment_{$payment->id}_delivery_mode", $payment->delivery_mode);

        try {
            $provider = new PayPal;
            $provider->setApiCredentials(config('paypal'));
            $provider->getAccessToken();

            $description = preg_replace('/[^a-zA-Z0-9_ \-.,]/', '', $course->title);
            $description = empty(trim((string) $description)) ? 'Course Enrollment' : substr($description, 0, 120);

            $order = $provider->createOrder([
                'intent' => 'CAPTURE',
                'application_context' => [
                    'brand_name' => 'Younimatiq',
                    'user_action' => 'PAY_NOW',
                    'return_url' => route('student.checkout.success', ['payment_id' => $payment->id]),
                    'cancel_url' => route('student.checkout.cancel', ['payment_id' => $payment->id]),
                ],
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => $paypalCurrency,
                            'value' => $formattedAmount,
                        ],
                        'description' => $description,
                        'custom_id' => (string) $payment->id,
                    ],
                ],
            ]);

            if (empty($order['id'])) {
                $payment->update(['status' => 'failed']);
                $errorMsg = $order['message'] ?? ($order['error_description'] ?? 'Failed to create PayPal order');
                if (isset($order['details']) && is_array($order['details'])) {
                    $details = array_map(function ($d) {
                        return ($d['field'] ?? '').': '.($d['issue'] ?? '').' - '.($d['description'] ?? '');
                    }, $order['details']);
                    $errorMsg .= ' Details: '.implode(' | ', $details);
                }
                Log::error('PayPal Error: '.json_encode($order));

                return response()->json(['error' => $errorMsg], 500);
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

                return response()->json(['error' => 'PayPal approval URL missing.'], 500);
            }

            return response()->json([
                'payment_id' => $payment->id,
                'paypal_order_id' => $paypalOrderId,
                'approval_url' => $approvalUrl,
                'checkout_url' => $approvalUrl,
                'amount' => $formattedAmount,
                'currency' => $paypalCurrency,
            ]);
        } catch (\Exception $e) {
            $payment->update(['status' => 'failed']);
            Log::error('PayPal Order creation error: '.$e->getMessage());

            return response()->json(['error' => 'PayPal error: '.$e->getMessage()], 500);
        }
    }

    public function process(Request $request, Course $course)
    {
        // Stripe hosted Checkout only. PayPal/Razorpay use their dedicated JSON endpoints + client SDKs.
        $request->validate([
            'payment_method' => 'required|in:stripe',
            'coupon_code' => 'nullable|string',
            'timezone' => 'nullable|string',
            'pricing_model' => 'nullable|in:full,per_class',
            'delivery_mode' => 'nullable|in:live,self-paced',
            'classes' => 'nullable|integer',
            'schedule_start_date' => 'nullable|date',
            'schedule_days' => 'nullable|array',
            'schedule_days.*' => 'integer|between:0,6',
            'schedule_times' => 'nullable|array',
            'currency' => 'nullable|string',
        ]);

        $settings = Cache::rememberForever('global_settings', function () {
            return Setting::pluck('value', 'key')->toArray();
        });
        if (! (bool) ($settings['enable_stripe'] ?? 1)) {
            return back()->with('error', 'Stripe payment gateway is currently disabled.');
        }
        if (! config('services.stripe.secret')) {
            return back()->with('error', 'Stripe is not configured. Add keys in Admin → Gateway.');
        }

        $user = $this->resolveUser($request);

        $currency = \App\Support\PaymentGatewayCurrency::forStripe(
            $request->input('currency', session('display_currency', 'USD'))
        );
        $amount = $this->getFinalAmount($request, $course, $currency);
        if ($amount <= 0) {
            return back()->with('error', 'Invalid payment amount for Stripe.');
        }

        $payment = Payment::create([
            'student_id' => $user->id,
            'course_id' => $course->id,
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'pending',
            'gateway' => 'stripe',
            'transaction_id' => 'init_'.uniqid(),
            'pricing_model' => $request->input('pricing_model'),
            'delivery_mode' => $this->resolveDeliveryMode($request, $course),
            'schedule' => $this->buildSchedule($request, $course),
        ]);

        if ($request->filled('pricing_model')) {
            session()->put("payment_{$payment->id}_pricing_model", $request->pricing_model);
        }
        session()->put("payment_{$payment->id}_delivery_mode", $payment->delivery_mode);

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
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
                'metadata' => [
                    'payment_id' => (string) $payment->id,
                    'display_currency' => $currency,
                ],
                'success_url' => route('student.checkout.success', ['payment_id' => $payment->id]).'&session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('student.checkout.cancel', ['payment_id' => $payment->id]),
            ]);

            // External Stripe URL must use Inertia::location from Inertia form posts.
            return Inertia::location($session->url);
        } catch (\Exception $e) {
            $payment->update(['status' => 'failed']);

            return back()->with('error', 'Stripe initialization failed: '.$e->getMessage());
        }
    }

    private function resolveDeliveryMode(Request $request, Course $course): string
    {
        $modes = $course->availableDeliveryModes();
        $mode = (string) $request->input('delivery_mode', '');

        return in_array($mode, $modes, true) ? $mode : ($modes[0] ?? 'live');
    }

    /**
     * Build the payment schedule payload from checkout form input.
     * Always records the opted number of classes; includes days/times when the
     * student picked a recurring schedule.
     */
    private function buildSchedule(Request $request, Course $course): ?array
    {
        $numberOfClasses = max(1, (int) ($course->number_of_classes ?? 1));
        $classes = (int) $request->input('classes', 0);
        $pricingModel = (string) $request->input('pricing_model', 'full');

        if ($classes <= 0) {
            $classes = $pricingModel === 'per_class' ? 1 : $numberOfClasses;
        }
        $classes = min(max($classes, 1), 48);

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

    private function getFinalAmount(Request $request, Course $course, string $currency): float
    {
        $classesCount = (int) $request->input('classes', 0);
        $numberOfClasses = max(1, (int) ($course->number_of_classes ?? 1));
        if ($classesCount > 48) {
            $classesCount = 48;
        }
        $isCustomClasses = $classesCount > 0;

        if ($currency === 'AED') {
            $perClassPrice = $course->price_aed ?? 30;
        } elseif ($currency === 'INR') {
            $perClassPrice = $course->price_inr ?? 600;
        } else {
            $perClassPrice = $course->price_usd ?? $course->per_class_price ?? 18;
        }

        if ($isCustomClasses) {
            $amount = $perClassPrice * $classesCount;
        } elseif ($request->pricing_model === 'per_class' && $course->per_class_price > 0) {
            $amount = $perClassPrice;
        } else {
            $amount = $perClassPrice * $numberOfClasses;
        }

        if ($request->filled('coupon_code')) {
            $coupon = Coupon::where('code', strtoupper($request->coupon_code))->first();
            if ($coupon && $coupon->isValid($amount)) {
                $amount = $coupon->applyDiscount($amount);
                $coupon->increment('used_count');
            }
        }

        $student = Auth::guard('web')->user();
        if ($student) {
            $amount = (float) app(SiblingPricingService::class)->forStudent($student, $amount)['final_price'];
            $amount = (float) app(BundlePricingService::class)->forStudentCourse($student, $course, $amount)['final_price'];
        }

        return max(0, $amount);
    }

    private function resolveUser(Request $request)
    {
        if (Auth::guard('web')->check()) {
            return Auth::guard('web')->user();
        }

        $request->validate([
            'name' => 'required_without:is_logged_in|string|max:255',
            'email' => 'required_without:is_logged_in|string|email|max:255',
            'password' => 'required_without:is_logged_in|string|min:8',
        ]);

        $existing = User::where('email', $request->email)->first();

        if ($existing) {
            // Accounts silently created by a free-trial booking can be claimed here
            if ($existing->is_auto_created) {
                $existing->update([
                    'name' => $request->name ?: $existing->name,
                    'password' => Hash::make($request->password),
                    'timezone' => $request->input('timezone', $existing->timezone),
                    'is_auto_created' => false,
                    'trial_ends_at' => $existing->trial_ends_at ?: now()->addDays(9),
                ]);

                Auth::guard('web')->login($existing);

                return $existing;
            }

            // Already-real account: only continue if the password is correct
            if (Hash::check($request->password, $existing->password)) {
                Auth::guard('web')->login($existing);

                return $existing;
            }

            throw ValidationException::withMessages([
                'email' => 'An account already exists for this email. Please log in first, then come back to complete your purchase.',
            ]);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'student',
            'email_verified_at' => now(),
        ]);

        Auth::guard('web')->login($user);

        try {
            app(EmailService::class)->sendTemplate('welcome_student', $user->email, [
                'student_name' => $user->name,
            ]);
        } catch (\Exception $e) {
        }

        return $user;
    }
}
