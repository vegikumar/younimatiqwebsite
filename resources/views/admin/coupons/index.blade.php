@extends('layouts.dashboard')

@section('title', 'Coupons')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden">
        <h1 class="font-headline-xl text-headline-xl text-on-surface mb-2">Discount Coupons</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant">Create and manage promotional discount codes.</p>
        <div class="absolute right-0 top-0 -mr-20 -mt-20 w-64 h-64 bg-primary-fixed rounded-full blur-3xl opacity-20 pointer-events-none"></div>
    </div>

    

    <div class="grid grid-cols-1 lg:grid-cols-1 md:grid-cols-3 gap-5 md:p-8">
        <div class="clay-card p-5 md:p-8">
            <h2 class="font-headline-md font-bold mb-6 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">local_offer</span> Create Coupon
            </h2>
            <form method="POST" action="{{ route('admin.coupons.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-bold mb-2">Code</label>
                    <input type="text" name="code" required class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none uppercase" placeholder="SUMMER20">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold mb-2">Type</label>
                        <select name="type" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                            <option value="percent">Percent (%)</option>
                            <option value="fixed">Fixed ($)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Value</label>
                        <input type="number" name="value" step="0.01" min="0" required class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Min Order Amount</label>
                    <input type="number" name="min_amount" step="0.01" min="0" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" placeholder="Optional">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Max Uses</label>
                    <input type="number" name="max_uses" min="1" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" placeholder="Unlimited">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Expires At</label>
                    <input type="date" name="expires_at" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                </div>
                <button type="submit" class="btn-primary btn-block">Create Coupon</button>
            </form>
        </div>

        <div class="lg:col-span-2 clay-card p-5 md:p-8">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b-2 border-outline-variant">
                            <th class="py-4 px-4 font-bold">Code</th>
                            <th class="py-4 px-4 font-bold">Discount</th>
                            <th class="py-4 px-4 font-bold">Uses</th>
                            <th class="py-4 px-4 font-bold">Expires</th>
                            <th class="py-4 px-4 font-bold">Status</th>
                            <th class="py-4 px-4 font-bold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($coupons as $coupon)
                            <tr class="border-b border-outline-variant hover:bg-surface-container-low">
                                <td class="py-4 px-4 font-mono font-bold text-primary">{{ $coupon->code }}</td>
                                <td class="py-4 px-4">
                                    {{ $coupon->type === 'percent' ? $coupon->value . '%' : '$' . number_format($coupon->value, 2) }}
                                </td>
                                <td class="py-4 px-4 text-on-surface-variant">{{ $coupon->used_count }}{{ $coupon->max_uses ? '/' . $coupon->max_uses : '' }}</td>
                                <td class="py-4 px-4 text-sm text-on-surface-variant">{{ $coupon->expires_at ? $coupon->expires_at->format('M d, Y') : 'Never' }}</td>
                                <td class="py-4 px-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold {{ $coupon->isValid() ? 'bg-secondary-container text-on-secondary-container' : 'bg-error-container text-on-error-container' }}">
                                        {{ $coupon->isValid() ? 'Valid' : 'Expired' }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-right flex justify-end gap-2">
                                    <button data-coupon="{{ json_encode([
                                        'id' => $coupon->id,
                                        'code' => $coupon->code,
                                        'type' => $coupon->type,
                                        'value' => $coupon->value,
                                        'min_amount' => $coupon->min_amount,
                                        'max_uses' => $coupon->max_uses,
                                        'expires_at' => $coupon->expires_at ? $coupon->expires_at->format('Y-m-d') : ''
                                    ]) }}" 
                                            onclick="openEditCoupon(this)"
                                            type="button" 
                                            class="p-2 text-on-surface-variant hover:text-primary" 
                                            title="Edit">
                                        <span class="material-symbols-outlined text-sm">edit</span>
                                    </button>
                                    <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" onsubmit="return confirm('Delete this coupon?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-2 text-on-surface-variant hover:text-error"><span class="material-symbols-outlined text-sm">delete</span></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-8 text-center text-on-surface-variant">No coupons created yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Global Edit Modal -->
<div x-data="{ show: false, coupon: {} }" @open-edit.window="coupon = $event.detail; show = true">
    <div x-show="show" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-on-background/40 backdrop-blur-sm text-left">
        <div class="bg-white rounded-[var(--radius)] p-6 max-w-md w-full shadow-2xl relative" @click.away="show = false">
            <button @click="show = false" type="button" class="absolute top-4 right-4 p-2 text-on-surface-variant hover:bg-surface-container-low rounded-full"><span class="material-symbols-outlined text-sm">close</span></button>
            <h3 class="font-bold text-xl mb-4">Edit Coupon</h3>
            <form method="POST" :action="'/admin/coupons/' + coupon.id" class="space-y-4">
                @csrf @method('PUT')
                <div>
                    <label class="block text-sm font-bold mb-2">Code</label>
                    <input type="text" name="code" :value="coupon.code" required class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-[var(--radius)] focus:border-primary outline-none uppercase">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold mb-2">Type</label>
                        <select name="type" :value="coupon.type" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-[var(--radius)] focus:border-primary outline-none">
                            <option value="percent">Percent (%)</option>
                            <option value="fixed">Fixed ($)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Value</label>
                        <input type="number" name="value" :value="coupon.value" step="0.01" min="0" required class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-[var(--radius)] focus:border-primary outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Min Order Amount</label>
                    <input type="number" name="min_amount" :value="coupon.min_amount" step="0.01" min="0" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-[var(--radius)] focus:border-primary outline-none">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold mb-2">Max Uses</label>
                        <input type="number" name="max_uses" :value="coupon.max_uses" min="1" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-[var(--radius)] focus:border-primary outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Expires At</label>
                        <input type="date" name="expires_at" :value="coupon.expires_at" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-[var(--radius)] focus:border-primary outline-none">
                    </div>
                </div>
                <button type="submit" class="btn-primary w-full">Save Changes</button>
            </form>
        </div>
    </div>
</div>

<script>
function openEditCoupon(btn) {
    try {
        var couponData = JSON.parse(btn.dataset.coupon);
        window.dispatchEvent(new CustomEvent('open-edit', { detail: couponData }));
    } catch (e) {
        console.error("Error parsing coupon data", e);
    }
}
</script>

@endsection
