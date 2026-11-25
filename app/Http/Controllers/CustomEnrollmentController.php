<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Course;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use App\Services\ClassScheduleService;
use App\Services\CurrencyService;
use App\Services\EmailService;
use App\Services\TimezoneService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Razorpay\Api\Api as RazorpayApi;
use Srmklive\PayPal\Services\PayPal;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;

class CustomEnrollmentController extends Controller
{
    public function generateLink(Request $request, Course $course)
    {
        $request->validate([
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'classes' => 'nullable|integer|min:1',
        ]);

        $params = [
            'course' => $course->slug,
            'price' => $request->price,
            'currency' => strtoupper($request->currency),
        ];

        if ($request->has('classes') && $request->classes) {
            $params['classes'] = $request->classes;
        }

        $url = URL::temporarySignedRoute(
            'custom.enrollment.checkout',
            now()->addHours(48),
            $params
        );

        $query = parse_url($url, PHP_URL_QUERY) ?: '';
        $mobileUrl = 'younimatiq://custom-enroll/'.$course->slug.($query !== '' ? '?'.$query : '');

        return response()->json([
            'url' => $url,
            'mobile_url' => $mobileUrl,
        ]);
    }

    public function publicGenerateLink(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'classes' => 'required|integer|min:1|max:100',
        ]);

        $course = Course::findOrFail($request->course_id);
        $classes = (int) $request->classes;
        $currency = session('display_currency', 'USD');

        if ($currency === 'INR') {
            $pricePerClass = 600;
        } elseif ($currency === 'OMR') {
            $pricePerClass = 3;
        } elseif ($currency === 'AED') {
            $pricePerClass = 30;
        } else {
            $pricePerClass = 20;
        }

        $totalPrice = $pricePerClass * $classes;

        $url = URL::temporarySignedRoute(
            'custom.enrollment.checkout',
            now()->addHours(48),
            [
                'course' => $course->slug,
                'price' => $totalPrice,
                'currency' => $currency,
                'classes' => $classes,
            ]
        );

        return redirect($url);
    }

    public function show(Request $request, Course $course)
    {
        $customPrice = $request->query('price');
        $customCurrency = $request->query('currency');
        $customClasses = $request->query('classes', 1);

        $timezones = TimezoneService::commonTimezones();

        $enableStripe = $this->settingEnabled('enable_stripe');
        $enablePaypal = $this->settingEnabled('enable_paypal');
        $enableRazorpay = $this->settingEnabled('enable_razorpay');

        $paypalClientId = config('services.paypal.client_id');
        $razorpayKey = config('services.razorpay.key');
        $paypalSupportedCurrencies = ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 'SGD', 'NZD', 'CHF', 'HKD'];
        $paypalCurrency = in_array(strtoupper((string) $customCurrency), $paypalSupportedCurrencies, true)
            ? strtoupper((string) $customCurrency)
            : 'USD';

        $couponCode = $request->query('coupon');
        $discountAmount = 0;
        $couponError = null;

        $finalPrice = $customPrice;

        if ($couponCode) {
            $coupon = Coupon::where('code', strtoupper($couponCode))->first();
            if ($coupon && $coupon->isValid($customPrice)) {
                $finalPrice = $coupon->applyDiscount($customPrice);
                $discountAmount = $customPrice - $finalPrice;
            } else {
                $couponError = 'Invalid or expired coupon code.';
            }
        }

        $processUrl = URL::temporarySignedRoute('custom.enrollment.process', now()->addHours(48), [
            'course' => $course->slug,
            'price' => $customPrice,
            'currency' => $customCurrency,
            'classes' => $customClasses,
        ]);

        $razorpayUrl = URL::temporarySignedRoute('custom.enrollment.razorpay.create', now()->addHours(48), [
            'course' => $course->slug,
            'price' => $customPrice,
            'currency' => $customCurrency,
            'classes' => $customClasses,
        ]);

        $paypalUrl = URL::temporarySignedRoute('custom.enrollment.paypal.create', now()->addHours(48), [
            'course' => $course->slug,
            'price' => $customPrice,
            'currency' => $customCurrency,
            'classes' => $customClasses,
        ]);

        return \App\Support\InertiaPage::react('Public/CustomEnrollment/Checkout', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'thumbnail_url' => $course->thumbnail_url,
                'description' => trim(preg_replace('/\s+/', ' ', strip_tags((string) ($course->description ?? ''))) ?: '') ?: null,
            ],
            'customPrice' => $customPrice,
            'customCurrency' => $customCurrency,
            'customClasses' => $customClasses,
            'finalPrice' => $finalPrice,
            'enableStripe' => $enableStripe,
            'enablePaypal' => $enablePaypal,
            'enableRazorpay' => $enableRazorpay,
            'paypalClientId' => $paypalClientId,
            'paypalCurrency' => $paypalCurrency,
            'razorpayKey' => $razorpayKey,
            'couponCode' => $couponCode,
            'discountAmount' => $discountAmount,
            'couponError' => $couponError,
            'processUrl' => $processUrl,
            'razorpayUrl' => $razorpayUrl,
            'paypalUrl' => $paypalUrl,
            'timezones' => $timezones,
        ]);
    }

    private function resolveUser(Request $request)
    {
        $timezone = TimezoneService::sanitizeTimezone($request->input('timezone'));

        // Already signed in: only child + timezone are needed (do not validate guest name/email).
        if (Auth::guard('web')->check() || $request->filled('is_logged_in')) {
            $request->validate([
                'child_name' => 'required|string|max:255',
                'timezone' => 'nullable|string|max:64',
            ]);

            if (Auth::guard('web')->check()) {
                $user = Auth::guard('web')->user();
                if ($timezone && $user->timezone !== $timezone) {
                    $user->update(['timezone' => $timezone]);
                }

                return $user;
            }
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8',
            'child_name' => 'required|string|max:255',
            'timezone' => 'nullable|string|max:64',
        ]);

        $existing = User::where('email', $request->email)->first();

        if ($existing) {
            // Accounts silently created by a free-trial booking can be claimed here
            if ($existing->is_auto_created) {
                $existing->update([
                    'name' => $request->name ?: $existing->name,
                    'password' => Hash::make($request->password),
                    'timezone' => $timezone ?: $existing->timezone,
                    'is_auto_created' => false,
                    'trial_ends_at' => $existing->trial_ends_at ?: now()->addDays(9),
                ]);

                Auth::guard('web')->login($existing);

                return $existing;
            }

            // Already-real account: only continue if the password is correct
            if (Hash::check($request->password, $existing->password)) {
                Auth::guard('web')->login($existing);
                if ($timezone) {
                    $existing->update(['timezone' => $timezone]);
                }

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
            'timezone' => $timezone,
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

    public function process(Request $request, Course $course)
    {
        $request->validate([
            'payment_method' => 'required|in:stripe',
            'coupon_code' => 'nullable|string',
            'child_name' => 'required|string|max:255',
            'class_date' => 'nullable|date',
            'class_time' => 'nullable|string',
            'timezone' => 'required|string',
            'schedule_start_date' => 'nullable|date',
            'schedule_days' => 'nullable|array',
            'schedule_days.*' => 'integer|between:0,6',
            'schedule_times' => 'nullable|array',
        ]);

        // Price/currency/classes come from the signed URL query only (not the body).
        $offer = $this->signedOffer($request);

        if (! $this->settingEnabled('enable_stripe')) {
            return back()->with('error', 'Stripe payment gateway is currently disabled.');
        }
        if (! config('services.stripe.secret')) {
            return back()->with('error', 'Stripe is not configured. Add keys in Admin → Gateway.');
        }

        $user = $this->resolveUser($request);
        $schedule = $this->buildSchedule($request, $course);

        $amount = $offer['price'];
        if ($request->filled('coupon_code')) {
            $coupon = Coupon::where('code', strtoupper($request->coupon_code))->first();
            if ($coupon && $coupon->isValid($amount)) {
                $amount = $coupon->applyDiscount($amount);
                $coupon->increment('used_count');
            }
        }
        $amount = max(0, $amount);

        $stripeCurrency = \App\Support\PaymentGatewayCurrency::forStripe($offer['currency']);

        $payment = Payment::create([
            'student_id' => $user->id,
            'course_id' => $course->id,
            'amount' => $amount,
            'currency' => $stripeCurrency,
            'status' => 'pending',
            'gateway' => 'stripe',
            'transaction_id' => 'init_'.uniqid(),
            'pricing_model' => 'custom',
            'delivery_mode' => $schedule ? 'live' : null,
            'schedule' => $schedule,
        ]);

        session()->put("payment_{$payment->id}_custom_enrollment", [
            'class_date' => $request->class_date,
            'class_time' => $request->class_time,
            'timezone' => $request->timezone,
            'classes' => $offer['classes'],
            'child_name' => $request->input('child_name'),
            'parent_name' => $request->input('name') ?: Auth::guard('web')->user()?->name,
        ]);

        Stripe::setApiKey(config('services.stripe.secret'));
        try {
            $unitAmount = (int) max(
                \App\Support\PaymentGatewayCurrency::stripeMinMinor($stripeCurrency),
                round($amount * 100)
            );
            $session = StripeSession::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => strtolower($stripeCurrency),
                        'unit_amount' => $unitAmount,
                        'product_data' => ['name' => $course->title],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'metadata' => ['payment_id' => (string) $payment->id, 'display_currency' => $stripeCurrency],
                'success_url' => route('student.checkout.success', ['payment_id' => $payment->id]).'&session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('student.checkout.cancel', ['payment_id' => $payment->id]),
            ]);

            return Inertia::location($session->url);
        } catch (\Exception $e) {
            $payment->update(['status' => 'failed']);

            return back()->with('error', 'Stripe initialization failed: '.$e->getMessage());
        }
    }

    public function createRazorpayOrder(Request $request, Course $course)
    {
        try {
            return $this->createRazorpayOrderInner($request, $course);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?: 'Validation failed.',
                'error' => collect($e->errors())->flatten()->first() ?: 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    private function createRazorpayOrderInner(Request $request, Course $course)
    {
        if (! $this->settingEnabled('enable_razorpay')) {
            return response()->json(['error' => 'Razorpay payment gateway is currently disabled.'], 400);
        }
        if (! config('services.razorpay.key') || ! config('services.razorpay.secret')) {
            return response()->json(['error' => 'Razorpay is not configured.'], 422);
        }

        $offer = $this->signedOffer($request);
        $user = $this->resolveUser($request);
        $schedule = $this->buildSchedule($request, $course);

        $amount = (float) $offer['price'];
        $offerCurrency = strtoupper((string) ($offer['currency'] ?? 'USD'));

        if ($request->filled('coupon_code')) {
            $coupon = Coupon::where('code', strtoupper($request->coupon_code))->first();
            if ($coupon && $coupon->isValid($amount)) {
                $amount = $coupon->applyDiscount($amount);
            }
        }
        $amount = max(0.01, $amount);

        // Razorpay settles in INR — convert signed offer amount when needed.
        $currency = \App\Support\PaymentGatewayCurrency::forRazorpay();
        if ($offerCurrency !== $currency) {
            $amount = \App\Services\CurrencyService::convertBetween($amount, $offerCurrency, $currency);
        }
        $amount = max(1, $amount);

        try {
            $api = new RazorpayApi(config('services.razorpay.key'), config('services.razorpay.secret'));
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
                'pricing_model' => 'custom',
                'delivery_mode' => $schedule ? 'live' : null,
                'schedule' => $schedule,
            ]);

            session()->put("payment_{$payment->id}_custom_enrollment", [
                'class_date' => $request->class_date,
                'class_time' => $request->class_time,
                'timezone' => $request->timezone,
                'classes' => $offer['classes'],
                'child_name' => $request->input('child_name'),
                'parent_name' => $request->input('name') ?: $user->name,
            ]);

            return response()->json([
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'amount' => (int) round($amount * 100),
                'amount_major' => $amount,
                'currency' => $currency,
                'key' => config('services.razorpay.key'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function createPaypalOrder(Request $request, Course $course)
    {
        try {
            return $this->createPaypalOrderInner($request, $course);
        } catch (ValidationException $e) {
            // Always JSON for this fetch()-based endpoint (never 302 back to the form).
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?: 'Validation failed.',
                'error' => collect($e->errors())->flatten()->first() ?: 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    private function createPaypalOrderInner(Request $request, Course $course)
    {
        if (! $this->settingEnabled('enable_paypal')) {
            return response()->json(['error' => 'PayPal payment gateway is currently disabled.'], 400);
        }

        if (! config('services.paypal.client_id') || ! config('services.paypal.secret')) {
            return response()->json(['error' => 'PayPal is not configured. Add keys in Admin → Gateway.'], 400);
        }

        $offer = $this->signedOffer($request);
        $user = $this->resolveUser($request);
        $schedule = $this->buildSchedule($request, $course);

        $amount = $offer['price'];
        $originalCurrency = $offer['currency'];
        $currency = $originalCurrency;

        $paypalSupportedCurrencies = ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 'SGD', 'NZD', 'CHF', 'HKD'];
        if (! in_array($currency, $paypalSupportedCurrencies, true)) {
            $rates = CurrencyService::getRates();
            $rate = $rates[$originalCurrency] ?? 1.0;
            if ($rate > 0) {
                $amount = round($amount / $rate, 2);
            }
            $currency = 'USD';
        }

        if ($request->filled('coupon_code')) {
            $coupon = Coupon::where('code', strtoupper($request->coupon_code))->first();
            if ($coupon && $coupon->isValid($amount)) {
                $amount = $coupon->applyDiscount($amount);
            }
        }
        $formattedAmount = number_format(max(0.01, $amount), 2, '.', '');

        $payment = Payment::create([
            'student_id' => $user->id,
            'course_id' => $course->id,
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'pending',
            'gateway' => 'paypal',
            'transaction_id' => 'init_'.uniqid(),
            'pricing_model' => 'custom',
            'delivery_mode' => $schedule ? 'live' : null,
            'schedule' => $schedule,
        ]);

        session()->put("payment_{$payment->id}_custom_enrollment", [
            'class_date' => $request->class_date,
            'class_time' => $request->class_time,
            'timezone' => $request->timezone,
            'classes' => $offer['classes'],
            'child_name' => $request->input('child_name'),
            'parent_name' => $request->input('name') ?: $user->name,
        ]);

        try {
            $provider = new PayPal;
            $provider->setApiCredentials(config('paypal'));
            $provider->getAccessToken();

            $description = substr(preg_replace('/[^a-zA-Z0-9_ \-.,]/', '', $course->title), 0, 120);
            if (empty(trim($description))) {
                $description = 'Course Enrollment';
            }

            $order = $provider->createOrder([
                'intent' => 'CAPTURE',
                'application_context' => [
                    'brand_name' => 'Younimatiq',
                    'user_action' => 'PAY_NOW',
                    'return_url' => route('student.checkout.success', ['payment_id' => $payment->id]),
                    'cancel_url' => route('student.checkout.cancel', ['payment_id' => $payment->id]),
                ],
                'purchase_units' => [[
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => $formattedAmount,
                    ],
                    'description' => $description,
                    'custom_id' => (string) $payment->id,
                ]],
            ]);

            if (empty($order['id'])) {
                $payment->update(['status' => 'failed']);
                $errorMsg = $this->paypalOrderErrorMessage($order);
                Log::error('PayPal Error: '.json_encode($order));

                return response()->json(['error' => $errorMsg], 500);
            }

            $payment->update(['transaction_id' => $order['id']]);

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
                'paypal_order_id' => $order['id'],
                'approval_url' => $approvalUrl,
                'checkout_url' => $approvalUrl,
                'amount' => $formattedAmount,
                'currency' => $currency,
            ]);
        } catch (\Exception $e) {
            $payment->update(['status' => 'failed']);
            Log::error('Custom enrollment PayPal error: '.$e->getMessage());

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Offer terms baked into the signed custom-enroll URL (query string).
     * Never trust body price/currency — those can be tampered with while keeping a valid signature.
     *
     * @return array{price: float, currency: string, classes: int}
     */
    private function signedOffer(Request $request): array
    {
        $price = $request->query('price');
        $currency = $request->query('currency');
        $classes = $request->query('classes', 1);

        if ($price === null || $currency === null || $currency === '') {
            throw ValidationException::withMessages([
                'price' => 'This custom enrollment link is missing price or currency. Please request a new link.',
            ]);
        }

        return [
            'price' => max(0, (float) $price),
            'currency' => strtoupper(substr((string) $currency, 0, 3)),
            'classes' => max(1, min(48, (int) $classes)),
        ];
    }

    /**
     * Build a recurring schedule from the custom-enrollment form. Falls back to
     * a weekly repeat of the single legacy class_date/class_time when provided.
     */
    private function buildSchedule(Request $request, Course $course): ?array
    {
        $classes = (int) ($request->query('classes') ?? $request->input('classes') ?? 0);
        if ($classes <= 0) {
            $classes = max(1, (int) ($course->number_of_classes ?? 1));
        }
        $classes = min($classes, 48);

        $days = $request->input('schedule_days', []);
        $times = $request->input('schedule_times', []);
        if (is_array($times)) {
            $times = array_filter($times, fn ($t) => $t && $t !== '__custom__');
        }
        $startDate = $request->input('schedule_start_date');

        if (empty($days) && $request->filled('class_date')) {
            try {
                $dow = Carbon::parse($request->input('class_date'))->dayOfWeek;
                $days = [$dow];
                $times = [$dow => $request->input('class_time') ?: '17:00'];
                $startDate = $request->input('class_date');
            } catch (\Throwable $e) {
                // fall through to normal normalization
            }
        }

        // Custom-enroll times are parent preferences for ops to place — do not
        // enforce public booking windows here (that produced opaque PayPal failures).
        return app(ClassScheduleService::class)->normalize([
            'classes' => $classes,
            'start_date' => $startDate,
            'days' => $days,
            'times' => $times,
            'timezone' => $request->input('timezone'),
        ]);
    }

    private function settingEnabled(string $key, bool $default = true): bool
    {
        $value = Setting::where('key', $key)->value('value');
        if ($value === null) {
            return $default;
        }

        return in_array((string) $value, ['1', 'true', 'on', 'yes'], true);
    }

    /**
     * Srmklive wraps PayPal API errors as { error: { message, details: [...] } }.
     */
    private function paypalOrderErrorMessage(array $order): string
    {
        $payload = is_array($order['error'] ?? null) ? $order['error'] : $order;
        $details = is_array($payload['details'] ?? null) ? $payload['details'] : [];
        $issue = (string) ($details[0]['issue'] ?? '');
        $description = (string) ($details[0]['description'] ?? '');

        if ($issue === 'PAYEE_ACCOUNT_RESTRICTED') {
            return 'PayPal rejected this payment because the receiving business account is restricted. Log in at paypal.com → Account Settings → resolve any limitations, or use sandbox credentials while testing.';
        }

        if ($issue === 'UNSUPPORTED_CURRENCY' || str_contains(strtolower($description), 'currency')) {
            return 'PayPal does not support this currency for your account. Try USD, or enable the currency in PayPal → Money → Currencies.';
        }

        if ($description !== '') {
            return 'PayPal: '.$description.($issue !== '' ? " ($issue)" : '');
        }

        return (string) ($payload['message'] ?? $order['message'] ?? $order['error_description'] ?? 'Failed to create PayPal order.');
    }
}
