@extends('layouts.app')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-surface">
    <div class="max-w-md w-full space-y-8 bg-white p-5 md:p-10 rounded-[2rem] shadow-xl animate-in fade-in slide-in-from-bottom-4 duration-500 border border-outline-variant/20">
        <div class="text-center">
            <span class="material-symbols-outlined text-primary text-6xl mb-4">payments</span>
            <h2 class="text-3xl font-extrabold text-on-surface">Complete Booking</h2>
            <p class="mt-2 text-sm text-on-surface-variant">
                You're booking <strong>{{ $booking->camp_name }}</strong> for {{ $booking->child_name }}.
            </p>
        </div>

        
        @if(session('info'))
            <div class="p-4 bg-secondary-container text-on-secondary-container rounded-xl text-sm font-medium">{{ session('info') }}</div>
        @endif

        <div class="bg-surface-container p-6 rounded-2xl mb-6 flex justify-between items-center shadow-inner">
            <span class="font-bold text-on-surface-variant text-lg">Total Due:</span>
            @php $demoPrice = (float) (\App\Models\Setting::where('key', 'demo_class_price')->value('value') ?? 0); @endphp
            <span class="font-extrabold text-primary text-3xl">{{ $displayAmount ?? \App\Services\CurrencyService::format($demoPrice, $booking->currency) }}</span>
        </div>

        @php
            $defaultMethod = $enableStripe ? 'stripe' : ($enablePaypal ? 'paypal' : ($enableRazorpay ? 'razorpay' : ''));
        @endphp
        <div class="space-y-6" x-data="{ method: '{{ $defaultMethod }}' }">
            <div>
                <label class="block text-sm font-bold text-on-surface mb-2">Select Payment Method</label>
                <div class="space-y-3">
                    @if($enablePaypal)
                    <label class="flex items-center gap-4 p-4 border-2 border-outline-variant/30 rounded-xl cursor-pointer hover:border-primary/50 transition-colors" :class="method === 'paypal' && 'border-primary'">
                        <input type="radio" name="payment_method_ui" value="paypal" x-model="method" class="w-5 h-5 text-primary focus:ring-primary">
                        <div class="flex-1">
                            <span class="block font-bold text-on-surface">PayPal</span>
                            <span class="block text-xs text-on-surface-variant">PayPal Balance, Debit & Credit Cards</span>
                        </div>
                        <img src="https://upload.wikimedia.org/wikipedia/commons/b/b5/PayPal.svg" class="h-6 w-auto" alt="PayPal">
                    </label>
                    @endif

                    @if($enableStripe)
                    <label class="flex items-center gap-4 p-4 border-2 border-outline-variant/30 rounded-xl cursor-pointer hover:border-primary/50 transition-colors" :class="method === 'stripe' && 'border-primary'">
                        <input type="radio" name="payment_method_ui" value="stripe" x-model="method" class="w-5 h-5 text-primary focus:ring-primary">
                        <div class="flex-1">
                            <span class="block font-bold text-on-surface">Stripe</span>
                            <span class="block text-xs text-on-surface-variant">Credit/Debit Cards, Apple Pay & Google Pay</span>
                        </div>
                        <span class="material-symbols-outlined text-primary">credit_card</span>
                    </label>
                    @endif

                    @if($enableRazorpay)
                    <label class="flex items-center gap-4 p-4 border-2 border-outline-variant/30 rounded-xl cursor-pointer hover:border-primary/50 transition-colors" :class="method === 'razorpay' && 'border-primary'">
                        <input type="radio" name="payment_method_ui" value="razorpay" x-model="method" class="w-5 h-5 text-primary focus:ring-primary">
                        <div class="flex-1">
                            <span class="block font-bold text-on-surface">Razorpay</span>
                            <span class="block text-xs text-on-surface-variant">UPI, Netbanking, Wallets & Cards</span>
                        </div>
                        <span class="material-symbols-outlined text-primary">account_balance_wallet</span>
                    </label>
                    @endif
                </div>
            </div>

            @if($enableRazorpay)
            <div x-show="method === 'razorpay'">
                <button type="button" id="rzp-pay-btn" onclick="payWithRazorpay()" class="btn-enroll btn-block btn-lg w-full" {{ $razorpayKey ? '' : 'disabled' }}>
                    <span class="material-symbols-outlined">lock</span>
                    Pay {{ $displayAmount ?? \App\Services\CurrencyService::format($demoPrice) }} with Razorpay
                </button>
            </div>
            @endif

            <form x-show="method !== 'razorpay'" action="{{ route('booking.pay', $booking->id) }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="payment_method" :value="method">
                <button type="submit" class="btn-enroll btn-block btn-lg w-full">
                    <span class="material-symbols-outlined">lock</span>
                    Pay {{ $displayAmount ?? \App\Services\CurrencyService::format($demoPrice) }} securely
                </button>
            </form>
        </div>
    </div>
</div>

@if($razorpayKey)
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
async function payWithRazorpay() {
    const btn = document.getElementById('rzp-pay-btn');
    btn.disabled = true;
    btn.textContent = 'Creating order…';

    try {
        const res = await fetch(@json(route('booking.razorpay.create', $booking)), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': @json(csrf_token()),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
        });
        const data = await res.json();
        if (!res.ok) {
            alert(data.error || 'Unable to create Razorpay order');
            btn.disabled = false;
            btn.innerHTML = '<span class="material-symbols-outlined">lock</span> Pay with Razorpay';
            return;
        }

        const options = {
            key: data.key,
            amount: data.amount,
            currency: data.currency,
            name: data.name,
            description: data.description,
            order_id: data.order_id,
            prefill: data.prefill,
            handler: async function (response) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = @json(route('booking.razorpay.verify', $booking));
                const fields = {
                    _token: @json(csrf_token()),
                    razorpay_order_id: response.razorpay_order_id,
                    razorpay_payment_id: response.razorpay_payment_id,
                    razorpay_signature: response.razorpay_signature,
                };
                Object.entries(fields).forEach(([k, v]) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = k;
                    input.value = v;
                    form.appendChild(input);
                });
                document.body.appendChild(form);
                form.submit();
            },
            theme: { color: '#004085' },
        };
        const rzp = new Razorpay(options);
        rzp.on('payment.failed', function (resp) {
            alert(resp.error?.description || 'Payment failed');
            btn.disabled = false;
        });
        rzp.open();
        btn.disabled = false;
        btn.innerHTML = '<span class="material-symbols-outlined">lock</span> Pay with Razorpay';
    } catch (e) {
        alert('Payment error: ' + e.message);
        btn.disabled = false;
    }
}
</script>
@endif
@endsection
