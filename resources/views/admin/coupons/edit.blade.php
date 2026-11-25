@extends('layouts.dashboard')

@section('title', 'Edit Coupon')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="clay-card p-6 bg-white max-w-xl">
        <h1 class="font-headline-sm text-lg font-bold mb-4">Edit Coupon</h1>
        <form method="POST" action="{{ route('admin.coupons.update', $coupon) }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-bold text-on-surface mb-1">Code *</label>
                <input type="text" name="code" value="{{ old('code', $coupon->code) }}" required class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary outline-none text-sm">
            </div>
            <div>
                <label class="block text-sm font-bold text-on-surface mb-1">Type *</label>
                <select name="type" required class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary outline-none text-sm">
                    <option value="percent" {{ old('type', $coupon->type) === 'percent' ? 'selected' : '' }}>Percent</option>
                    <option value="fixed" {{ old('type', $coupon->type) === 'fixed' ? 'selected' : '' }}>Fixed</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-on-surface mb-1">Value *</label>
                <input type="number" step="0.01" min="0" name="value" value="{{ old('value', $coupon->value) }}" required class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary outline-none text-sm">
            </div>
            <div>
                <label class="block text-sm font-bold text-on-surface mb-1">Min Amount</label>
                <input type="number" step="0.01" min="0" name="min_amount" value="{{ old('min_amount', $coupon->min_amount) }}" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary outline-none text-sm">
            </div>
            <div>
                <label class="block text-sm font-bold text-on-surface mb-1">Max Uses</label>
                <input type="number" min="1" name="max_uses" value="{{ old('max_uses', $coupon->max_uses) }}" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary outline-none text-sm">
            </div>
            <div>
                <label class="block text-sm font-bold text-on-surface mb-1">Expires At</label>
                <input type="date" name="expires_at" value="{{ old('expires_at', optional($coupon->expires_at)->format('Y-m-d')) }}" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary outline-none text-sm">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-primary py-2 px-4 text-sm font-bold">Save</button>
                <a href="{{ route('admin.coupons') }}" class="btn-outline-primary py-2 px-4 text-sm font-bold">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
