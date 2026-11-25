@extends('layouts.dashboard')

@section('title', 'My Earnings')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <div class="clay-card p-5 md:p-8 bg-gradient-to-br from-tertiary/20 to-tertiary/5 border-l-8 border-tertiary">
            <h3 class="font-headline-sm text-on-surface-variant mb-2">Total Estimated Earnings</h3>
            <div class="font-headline-xl text-4xl font-bold text-tertiary">${{ number_format($totalEarnings, 2) }}</div>
        </div>
        
        <div class="clay-card p-5 md:p-8">
            <h3 class="font-headline-sm text-on-surface-variant mb-2">Total Sales</h3>
            <div class="font-headline-xl text-4xl font-bold text-on-surface">{{ $payments->count() }}</div>
        </div>
        
        <div class="clay-card p-5 md:p-8">
            <h3 class="font-headline-sm text-on-surface-variant mb-2">Revenue Share</h3>
            <div class="font-headline-xl text-4xl font-bold text-on-surface">80%</div>
        </div>
    </div>

    <div class="clay-card p-5 md:p-8">
        <h2 class="font-headline-md text-xl mb-6">Recent Transactions</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b-2 border-outline-variant">
                        <th class="py-4 px-4 font-bold text-on-surface">Date</th>
                        <th class="py-4 px-4 font-bold text-on-surface">Course</th>
                        <th class="py-4 px-4 font-bold text-on-surface">Student</th>
                        <th class="py-4 px-4 font-bold text-on-surface text-right">Amount</th>
                        <th class="py-4 px-4 font-bold text-on-surface text-right">Your Share</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr class="border-b border-outline-variant hover:bg-surface-container-low transition-colors">
                            <td class="py-4 px-4 text-on-surface-variant"><x-local-time :date="$payment->created_at" format="date" /></td>
                            <td class="py-4 px-4 text-on-surface-variant">{{ $payment->course->title ?? 'Unknown Course' }}</td>
                            <td class="py-4 px-4 text-on-surface-variant">{{ $payment->user->name ?? 'Unknown Student' }}</td>
                            <td class="py-4 px-4 text-right text-on-surface font-medium">${{ number_format($payment->amount, 2) }}</td>
                            <td class="py-4 px-4 text-right text-tertiary font-bold">+${{ number_format($payment->amount * 0.8, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-on-surface-variant">
                                <span class="material-symbols-outlined text-4xl mb-2">payments</span>
                                <p>No transactions found yet. Keep building great courses!</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
