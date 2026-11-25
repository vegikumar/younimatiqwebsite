@extends('layouts.app')

@section('title', 'Family Referrals & Sibling Discounts')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden bg-white">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
            <div>
                <span class="px-3 py-1 bg-warning-container text-warning rounded-full text-xs font-bold uppercase tracking-wider mb-2 inline-block">
                    Family Rewards & Sibling Benefits
                </span>
                <h1 class="font-headline-xl text-3xl font-extrabold text-on-surface mb-1">Referrals & Sibling Discounts</h1>
                <p class="font-body-lg text-sm text-on-surface-variant">Share your family referral code for $25 tuition credits and enjoy an automatic 15% discount on sibling enrollments.</p>
            </div>
            <div class="px-6 py-4 bg-tertiary-fixed border border-tertiary-fixed-dim rounded-2xl text-center">
                <p class="text-xs font-bold text-tertiary uppercase tracking-wider">Total Reward Credits Earned</p>
                <h3 class="text-3xl font-extrabold text-on-tertiary-container">${{ number_format($totalEarned, 2) }}</h3>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-1 md:grid-cols-3 gap-5 md:p-8">
        
        <!-- Left: Share Referral Code Card -->
        <div class="clay-card p-6 bg-white space-y-4 h-fit border border-outline-variant/20">
            <h3 class="font-bold text-lg text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">share</span> Your Family Referral Code
            </h3>
            
            <div class="p-4 bg-surface-container-low border-2 border-dashed border-primary/40 rounded-2xl text-center space-y-2">
                <p class="text-xs text-on-surface-variant font-bold">Share with friends & get $25 each</p>
                <h2 class="text-3xl font-extrabold text-primary tracking-widest uppercase">{{ $referralCode }}</h2>
                <p class="text-[10px] text-on-surface-variant">Friends get $25 off their 1st enrollment!</p>
            </div>

            <button onclick="navigator.clipboard.writeText('{{ $referralCode }}'); alert('Referral code copied!');" class="btn-enroll w-full py-3 text-xs font-bold flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-sm">content_copy</span> Copy Referral Code
            </button>
        </div>

        <!-- Right: Referrals & Sibling Enrollment Tracker -->
        <div class="lg:col-span-2 space-y-8">
            
            <!-- Sibling Enrollment Discount Status -->
            <div class="clay-card p-6 bg-white space-y-4 border border-outline-variant/20">
                <div class="flex justify-between items-center border-b border-outline-variant/20 pb-4">
                    <h3 class="font-bold text-lg text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">family_restroom</span> Sibling Enrollment Discount (15% Off)
                    </h3>
                    <span class="px-3 py-1 bg-tertiary-fixed text-tertiary rounded-full text-xs font-bold">
                        Auto-Applied at Checkout
                    </span>
                </div>
                <p class="text-xs text-on-surface-variant leading-relaxed">
                    Enrolling a 2nd or 3rd child? An automatic 15% discount is applied to every sibling enrollment.
                </p>
            </div>

            <!-- Referred Families History -->
            <div class="clay-card p-6 bg-white space-y-4 border border-outline-variant/20">
                <h3 class="font-bold text-lg text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">card_giftcard</span> Referred Families Status
                </h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-outline-variant/20 text-xs">
                                <th class="py-3 px-4 font-bold">Referral Code</th>
                                <th class="py-3 px-4 font-bold">Reward Credit</th>
                                <th class="py-3 px-4 font-bold">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($referrals as $ref)
                                <tr class="border-b border-outline-variant/10 text-xs hover:bg-surface-container-low">
                                    <td class="py-3 px-4 font-bold">{{ $ref->referral_code }}</td>
                                    <td class="py-3 px-4 font-bold text-on-tertiary-container">+${{ number_format($ref->reward_amount, 2) }}</td>
                                    <td class="py-3 px-4">
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $ref->status === 'completed' ? 'bg-tertiary-fixed text-tertiary' : 'bg-warning-container text-warning' }}">
                                            {{ $ref->status }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-6 text-center text-xs text-on-surface-variant">No referrals recorded yet. Share your code above to earn $25 credits!</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
</div>
@endsection
