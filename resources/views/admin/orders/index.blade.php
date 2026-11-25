@extends('layouts.dashboard')

@section('title', 'Orders & Sales')

@php
    /**
     * Returns the proper currency symbol for a given currency code.
     */
    function currencySymbol(?string $currency): string {
        $symbols = [
            'USD' => '$',
            'INR' => '₹',
            'AED' => 'AED ',
            'EUR' => '€',
            'GBP' => '£',
            'AUD' => 'A$',
            'SGD' => 'S$',
            'OMR' => 'OMR ',
        ];
        $code = strtoupper($currency ?? 'USD');
        return $symbols[$code] ?? ($code . ' ');
    }

    /**
     * Format an amount with proper currency symbol and decimals.
     */
    function formatAmount(float|string|null $amount, ?string $currency): string {
        $symbol = currencySymbol($currency);
        return $symbol . number_format((float)($amount ?? 0), 2);
    }
@endphp

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-tertiary relative overflow-hidden">
        <h1 class="font-headline-xl text-headline-xl text-on-surface mb-2">Orders & Sales</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant">Payments, orders, and free consultation / trial bookings overview.</p>
        <a href="{{ route('admin.orders.export') }}" class="btn-primary btn-sm mt-4 inline-flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">download</span> Export CSV
        </a>
        <div class="absolute right-0 top-0 -mr-20 -mt-20 w-64 h-64 bg-tertiary-fixed rounded-full blur-3xl opacity-20 pointer-events-none"></div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <div class="clay-card p-6 text-center">
            <span class="material-symbols-outlined text-primary text-4xl mb-2">payments</span>
            {{-- Show revenue per currency; avoid mixing incompatible currencies --}}
            @if(!empty($revenueGrouped))
                @foreach($revenueGrouped as $currency => $total)
                    <p class="text-2xl font-bold text-primary leading-tight">{{ formatAmount($total, $currency) }}</p>
                @endforeach
            @else
                <p class="text-3xl font-bold text-primary">$0.00</p>
            @endif
            <p class="text-on-surface-variant text-sm mt-1">Total Revenue (Completed)</p>
        </div>
        <div class="clay-card p-6 text-center">
            <span class="material-symbols-outlined text-secondary text-4xl mb-2">receipt_long</span>
            <p class="text-3xl font-bold">{{ $orders->count() }}</p>
            <p class="text-on-surface-variant text-sm">Recent Orders</p>
        </div>
        <div class="clay-card p-6 text-center">
            <span class="material-symbols-outlined text-tertiary text-4xl mb-2">school</span>
            <p class="text-3xl font-bold">{{ $campBookings->count() }}</p>
            <p class="text-on-surface-variant text-sm">Consultation & Trial Bookings</p>
        </div>
    </div>

    <div class="space-y-8">
        <div class="clay-card p-5 md:p-8">
            <h2 class="font-headline-md font-bold mb-6 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">credit_card</span> Payments
            </h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b-2 border-outline-variant">
                            <th class="py-3 px-4 font-bold">Student</th>
                            <th class="py-3 px-4 font-bold">Course</th>
                            <th class="py-3 px-4 font-bold">Amount</th>
                            <th class="py-3 px-4 font-bold">Currency</th>
                            <th class="py-3 px-4 font-bold">Gateway</th>
                            <th class="py-3 px-4 font-bold">Status</th>
                            <th class="py-3 px-4 font-bold">Date</th>
                            <th class="py-3 px-4 font-bold">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            @php $currency = strtoupper($payment->currency ?? 'USD'); @endphp
                            <tr class="border-b border-outline-variant hover:bg-surface-container-low">
                                <td class="py-3 px-4">{{ $payment->student->name ?? 'N/A' }}</td>
                                <td class="py-3 px-4">{{ $payment->course->title ?? 'N/A' }}</td>
                                <td class="py-3 px-4 font-bold">{{ formatAmount($payment->amount, $currency) }}</td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded text-xs font-mono font-bold bg-surface-container text-on-surface-variant">
                                        {{ $currency }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-on-surface-variant">{{ ucfirst($payment->gateway ?? 'N/A') }}</td>
                                <td class="py-3 px-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold {{ $payment->status === 'completed' ? 'bg-secondary-container text-on-secondary-container' : ($payment->status === 'failed' ? 'bg-error-container text-on-error-container' : 'bg-surface-container text-on-surface-variant') }}">
                                        {{ ucfirst($payment->status) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-sm text-on-surface-variant"><x-local-time :date="$payment->created_at" format="date" /></td>
                                <td class="py-3 px-4">
                                    @if($payment->status === 'completed')
                                    <form method="POST" action="{{ route('admin.orders.refund', $payment) }}" onsubmit="return confirm('Refund this payment? Enrollment will be cancelled.');">
                                        @csrf
                                        <button type="submit" class="text-xs font-bold text-error hover:underline">Refund</button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="py-6 text-center text-on-surface-variant">No payments yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($payments->hasPages())
                <div class="mt-4">{{ $payments->links() }}</div>
            @endif
        </div>

        <div class="clay-card p-5 md:p-8">
            <h2 class="font-headline-md font-bold mb-6 flex items-center gap-2">
                <span class="material-symbols-outlined text-secondary">shopping_cart</span> Orders
            </h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b-2 border-outline-variant">
                            <th class="py-3 px-4 font-bold">Order #</th>
                            <th class="py-3 px-4 font-bold">User</th>
                            <th class="py-3 px-4 font-bold">Course</th>
                            <th class="py-3 px-4 font-bold">Amount</th>
                            <th class="py-3 px-4 font-bold">Currency</th>
                            <th class="py-3 px-4 font-bold">Method</th>
                            <th class="py-3 px-4 font-bold">Status</th>
                            <th class="py-3 px-4 font-bold">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            @php
                                // Derive currency from linked payment; fallback to USD
                                $orderCurrency = strtoupper($order->payment->currency ?? 'USD');
                            @endphp
                            <tr class="border-b border-outline-variant hover:bg-surface-container-low">
                                <td class="py-3 px-4 font-mono text-sm">#{{ $order->order_number ?? $order->id }}</td>
                                <td class="py-3 px-4">{{ $order->user->name ?? 'N/A' }}</td>
                                <td class="py-3 px-4">{{ $order->course->title ?? 'N/A' }}</td>
                                <td class="py-3 px-4 font-bold">{{ formatAmount($order->amount, $orderCurrency) }}</td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded text-xs font-mono font-bold bg-surface-container text-on-surface-variant">
                                        {{ $orderCurrency }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-on-surface-variant text-sm">{{ ucfirst($order->payment_method ?? 'N/A') }}</td>
                                <td class="py-3 px-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-primary-container text-on-primary-container">{{ ucfirst($order->status) }}</span>
                                </td>
                                <td class="py-3 px-4 text-sm text-on-surface-variant"><x-local-time :date="$order->created_at" format="date" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="py-6 text-center text-on-surface-variant">No orders yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="clay-card p-5 md:p-8">
            <h2 class="font-headline-md font-bold mb-6 flex items-center gap-2">
                <span class="material-symbols-outlined text-tertiary">school</span> Consultation & Trial Bookings
            </h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b-2 border-outline-variant">
                            <th class="py-3 px-4 font-bold">Consultation / Trial</th>
                            <th class="py-3 px-4 font-bold">Child</th>
                            <th class="py-3 px-4 font-bold">Parent</th>
                            <th class="py-3 px-4 font-bold">Email</th>
                            <th class="py-3 px-4 font-bold">Amount</th>
                            <th class="py-3 px-4 font-bold">Currency</th>
                            <th class="py-3 px-4 font-bold">Gateway</th>
                            <th class="py-3 px-4 font-bold">Status</th>
                            <th class="py-3 px-4 font-bold">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($campBookings as $booking)
                            @php $bookingCurrency = strtoupper($booking->currency ?? 'USD'); @endphp
                            <tr class="border-b border-outline-variant hover:bg-surface-container-low">
                                <td class="py-3 px-4 font-medium">{{ $booking->camp_name }}</td>
                                <td class="py-3 px-4">{{ $booking->child_name }}</td>
                                <td class="py-3 px-4">{{ $booking->parent_name }}</td>
                                <td class="py-3 px-4 text-sm">{{ $booking->email }}</td>
                                <td class="py-3 px-4 font-bold">
                                    @if((float)$booking->amount > 0)
                                        {{ formatAmount($booking->amount, $bookingCurrency) }}
                                    @else
                                        <span class="text-on-surface-variant text-sm">Free</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    @if((float)$booking->amount > 0)
                                        <span class="px-2 py-0.5 rounded text-xs font-mono font-bold bg-surface-container text-on-surface-variant">
                                            {{ $bookingCurrency }}
                                        </span>
                                    @else
                                        <span class="text-on-surface-variant text-xs">—</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    <span class="text-on-surface-variant text-sm font-bold bg-surface-container-low px-2 py-1 rounded-md">{{ ucfirst($booking->payment_method ?? 'Free Trial') }}</span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold {{ $booking->payment_status === 'paid' ? 'bg-secondary-container text-on-secondary-container' : 'bg-error-container text-on-error-container' }}">
                                        {{ ucfirst($booking->payment_status) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-sm text-on-surface-variant"><x-local-time :date="$booking->created_at" format="date" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="py-6 text-center text-on-surface-variant">No consultation or trial bookings yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
