@extends('layouts.dashboard')

@section('title', 'Payment History')

@section('content')
<div class="p-5 md:p-8 space-y-8">
    <div>
        <h1 class="font-headline-lg text-headline-lg font-bold mb-2">Payment History 🧾</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant">Review your past transactions and receipts.</p>
    </div>

    @if($payments->isNotEmpty())
    <div class="bg-white rounded-2xl border border-surface-container-high shadow-sm overflow-hidden">
        <div class="p-6 border-b border-surface-container-high">
            <h2 class="font-headline-md font-bold">Payments</h2>
        </div>
        <div class="overflow-x-auto w-full"><table class="w-full text-left">
            <thead class="bg-surface-container-low text-on-surface-variant font-bold border-b border-surface-container-high">
                <tr>
                    <th class="p-4">Date</th>
                    <th class="p-4">Course</th>
                    <th class="p-4">Amount</th>
                    <th class="p-4">Gateway</th>
                    <th class="p-4">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-container-high">
                @foreach($payments as $payment)
                <tr class="hover:bg-surface-container-low transition-colors">
                    <td class="p-4 text-on-surface-variant"><x-local-time :date="$payment->created_at" format="date" /></td>
                    <td class="p-4 font-bold">{{ $payment->course->title ?? 'N/A' }}</td>
                    <td class="p-4 font-bold">${{ number_format($payment->amount, 2) }}</td>
                    <td class="p-4 text-sm capitalize">{{ $payment->gateway ?? 'N/A' }}</td>
                    <td class="p-4">
                        <span class="px-3 py-1 {{ $payment->status === 'completed' ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container text-on-surface-variant' }} rounded-full text-xs font-bold">{{ ucfirst($payment->status) }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table></div>
    </div>
    @endif

    <div class="bg-white rounded-2xl border border-surface-container-high shadow-sm overflow-hidden">
        <div class="p-6 border-b border-surface-container-high">
            <h2 class="font-headline-md font-bold">Orders</h2>
        </div>
        <div class="overflow-x-auto w-full"><table class="w-full text-left">
            <thead class="bg-surface-container-low text-on-surface-variant font-bold border-b border-surface-container-high">
                <tr>
                    <th class="p-4">Order ID</th>
                    <th class="p-4">Date</th>
                    <th class="p-4">Course</th>
                    <th class="p-4">Amount</th>
                    <th class="p-4">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-container-high">
                @forelse($orders as $order)
                <tr class="hover:bg-surface-container-low transition-colors">
                    <td class="p-4 font-mono text-sm">#{{ $order->order_number ?? $order->id }}</td>
                    <td class="p-4 text-on-surface-variant"><x-local-time :date="$order->created_at" format="date" /></td>
                    <td class="p-4 font-bold">{{ $order->course->title ?? 'N/A' }}</td>
                    <td class="p-4 font-bold">${{ number_format($order->amount, 2) }}</td>
                    <td class="p-4">
                        <span class="px-3 py-1 bg-secondary-container text-on-secondary-container rounded-full text-xs font-bold">{{ ucfirst($order->status) }}</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="p-5 md:p-8 text-center text-on-surface-variant">No payment history found.</td>
                </tr>
                @endforelse
            </tbody>
        </table></div>
    </div>
</div>
@endsection
