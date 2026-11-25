@extends('layouts.app')

@section('content')
<div class="bg-surface-light min-h-screen py-20">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-10">
        
        <div class="mb-10 text-center">
            <h1 class="text-3xl md:text-4xl font-extrabold text-on-surface tracking-tight">Custom Enrollment</h1>
            <p class="text-on-surface-variant mt-2">Enroll in {{ $course->title }} with a special price</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 md:p-10">
            
            <div class="lg:col-span-7">
                <div class="bg-white rounded-3xl p-5 md:p-8 shadow-sm border border-outline-variant">
                    <h2 class="text-2xl font-bold text-on-surface mb-6 pb-4 border-b border-outline-variant">Registration Details</h2>
                    
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

                    <form id="checkout-form" action="{!! $processUrl !!}" method="POST" autocomplete="on" onsubmit="if (!validateRegistration()) { return false; } document.getElementById('stripe-submit-btn').disabled = true; document.getElementById('stripe-submit-btn').innerHTML = 'Processing...';">
                        @csrf
                        <input type="hidden" name="price" value="{{ $customPrice }}">
                        <input type="hidden" name="currency" value="{{ $customCurrency }}">

                        @guest('web')
                        <div class="mb-8 bg-surface-container-low p-6 rounded-2xl border border-outline-variant">
                            <h3 class="text-lg font-bold text-on-surface mb-4">Create Your Account</h3>
                            
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

                        <!-- Class Schedule -->
                        <div class="mb-8 bg-surface-container-low p-6 rounded-2xl border border-outline-variant">
                            <h3 class="text-lg font-bold text-on-surface mb-1">Class Schedule</h3>
                            <p class="text-sm text-on-surface-variant mb-4">
                                You are enrolling in <span class="font-bold text-on-surface">{{ $customClasses }}</span>
                                {{ (int) $customClasses === 1 ? 'class' : 'classes' }}. Pick the days and times you'd like to attend and we'll schedule them all automatically.
                            </p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-bold text-on-surface mb-2">Start Date <span class="text-error">*</span></label>
                                    <input type="date" name="schedule_start_date" id="schedule_start_date" required min="{{ date('Y-m-d') }}" value="{{ date('Y-m-d', strtotime('+1 day')) }}" class="w-full px-4 py-3 border border-outline-variant rounded-xl bg-white font-medium focus:border-primary-fixed0">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-on-surface mb-2">Your Timezone <span class="text-error">*</span></label>
                                    <select name="timezone" id="timezone" required class="w-full px-4 py-3 border border-outline-variant rounded-xl bg-white font-medium focus:border-primary-fixed0">
                                        @foreach($timezones as $key => $name)
                                            <option value="{{ $key }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <p class="text-xs text-on-surface-variant mt-3 mb-2">Select one or more days and a time for each.</p>
                            <div class="space-y-3">
                                @foreach([1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 0 => 'Sunday'] as $dayValue => $dayLabel)
                                    <div class="flex flex-col sm:flex-row sm:items-center gap-3 bg-white p-3 rounded-xl border border-outline-variant/70" data-day-time-row>
                                        <label class="flex items-center gap-3 w-40 cursor-pointer">
                                            <input type="checkbox" name="schedule_days[]" value="{{ $dayValue }}" class="day-checkbox w-5 h-5 text-primary bg-white border-outline rounded focus:ring-primary-fixed0" onchange="toggleScheduleTime(this, {{ $dayValue }})">
                                            <span class="font-medium text-on-surface">{{ $dayLabel }}</span>
                                        </label>
                                        <select name="schedule_times[{{ $dayValue }}]" id="schedule_time_{{ $dayValue }}" data-day-time-select data-day="{{ $dayValue }}" disabled class="w-full max-w-md px-3 py-2 bg-white border border-outline-variant rounded-lg text-sm disabled:opacity-50">
                                            <option value="">Select a recommended time</option>
                                        </select>
                                        <div data-custom-time-wrap style="display:none;">
                                            <input type="time" data-custom-time step="300" class="w-40 px-3 py-2 bg-white border border-outline-variant rounded-lg text-sm">
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Coupon Code Field -->
                        <div class="mb-8 bg-surface-container-low p-6 rounded-2xl border border-outline-variant">
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

                        <!-- Payment Method Section -->
                        <div class="mb-6">
                            <h3 class="text-lg font-bold text-on-surface mb-4">Select Payment Method</h3>
                            <div class="space-y-4">
                            @if($enableStripe)
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
                            <label class="relative flex items-center p-5 border rounded-2xl cursor-pointer transition-all hover:border-primary-fixed-dim bg-surface-container-low border-outline-variant payment-option has-[:checked]:border-primary has-[:checked]:bg-primary-fixed has-[:checked]:ring-1 has-[:checked]:ring-primary">
                                <input type="radio" name="payment_method" value="razorpay" class="w-5 h-5 text-primary border-outline focus:ring-primary" {{ (!$enableStripe && !$enablePaypal && $enableRazorpay) ? 'checked' : '' }} onchange="togglePaymentAction()">
                                <div class="ml-4 flex-1 flex items-center justify-between">
                                    <div>
                                        <h4 class="font-bold text-on-surface">Razorpay</h4>
                                        <p class="text-sm text-on-surface-variant">UPI, Cards, Wallets</p>
                                    </div>
                                    <div class="text-[#3395FF] font-bold">Razorpay</div>
                                </div>
                            </label>
                            @endif
                            </div>
                        </div>
                        
                        <div id="stripe-container" class="{{ $enableStripe ? '' : 'hidden' }}">
                            <button type="submit" id="stripe-submit-btn" class="btn-enroll btn-block btn-lg">
                                Complete Registration & Pay
                                <span class="material-symbols-outlined">arrow_forward</span>
                            </button>
                        </div>
                        
                        <div id="razorpay-container" class="{{ (!$enableStripe && $enableRazorpay) ? '' : 'hidden' }}">
                            <button type="button" onclick="initRazorpay()" class="btn-primary btn-block btn-lg">
                                Complete Registration & Pay
                            </button>
                        </div>

                        <div id="paypal-button-container" class="{{ (!$enableStripe && !$enableRazorpay && $enablePaypal) ? '' : 'hidden' }} w-full relative z-0"></div>
                        
                    </form>
                </div>
            </div>
            
            <div class="lg:col-span-5">
                <div class="bg-white rounded-3xl overflow-hidden shadow-sm border border-outline-variant sticky top-28">
                    <x-course-card :course="$course" :hideButtons="true" class="!shadow-none !border-0 !rounded-none" />

                    <div class="p-6 border-t border-outline-variant bg-surface-container-low/50 space-y-4">
                        @if($couponCode && $discountAmount > 0)
                            <div class="space-y-2.5 text-sm bg-white p-4 rounded-2xl border border-outline-variant/80 shadow-xs">
                                <div class="flex justify-between text-on-surface font-medium">
                                    <span>Special Price</span>
                                    <span class="line-through">{{ $customCurrency }} {{ number_format($customPrice, 2) }}</span>
                                </div>
                                <div class="flex justify-between text-on-tertiary-container font-bold">
                                    <span>Coupon Discount ({{ $couponCode }})</span>
                                    <span>-{{ $customCurrency }} {{ number_format($discountAmount, 2) }}</span>
                                </div>
                            </div>
                        @endif

                        <div class="pt-2 flex flex-col gap-3">
                            @if($customClasses > 1)
                            <div class="flex justify-between items-baseline text-sm border-b border-outline-variant pb-2 mb-2">
                                <span class="text-on-surface-variant font-medium">Number of Classes:</span>
                                <span class="text-on-surface font-bold">{{ $customClasses }}</span>
                            </div>
                            @endif
                            <div class="flex justify-between items-baseline">
                                <span class="text-on-surface font-medium text-base">Total due:</span>
                                <span class="text-3xl font-extrabold text-on-surface">
                                    {{ $customCurrency }} {{ number_format(max(0, $finalPrice), 2) }}
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

@if($enablePaypal && $paypalClientId)
    <script src="https://www.paypal.com/sdk/js?client-id={{ $paypalClientId }}&currency={{ $customCurrency ?? 'USD' }}"></script>
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

    document.addEventListener("DOMContentLoaded", function() {
        const tzInput = document.getElementById('timezone');
        if (tzInput && !tzInput.dataset.userModified) {
            const serverTz = '{{ session("timezone", "") }}';
            const browserTz = Intl.DateTimeFormat().resolvedOptions().timeZone;
            const detectedTz = serverTz || browserTz || 'UTC';
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
            tzInput.dispatchEvent(new Event('change'));
            tzInput.addEventListener('change', () => tzInput.dataset.userModified = '1');
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        togglePaymentAction();
        
        // Auto-select timezone
        try {
            const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
            const select = document.getElementById('timezone');
            if (tz && select) {
                for (let i = 0; i < select.options.length; i++) {
                    if (select.options[i].value === tz) {
                        select.selectedIndex = i;
                        break;
                    }
                }
            }
        } catch (e) {}
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

    function toggleScheduleTime(checkbox, day) {
        const input = document.getElementById('schedule_time_' + day);
        if (!input) return;
        input.disabled = !checkbox.checked;
        if (checkbox.checked) input.dispatchEvent(new Event('change'));
    }

    function validateRegistration() {
        const startDate = document.getElementById('schedule_start_date').value;
        const timezone = document.getElementById('timezone').value;
        const days = document.querySelectorAll('input[name="schedule_days[]"]:checked');

        if (!startDate || !timezone || days.length === 0) {
            alert('Please choose a start date, at least one class day, and your timezone.');
            return false;
        }

        @guest('web')
        const name = document.querySelector('input[name="name"]').value;
        const email = document.querySelector('input[name="email"]').value;
        const password = document.querySelector('input[name="password"]').value;
        if (!name || !email || !password) {
            alert('Please fill in all required registration fields.');
            return false;
        }
        @endguest
        return true;
    }

    function getCheckoutData() {
        const scheduleDays = Array.from(document.querySelectorAll('input[name="schedule_days[]"]:checked'))
            .map(cb => parseInt(cb.value, 10));
        const scheduleTimes = {};
        scheduleDays.forEach(day => {
            const input = document.getElementById('schedule_time_' + day);
            if (!input || !input.value) return;
            if (input.value === '__custom__') {
                const row = input.closest('[data-day-time-row]');
                const custom = row && row.querySelector('[data-custom-time]');
                if (custom && custom.value) scheduleTimes[day] = custom.value;
            } else {
                scheduleTimes[day] = input.value;
            }
        });

        const data = {
            coupon_code: document.getElementById('coupon_code_hidden').value,
            price: '{{ $customPrice }}',
            currency: '{{ $customCurrency }}',
            schedule_start_date: document.getElementById('schedule_start_date').value,
            schedule_days: scheduleDays,
            schedule_times: scheduleTimes,
            timezone: document.getElementById('timezone').value
        };

        @guest('web')
        data.name = document.querySelector('input[name="name"]').value;
        data.email = document.querySelector('input[name="email"]').value;
        data.password = document.querySelector('input[name="password"]').value;
        data.child_name = document.querySelector('input[name="child_name"]').value;
        @endguest
        return data;
    }

    function initRazorpay() {
        if (!validateRegistration()) return;

        fetch("{!! $razorpayUrl !!}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(getCheckoutData())
        })
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                alert("Error initializing Razorpay: " + data.error);
                return;
            }

            var options = {
                "key": "{{ $razorpayKey }}",
                "amount": Math.round(data.amount * 100),
                "currency": "{{ strtoupper($customCurrency) }}",
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

    if (typeof paypal !== 'undefined') {
        paypal.Buttons({
            onClick: function(data, actions) {
                if (!validateRegistration()) return actions.reject();
                return actions.resolve();
            },
            createOrder: function(data, actions) {
                return fetch("{!! $paypalUrl !!}", {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json', 
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(getCheckoutData())
                })
                .then(function(res) {
                    if (!res.ok) {
                        return res.json().then(function(errData) {
                            throw new Error(errData.error || 'Server error creating PayPal order');
                        });
                    }
                    return res.json();
                })
                .then(function(paymentData) {
                    if (!paymentData || !paymentData.paypal_order_id) {
                        throw new Error(paymentData.error || 'Invalid PayPal payment initialization response.');
                    }
                    window.currentPaypalPaymentId = paymentData.payment_id;
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
            }
        }).render('#paypal-button-container');
    }
</script>
<script src="{{ asset('js/booking-slots.js') }}"></script>
@endsection
