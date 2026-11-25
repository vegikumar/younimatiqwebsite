@extends('layouts.dashboard')

@section('title', 'Platform Settings')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden">
        <h1 class="font-headline-xl text-headline-xl text-on-surface mb-2">Platform Settings</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant">Manage global settings like pricing, discounts, and platform rules.</p>
    </div>

    

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 md:p-8">
        <div class="clay-card p-5 md:p-8">
            <h2 class="font-headline-md font-bold mb-6 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">payments</span> Pricing & Payments
            </h2>
            <form method="POST" action="{{ route('admin.platform-settings.store') }}" class="space-y-6">
                @csrf
                <div>
                    <label class="block text-sm font-bold mb-2">Free Trial / Consultation Price ($)</label>
                    <p class="text-xs text-on-surface-variant mb-3">Set to 0 to make free consultations and trial classes completely free and bypass the payment gateway.</p>
                    <div class="relative">
                        <span class="absolute left-4 top-3 font-bold text-on-surface-variant">$</span>
                        <input type="number" step="0.01" min="0" name="demo_class_price" value="{{ $settings['demo_class_price'] ?? 0 }}" required class="w-full p-3 pl-8 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                    </div>
                </div>
                
                <div class="space-y-4 pt-4 border-t border-outline-variant/30">
                    <h3 class="font-bold text-sm text-on-surface">Active Payment Gateways</h3>
                    <label class="flex items-center justify-between p-3 bg-surface-container-low border border-outline-variant rounded-xl cursor-pointer">
                        <span class="text-on-surface text-sm font-medium">Stripe (Credit/Debit Cards, Apple Pay)</span>
                        <div class="relative inline-flex items-center">
                            <input type="checkbox" name="enable_stripe" value="1" class="sr-only peer" {{ ($settings['enable_stripe'] ?? 1) ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-surface-container peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-outline after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                        </div>
                    </label>
                    <label class="flex items-center justify-between p-3 bg-surface-container-low border border-outline-variant rounded-xl cursor-pointer">
                        <span class="text-on-surface text-sm font-medium">PayPal (PayPal Balance & Cards)</span>
                        <div class="relative inline-flex items-center">
                            <input type="checkbox" name="enable_paypal" value="1" class="sr-only peer" {{ ($settings['enable_paypal'] ?? 1) ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-surface-container peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-outline after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                        </div>
                    </label>
                    <label class="flex items-center justify-between p-3 bg-surface-container-low border border-outline-variant rounded-xl cursor-pointer">
                        <span class="text-on-surface text-sm font-medium">Razorpay (UPI, Netbanking, India)</span>
                        <div class="relative inline-flex items-center">
                            <input type="checkbox" name="enable_razorpay" value="1" class="sr-only peer" {{ ($settings['enable_razorpay'] ?? 1) ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-surface-container peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-outline after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                        </div>
                    </label>
                </div>
                <button type="submit" class="btn-primary w-full">Save Settings</button>
            </form>
        </div>
    </div>
</div>
@endsection
