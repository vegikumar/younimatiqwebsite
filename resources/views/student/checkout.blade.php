@extends('layouts.app')

@section('content')
<div class="bg-surface-light min-h-screen py-20">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-10">
        
        <div class="mb-10 text-center">
            <h1 class="text-3xl md:text-4xl font-extrabold text-on-surface tracking-tight">Secure Checkout</h1>
            <p class="text-on-surface-variant mt-2"></p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 md:p-10">
            
            <!-- Left Column: Payment Methods -->
            <!-- Left Column: Schedule & Payment Card -->
            <div class="lg:col-span-7">
                <div class="bg-white rounded-3xl p-5 md:p-8 shadow-sm border border-outline-variant">
                    <h2 class="text-2xl font-bold text-on-surface mb-6 pb-4 border-b border-outline-variant">Schedule Your Class: {{ $course->title }}</h2>
                    
                    @if(session('error'))
                        <div class="mb-4 p-4 bg-error-container text-error rounded-xl">
                            {{ session('error') }}
                        </div>
                    @endif
                    
                    @if($errors->any())
                        <div class="mb-4 p-4 bg-error-container text-error rounded-xl">
                            <ul class="list-disc pl-5">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <form id="checkout-form" action="{{ route('student.checkout.process', $course->id) }}" method="POST" onsubmit="return validateScheduleSelection(event)">
                        @csrf
                        
                        @guest('web')
                        <div class="mb-8 bg-surface-container-low p-6 rounded-2xl border border-outline-variant">
                            <h3 class="text-lg font-bold text-on-surface mb-4">Create Your Account</h3>
                            <p class="text-sm text-on-surface-variant mb-4 -mt-2">Already booked a free trial with this email? Enter the same email here and we'll sign you in and continue your purchase.</p>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-bold text-on-surface mb-2">Parent Name <span class="text-error">*</span></label>
                                    <input type="text" name="name" value="{{ old('name') }}" required autocomplete="name" class="w-full px-4 py-3 border border-outline-variant rounded-xl bg-white font-medium" placeholder="Your full name">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-bold text-on-surface mb-2">Email Address <span class="text-error">*</span></label>
                                    <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email" inputmode="email" class="w-full px-4 py-3 border border-outline-variant rounded-xl bg-white font-medium" placeholder="you@example.com">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-bold text-on-surface mb-2">Password <span class="text-error">*</span></label>
                                    <input type="password" name="password" required autocomplete="new-password" class="w-full px-4 py-3 border border-outline-variant rounded-xl bg-white font-medium" placeholder="Min. 8 characters">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-bold text-on-surface mb-2">Child's Name (Optional)</label>
                                    <input type="text" name="child_name" value="{{ old('child_name') }}" class="w-full px-4 py-3 border border-outline-variant rounded-xl bg-white font-medium" placeholder="Child's full name">
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="mb-8 bg-surface-container-low p-6 rounded-2xl border border-outline-variant">
                            <h3 class="text-lg font-bold text-on-surface mb-2">Logged In As</h3>
                            <p class="font-medium text-on-surface">{{ Auth::guard('web')->user()->name }} ({{ Auth::guard('web')->user()->email }})</p>
                            <input type="hidden" name="is_logged_in" value="1">
                        </div>
                        @endguest
                        
                        @php($deliveryModes = $deliveryModes ?? [$course->course_type ?: 'live'])
                        @if(count($deliveryModes) > 1)
                        <!-- Delivery / Learning Mode -->
                        <div class="mb-8 bg-surface-container-low p-6 rounded-2xl border border-outline-variant">
                            <label class="block text-sm font-bold text-on-surface mb-2">Choose Your Learning Mode <span class="text-error">*</span></label>
                            <div class="flex flex-col gap-3">
                                @foreach($deliveryModes as $mode)
                                <label class="flex items-start gap-3 p-4 border rounded-xl cursor-pointer bg-white has-[:checked]:border-primary-fixed0 has-[:checked]:bg-primary-fixed">
                                    <input type="radio" name="delivery_mode" value="{{ $mode }}" {{ $selectedDeliveryMode === $mode ? 'checked' : '' }} onchange="setQueryParam('delivery_mode','{{ $mode }}')" class="mt-0.5 w-5 h-5 text-primary">
                                    <span>
                                        <span class="font-bold block">{{ $mode === 'self-paced' ? 'Self-Paced Learning' : 'Live Classes' }}</span>
                                        <span class="text-sm text-on-surface-variant">{{ $mode === 'self-paced' ? 'Learn anytime with recorded lessons and downloadable materials.' : 'Attend scheduled live sessions with your tutor.' }}</span>
                                    </span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @else
                            <input type="hidden" name="delivery_mode" value="{{ $deliveryModes[0] ?? 'live' }}">
                        @endif
                        
                        <!-- Schedule Details Box (Timezone Only) -->
                        <div class="mb-8 bg-surface-container-low p-6 rounded-2xl border border-outline-variant">
                            <div class="mb-4">
                                <label class="block text-sm font-bold text-on-surface mb-2">Timezone <span class="text-error">*</span></label>
                                <select name="timezone" id="user_timezone" required class="w-full px-4 py-3 border border-outline-variant rounded-xl bg-white font-medium">
                                    <option value="">Select your timezone</option>
                                    @foreach(\App\Services\TimezoneService::commonTimezones() as $tzKey => $tzLabel)
                                        <option value="{{ $tzKey }}" @selected(old('timezone', session('timezone')) === $tzKey)>{{ $tzLabel }} ({{ $tzKey }})</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <!-- Number of Classes -->
                            <div class="mt-4 pt-4 border-t border-outline-variant">
                                <label class="block text-sm font-bold text-on-surface mb-2">Number of Classes <span class="text-error">*</span></label>
                                <input type="number" name="classes" id="num_classes" min="1" max="48"
                                       value="{{ $isCustomClasses ? $classesCount : ($numberOfClasses ?? ($course->number_of_classes ?: 1)) }}"
                                       class="w-full md:w-1/2 px-4 py-3 border border-outline-variant rounded-xl bg-white font-medium outline-none focus:border-primary-fixed0"
                                       onchange="updateClasses(this.value)" required>
                                <p class="text-xs text-outline mt-1">The full course includes {{ $numberOfClasses ?? ($course->number_of_classes ?: 1) }} classes. Changing this selects a custom class package priced per class.</p>
                            </div>

                            <!-- Recurring Class Schedule -->
                            <div class="mt-4 pt-4 border-t border-outline-variant">
                                <label class="block text-sm font-bold text-on-surface mb-2">When would you like your classes to start?</label>
                                <input type="date" name="schedule_start_date" min="{{ date('Y-m-d') }}" value="{{ $defaultStartDate ?? date('Y-m-d') }}"
                                       class="w-full md:w-1/2 px-4 py-3 border border-outline-variant rounded-xl bg-white font-medium outline-none focus:border-primary-fixed0">
                                <p class="text-xs text-outline mt-2 mb-3">Select the days and times you wish to attend. We'll schedule all your classes on these days. Leave blank to schedule later from your dashboard.</p>
                                <div class="space-y-3">
                                    @foreach(($daysOfWeek ?? [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 0 => 'Sunday']) as $dayValue => $dayLabel)
                                        <div class="flex items-center gap-4 bg-white p-3 rounded-xl border border-outline-variant/70">
                                            <label class="flex items-center gap-3 w-40 cursor-pointer">
                                                <input type="checkbox" name="schedule_days[]" value="{{ $dayValue }}" class="day-checkbox w-5 h-5 text-primary bg-white border-outline rounded focus:ring-primary-fixed0" onchange="toggleScheduleTime(this, {{ $dayValue }})">
                                                <span class="font-medium text-on-surface">{{ $dayLabel }}</span>
                                            </label>
                                            <input type="time" name="schedule_times[{{ $dayValue }}]" id="schedule_time_{{ $dayValue }}" disabled
                                                   class="w-40 px-3 py-2 bg-white border border-outline-variant rounded-lg text-sm disabled:opacity-50">
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            @if($isCustomClasses ?? false)
                                <input type="hidden" name="currency" value="{{ $sessionCurrency }}">
                                <div class="mt-4 pt-4 border-t border-outline-variant">
                                    <div class="p-4 border rounded-xl border-primary-fixed0 bg-primary-fixed">
                                        <span class="font-bold">Custom Course Package ({{ $classesCount }} classes)</span><br>
                                        <span class="text-sm text-on-surface">Total: {{ \App\Services\CurrencyService::formatLocal($originalPrice, $sessionCurrency) }}</span>
                                    </div>
                                </div>
                            @elseif($course->per_class_price > 0 || $course->price_usd > 0)
                            <div class="mt-4 pt-4 border-t border-outline-variant">
                                <label class="block text-sm font-bold text-on-surface mb-2">Pricing Option <span class="text-error">*</span></label>
                                <div class="flex flex-col gap-3">
                                    <label class="flex items-center gap-3 p-4 border rounded-xl cursor-pointer has-[:checked]:border-primary-fixed0 has-[:checked]:bg-primary-fixed">
                                        <input type="radio" name="pricing_model" value="full" {{ request('pricing_model', 'full') === 'full' ? 'checked' : '' }} onchange="setQueryParam('pricing_model','full')" class="w-5 h-5 text-primary">
                                        <span class="font-bold">Full Course ({{ \App\Services\CurrencyService::formatLocal($fullCoursePrice, $sessionCurrency) }})</span>
                                    </label>
                                    <label class="flex items-center gap-3 p-4 border rounded-xl cursor-pointer has-[:checked]:border-primary-fixed0 has-[:checked]:bg-primary-fixed">
                                        <input type="radio" name="pricing_model" value="per_class" {{ request('pricing_model') === 'per_class' ? 'checked' : '' }} onchange="setQueryParam('pricing_model','per_class')" class="w-5 h-5 text-primary">
                                        <span class="font-bold">Pay per Class ({{ \App\Services\CurrencyService::formatLocal($perClassPriceLocal, $sessionCurrency) }} / class)</span>
                                    </label>
                                </div>
                            </div>
                            @else
                                <input type="hidden" name="pricing_model" value="full">
                            @endif

                            <!-- Coupon Code Field -->
                            <div class="mt-5 pt-5 border-t border-outline-variant">
                                <label class="block text-sm font-bold text-on-surface mb-2">Coupon Code</label>
                                <div class="flex items-center gap-2">
                                    <input type="text" id="coupon_input" value="{{ $couponCode ?? old('coupon_code') }}" class="flex-1 px-4 py-3 border border-outline-variant rounded-xl uppercase font-bold tracking-wider bg-white outline-none focus:border-primary-fixed0" placeholder="Enter coupon code">
                                    <button type="button" onclick="applyCoupon()" class="px-6 py-3 bg-on-surface-variant text-white rounded-xl text-sm font-bold hover:bg-on-surface-variant transition-colors shadow-sm whitespace-nowrap">Apply</button>
                                </div>
                                <input type="hidden" name="coupon_code" id="coupon_code_hidden" value="{{ $couponCode ?? old('coupon_code') }}">
                                @if($couponError)
                                    <p class="text-error text-xs mt-1.5 font-medium">{{ $couponError }}</p>
                                @elseif($discountAmount > 0)
                                    <p class="text-on-tertiary-container text-xs mt-1.5 font-medium">✓ Coupon applied successfully!</p>
                                @endif
                            </div>
                        </div>

                        <!-- Payment Method Section -->
                        <div class="mb-6">
                            <h3 class="text-lg font-bold text-on-surface mb-4">Select Payment Method</h3>
                            <div class="space-y-4">
                            @if($enableStripe)
                            <!-- Stripe Option -->
                            <label class="relative flex items-center p-5 border rounded-2xl cursor-pointer transition-all hover:border-primary-fixed-dim bg-surface-container-low border-outline-variant payment-option has-[:checked]:border-primary has-[:checked]:bg-primary-fixed has-[:checked]:ring-1 has-[:checked]:ring-primary">
                                <input type="radio" name="payment_method" value="stripe" class="w-5 h-5 text-primary border-outline focus:ring-primary" {{ $enableStripe ? 'checked' : '' }} onchange="togglePaymentAction()">
                                <div class="ml-4 flex-1 flex items-center justify-between">
                                    <div>
                                        <h4 class="font-bold text-on-surface">Stripe</h4>
                                        <p class="text-sm text-on-surface-variant">Processed securely via Stripe</p>
                                    </div>
                                    <div class="flex space-x-2 text-outline">
                                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg>
                                    </div>
                                </div>
                            </label>
                            @endif
                            
                            @if($enablePaypal && $paypalClientId)
                            <!-- PayPal Option -->
                            <label class="relative flex items-center p-5 border rounded-2xl cursor-pointer transition-all hover:border-primary-fixed-dim bg-surface-container-low border-outline-variant payment-option has-[:checked]:border-primary has-[:checked]:bg-primary-fixed has-[:checked]:ring-1 has-[:checked]:ring-primary">
                                <input type="radio" name="payment_method" value="paypal" class="w-5 h-5 text-primary border-outline focus:ring-primary" {{ (!$enableStripe && $enablePaypal) ? 'checked' : '' }} onchange="togglePaymentAction()">
                                <div class="ml-4 flex-1 flex items-center justify-between">
                                    <div>
                                        <h4 class="font-bold text-on-surface">PayPal</h4>
                                        <p class="text-sm text-on-surface-variant">Fast and secure</p>
                                    </div>
                                    <div class="text-[#00457C] font-bold italic">PayPal</div>
                                </div>
                            </label>
                            @endif

                            @if($enableRazorpay && $razorpayKey)
                            <!-- Razorpay Option -->
                            <label class="relative flex items-center p-5 border rounded-2xl cursor-pointer transition-all hover:border-primary-fixed-dim bg-surface-container-low border-outline-variant payment-option has-[:checked]:border-primary has-[:checked]:bg-primary-fixed has-[:checked]:ring-1 has-[:checked]:ring-primary">
                                <input type="radio" name="payment_method" value="razorpay" class="w-5 h-5 text-primary border-outline focus:ring-primary" {{ (!$enableStripe && !$enablePaypal && $enableRazorpay) ? 'checked' : '' }} onchange="togglePaymentAction()">
                                <div class="ml-4 flex-1 flex items-center justify-between">
                                    <div>
                                        <h4 class="font-bold text-on-surface">Razorpay</h4>
                                        <p class="text-sm text-on-surface-variant">UPI, Cards, Wallets (India)</p>
                                    </div>
                                    <div class="text-[#3395FF] font-bold">Razorpay</div>
                                </div>
                            </label>
                            @endif

                            @if(!$enableStripe && !$enablePaypal && !$enableRazorpay)
                            <div class="p-4 bg-warning-container border border-warning-container text-warning rounded-xl text-sm font-medium">
                                No payment gateways are currently enabled. Please contact platform administration.
                            </div>
                            @endif
                            </div>
                        </div>
                        
                        <!-- Standard Submit Button (Stripe) -->
                        <div id="stripe-container" class="{{ $enableStripe ? '' : 'hidden' }}">
                            <button type="submit" class="btn-enroll btn-block btn-lg">
                                Pay with Stripe
                                <span class="material-symbols-outlined">arrow_forward</span>
                            </button>
                        </div>
                        
                        <!-- Razorpay Custom Trigger -->
                        <div id="razorpay-container" class="{{ (!$enableStripe && $enableRazorpay) ? '' : 'hidden' }}">
                            <button type="button" onclick="initRazorpay()" class="btn-primary btn-block btn-lg">
                                Pay with Razorpay
                            </button>
                        </div>

                        <!-- PayPal Button Container -->
                        <div id="paypal-button-container" class="{{ (!$enableStripe && !$enableRazorpay && $enablePaypal) ? '' : 'hidden' }} w-full relative z-0"></div>
                        
                    </form>
                </div>
            </div>
            
            <!-- Right Column: Course Summary Card -->
            <div class="lg:col-span-5">
                <div class="bg-white rounded-3xl overflow-hidden shadow-sm border border-outline-variant sticky top-28">
                    <!-- Course Card Component -->
                    <x-course-card :course="$course" :hideButtons="true" class="!shadow-none !border-0 !rounded-none" />

                    <!-- Pricing & Total Summary -->
                    <div class="p-6 border-t border-outline-variant bg-surface-container-low/50 space-y-4">
                        @if($couponCode && $discountAmount > 0)
                            <div class="space-y-2.5 text-sm bg-white p-4 rounded-2xl border border-outline-variant/80 shadow-xs">
                                <div class="flex justify-between text-on-surface font-medium">
                                    <span>Original Price</span>
                                    <span class="line-through">
                                        {{ \App\Services\CurrencyService::formatLocal($originalPrice, $sessionCurrency) }}
                                    </span>
                                </div>
                                <div class="flex justify-between text-on-tertiary-container font-bold">
                                    <span>Coupon Discount ({{ $couponCode }})</span>
                                    <span>-{{ \App\Services\CurrencyService::formatLocal($discountAmount, $sessionCurrency) }}</span>
                                </div>
                            </div>
                        @endif

                        <!-- Total Due Price & SSL Badge -->
                        <div class="pt-2 flex flex-col gap-3">
                            <div class="flex justify-between items-baseline">
                                <span class="text-on-surface font-medium text-base">Total due:</span>
                                <span class="text-3xl font-extrabold text-on-surface">
                                    {{ \App\Services\CurrencyService::formatLocal(max(0, $originalPrice - $discountAmount), $sessionCurrency) }}
                                </span>
                            </div>

                            <div class="flex items-center gap-2 text-xs text-outline justify-center pt-2 border-t border-outline-variant/60">
                                <span class="material-symbols-outlined text-base text-on-tertiary-container">lock</span>
                                256-bit SSL Encrypted & Secure Checkout
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>

<!-- SDKs -->
@if($enablePaypal && $paypalClientId)
    <script src="https://www.paypal.com/sdk/js?client-id={{ $paypalClientId }}&currency={{ $paypalCurrency ?? 'USD' }}"></script>
@endif
@if($enableRazorpay && $razorpayKey)
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
@endif

<script>
    function togglePaymentAction() {
        const checkedInput = document.querySelector('input[name="payment_method"]:checked');
        if (!checkedInput) return;
        const method = checkedInput.value;
        const stripeBtn = document.getElementById('stripe-container');
        const rzpBtn = document.getElementById('razorpay-container');
        const paypalBtn = document.getElementById('paypal-button-container');
        
        if (stripeBtn) stripeBtn.classList.add('hidden');
        if (rzpBtn) rzpBtn.classList.add('hidden');
        if (paypalBtn) paypalBtn.classList.add('hidden');

        if (method === 'stripe' && stripeBtn) {
            stripeBtn.classList.remove('hidden');
        } else if (method === 'razorpay' && rzpBtn) {
            rzpBtn.classList.remove('hidden');
        } else if (method === 'paypal' && paypalBtn) {
            paypalBtn.classList.remove('hidden');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        togglePaymentAction();
        try {
            const tzInput = document.getElementById('user_timezone');
            if (tzInput && !tzInput.dataset.userModified) {
                const detectedTz = Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';
                let optionExists = false;
                for (let i = 0; i < tzInput.options.length; i++) {
                    if (tzInput.options[i].value === detectedTz) {
                        optionExists = true;
                        break;
                    }
                }
                if (!optionExists) {
                    tzInput.add(new Option(detectedTz, detectedTz, true, true));
                } else {
                    tzInput.value = detectedTz;
                }
            }
            if (tzInput) {
                tzInput.addEventListener('change', () => tzInput.dataset.userModified = '1');
            }
        } catch (e) {
            console.log('Timezone auto-detection error:', e);
        }
    });
    
    function applyCoupon() {
        const coupon = document.getElementById('coupon_input').value;
        const url = new URL(window.location.href);
        if (coupon) {
            url.searchParams.set('coupon', coupon);
        } else {
            url.searchParams.delete('coupon');
        }
        window.location.href = url.toString();
    }

    function setQueryParam(key, value) {
        const url = new URL(window.location.href);
        if (value) {
            url.searchParams.set(key, value);
        } else {
            url.searchParams.delete(key);
        }
        window.location.href = url.toString();
    }

    function validateScheduleSelection(event = null) {        const form = document.getElementById('checkout-form');
        if (form && !form.checkValidity()) {
            form.reportValidity();
            if (event) event.preventDefault();
            return false;
        }
        return true;
    }

    function updateClasses(value) {
        const def = {{ (int) ($numberOfClasses ?? ($course->number_of_classes ?: 1)) }};
        const url = new URL(window.location.href);
        if (value && parseInt(value, 10) !== def) {
            url.searchParams.set('classes', value);
        } else {
            url.searchParams.delete('classes');
        }
        window.location.href = url.toString();
    }

    function toggleScheduleTime(checkbox, dayValue) {
        const timeInput = document.getElementById('schedule_time_' + dayValue);
        if (!timeInput) return;
        if (checkbox.checked) {
            timeInput.disabled = false;
            timeInput.required = true;
            if (!timeInput.value) timeInput.value = '17:00';
        } else {
            timeInput.disabled = true;
            timeInput.required = false;
            timeInput.value = '';
        }
    }

    function getScheduleData() {
        const startInput = document.querySelector('input[name="schedule_start_date"]');
        const days = [];
        document.querySelectorAll('input[name="schedule_days[]"]:checked').forEach(function (cb) {
            days.push(parseInt(cb.value, 10));
        });
        const times = {};
        days.forEach(function (day) {
            const t = document.getElementById('schedule_time_' + day);
            if (t && t.value) times[day] = t.value;
        });

        return {
            schedule_start_date: startInput ? startInput.value : null,
            schedule_days: days,
            schedule_times: times,
        };
    }

    function getCouponCode() {
        const el = document.querySelector('input[name="coupon_code"]');
        return el ? el.value : '';
    }

    function getCheckoutData() {
        const tzInput = document.getElementById('user_timezone');
        const pmInput = document.querySelector('input[name="pricing_model"]:checked') || document.querySelector('input[name="pricing_model"]');
        const dmInput = document.querySelector('input[name="delivery_mode"]:checked') || document.querySelector('input[name="delivery_mode"]');
        const classesInput = document.querySelector('input[name="classes"]');
        const currencyInput = document.querySelector('input[name="currency"]');
        
        let data = {
            coupon_code: getCouponCode(),
            timezone: (tzInput && tzInput.value) ? tzInput.value : (Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC'),
            pricing_model: pmInput ? pmInput.value : 'full',
            delivery_mode: dmInput ? dmInput.value : null,
            classes: classesInput ? classesInput.value : 0,
            currency: currencyInput ? currencyInput.value : null
        };

        Object.assign(data, getScheduleData());
        
        const isLoggedIn = document.querySelector('input[name="is_logged_in"]');
        if (!isLoggedIn) {
            data.name = document.querySelector('input[name="name"]')?.value;
            data.email = document.querySelector('input[name="email"]')?.value;
            data.password = document.querySelector('input[name="password"]')?.value;
            data.child_name = document.querySelector('input[name="child_name"]')?.value;
        }
        
        return data;
    }

    // Initialize Razorpay logic
    function initRazorpay() {
        if (!validateScheduleSelection(null)) {
            return;
        }

        fetch("{{ route('student.checkout.razorpay.create', $course->id) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify(getCheckoutData())
        })
        .then(async res => {
            const data = await res.json();
            if (!res.ok) {
                if (data.errors) {
                    const firstError = Object.values(data.errors)[0][0];
                    throw new Error(firstError);
                }
                throw new Error(data.error || data.message || 'Server error initializing Razorpay');
            }
            return data;
        })
        .then(data => {
            var options = {
                "key": "{{ $razorpayKey }}",
                "amount": Math.round(data.amount * 100),
                "currency": data.currency || "{{ session('display_currency', 'USD') }}",
                "name": "Younimatiq",
                "description": "{{ $course->title }}",
                "order_id": data.order_id,
                "handler": function (response) {
                    const params = new URLSearchParams({
                        payment_id: data.payment_id,
                        razorpay_payment_id: response.razorpay_payment_id,
                        razorpay_order_id: response.razorpay_order_id,
                        razorpay_signature: response.razorpay_signature,
                    });
                    window.location.href = "{{ route('student.checkout.success') }}?" + params.toString();
                },
                "theme": { "color": "#046bd2" }
            };
            var rzp = new Razorpay(options);
            rzp.on('payment.failed', function (response) {
                alert("Payment failed: " + response.error.description);
                window.location.href = "{{ route('student.checkout.cancel') }}?payment_id=" + data.payment_id;
            });
            rzp.open();
        })
        .catch(err => {
            console.error(err);
            alert("Could not connect to Razorpay server.");
        });
    }

    // Initialize PayPal logic
    if (typeof paypal !== 'undefined') {
        paypal.Buttons({
            onClick: function(data, actions) {
                if (!validateScheduleSelection(null)) {
                    return actions.reject();
                }
                return actions.resolve();
            },
            createOrder: function(data, actions) {
                return fetch("{{ route('student.checkout.paypal.create', $course->id) }}", {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json', 
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(getCheckoutData())
                })
                .then(function(res) {
                    if (res.status === 419) {
                        alert("Your session has expired. Please refresh the page and try again.");
                        window.location.reload();
                        throw new Error("Session expired");
                    }
                    // Check if response is json
                    const contentType = res.headers.get("content-type");
                    if (!contentType || !contentType.includes("application/json")) {
                        throw new Error("Server returned an invalid response. Please try again.");
                    }
                    return res.json().then(function(data) {
                        if (!res.ok) {
                            if (data.errors) {
                                const firstError = Object.values(data.errors)[0][0];
                                throw new Error(firstError);
                            }
                            throw new Error(data.error || data.message || 'Server error creating PayPal order');
                        }
                        return data;
                    });
                })
                .then(function(paymentData) {
                    if (!paymentData || !paymentData.paypal_order_id) {
                        throw new Error(paymentData.error || 'Invalid PayPal payment initialization response.');
                    }
                    window.currentPaypalPaymentId = paymentData.payment_id;
                    window.currentPaypalAmount = paymentData.amount;
                    return paymentData.paypal_order_id;
                })
                .catch(function(err) {
                    console.error('PayPal order creation failed:', err);
                    alert("Could not initialize PayPal order: " + (err.message || 'Unknown error'));
                    throw err;
                });
            },
            onApprove: function(data, actions) {
                return actions.order.capture().then(function(details) {
                    if (details.status === 'COMPLETED' || details.status === 'APPROVED') {
                        const params = new URLSearchParams({
                            payment_id: window.currentPaypalPaymentId,
                            paypal_order_id: data.orderID,
                            transaction_id: details.id || data.orderID,
                        });
                        window.location.href = "{{ route('student.checkout.success') }}?" + params.toString();
                    } else {
                        alert('PayPal payment status: ' + details.status);
                    }
                }).catch(function(err) {
                    console.error('PayPal capture error:', err);
                    alert('PayPal capture error: ' + (err.message || 'Unknown error'));
                });
            },
            onError: function(err) {
                console.error('PayPal SDK Error:', err);
                alert('PayPal error: Ensure your PayPal Client ID and Sandbox/Live mode match your developer credentials in Admin Settings.');
            },
            onCancel: function (data) {
                console.log('PayPal payment cancelled by user:', data);
            }
        }).render('#paypal-button-container');
    }
</script>
@endsection
