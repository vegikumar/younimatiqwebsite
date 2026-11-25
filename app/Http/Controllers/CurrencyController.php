<?php

namespace App\Http\Controllers;

use App\Services\CurrencyService;
use App\Services\GeoLocaleService;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function switch(Request $request)
    {
        $request->validate(['currency' => 'required|in:'.implode(',', array_keys(CurrencyService::supportedCurrencies()))]);
        session([
            'display_currency' => strtoupper($request->currency),
            'currency_manual' => true,
        ]);

        return back();
    }

    public function updateLocale(Request $request, GeoLocaleService $geo)
    {
        $countries = array_keys($geo->commonCountries());
        $validated = $request->validate([
            'country_code' => 'nullable|string|in:'.implode(',', $countries),
            'state' => 'nullable|string|max:100',
            'timezone' => 'nullable|string|max:50',
            'currency' => 'nullable|in:'.implode(',', array_keys(CurrencyService::supportedCurrencies())),
        ]);

        if (! empty($validated['country_code'])) {
            $countriesMap = $geo->commonCountries();
            session([
                'country_code' => $validated['country_code'],
                'country' => $countriesMap[$validated['country_code']] ?? $validated['country_code'],
            ]);

            if (empty($validated['currency']) && ! session('currency_manual')) {
                session(['display_currency' => $geo->mapCurrency($validated['country_code'])]);
            }
        }

        if (array_key_exists('state', $validated)) {
            session(['state' => $validated['state'] ?? '']);
        }

        if (! empty($validated['timezone'])) {
            session([
                'timezone' => $validated['timezone'],
                'timezone_manual' => true,
            ]);
        }

        if (! empty($validated['currency'])) {
            session([
                'display_currency' => strtoupper($validated['currency']),
                'currency_manual' => true,
            ]);
        }

        return back()->with('success', 'Location preferences updated.');
    }
}
