<?php

namespace App\Http\Controllers;

use App\Services\CurrencyService;
use App\Services\GeoLocaleService;
use Illuminate\Http\Request;

class GeoController extends Controller
{
    public function country(Request $request, GeoLocaleService $geo)
    {
        $geo->applyToSession($request);
        $code = strtoupper((string) (session('country_code') ?: 'US'));

        return response()->json([
            'country_code' => $code,
            'country' => session('country'),
            'currency' => strtoupper((string) (session('display_currency') ?: 'USD')),
            'rates' => CurrencyService::getRates(),
            'symbols' => self::symbols(),
        ], 200, ['Cache-Control' => 'no-store, private']);
    }

    /**
     * Currency symbols, mirrored from CurrencyService::formatLocal so the
     * client formatter matches server output exactly.
     *
     * @return array<string,string>
     */
    public static function symbols(): array
    {
        return ['USD' => '$', 'EUR' => '€', 'GBP' => '£', 'INR' => '₹', 'AUD' => 'A$', 'AED' => 'د.إ ', 'SGD' => 'S$', 'OMR' => 'ر.ع. '];
    }
}
