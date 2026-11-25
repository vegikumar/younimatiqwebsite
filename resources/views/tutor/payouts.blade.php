@extends('layouts.dashboard')

@section('title', 'Itemized Payouts Report')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="font-headline-xl text-headline-xl text-on-surface mb-2">Itemized Payouts Report</h1>
                <p class="font-body-lg text-body-lg text-on-surface-variant">Transparent itemized payment tracking including base earnings, class completion bonuses, and upsell commissions.</p>
            </div>
            <a href="{{ route('tutor.analytics') }}" class="btn-outline-primary py-2 px-4 text-xs font-bold flex items-center gap-1">
                <span class="material-symbols-outlined text-sm">arrow_back</span> Back to Analytics
            </a>
        </div>
    </div>

    <div class="clay-card bg-white p-6 md:p-8 space-y-6">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-outline-variant/20 text-xs">
                        <th class="py-3 px-4 font-bold">Pay Period</th>
                        <th class="py-3 px-4 font-bold">Base Earnings</th>
                        <th class="py-3 px-4 font-bold">Upsell Commission</th>
                        <th class="py-3 px-4 font-bold">Bonuses</th>
                        <th class="py-3 px-4 font-bold">Net Payout</th>
                        <th class="py-3 px-4 font-bold">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payouts as $payout)
                        <tr class="border-b border-outline-variant/10 text-xs hover:bg-surface-container-low">
                            <td class="py-3 px-4 font-bold">{{ DateTime::createFromFormat('!m', $payout->period_month)->format('F') }} {{ $payout->period_year }}</td>
                            <td class="py-3 px-4">${{ number_format($payout->base_earnings, 2) }}</td>
                            <td class="py-3 px-4 text-on-tertiary-container font-bold">+${{ number_format($payout->upsell_commission, 2) }}</td>
                            <td class="py-3 px-4 text-on-tertiary-container font-bold">+${{ number_format($payout->bonus, 2) }}</td>
                            <td class="py-3 px-4 font-extrabold text-primary">${{ number_format($payout->net_payout, 2) }}</td>
                            <td class="py-3 px-4">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $payout->status === 'paid' ? 'bg-tertiary-fixed text-tertiary' : 'bg-warning-container text-warning' }}">
                                    {{ $payout->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-on-surface-variant text-xs">No itemized payouts processed yet. Earnings roll up automatically at the end of each billing cycle.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
