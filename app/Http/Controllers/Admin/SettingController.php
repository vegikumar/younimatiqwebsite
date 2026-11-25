<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::pluck('value', 'key')->toArray();
        return \App\Support\InertiaPage::react('Admin/PlatformSettings/Index', [
            'settings' => $settings,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'demo_class_price' => 'nullable|numeric|min:0',
            'enable_stripe' => 'nullable|boolean',
            'enable_paypal' => 'nullable|boolean',
            'enable_razorpay' => 'nullable|boolean',
        ]);
        
        // Handle unchecked checkboxes
        $data['enable_stripe'] = $request->has('enable_stripe') ? 1 : 0;
        $data['enable_paypal'] = $request->has('enable_paypal') ? 1 : 0;
        $data['enable_razorpay'] = $request->has('enable_razorpay') ? 1 : 0;

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? 0]
            );
        }

        \Illuminate\Support\Facades\Cache::forget('global_settings');

        return back()->with('success', 'Platform settings updated successfully.');
    }
}
