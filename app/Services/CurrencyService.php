<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CurrencyService
{
    private static array $rates = [
        'USD' => 1.0,
        'EUR' => 0.92,
        'GBP' => 0.79,
        'INR' => 83.5,
        'AUD' => 1.53,
        'AED' => 3.67,
        'SGD' => 1.34,
        'OMR' => 0.38,
    ];

    public static function getRates(): array
    {
        return Cache::remember('currency_rates', 3600 * 12, function () {
            try {
                $response = Http::timeout(1)->get('https://api.exchangerate-api.com/v4/latest/USD');
                if ($response->successful()) {
                    return $response->json()['rates'];
                }
            } catch (\Exception $e) {
                // ignore
            }
            return self::$rates;
        });
    }

    public static function convert(float $usdAmount, string $toCurrency = 'USD'): float
    {
        $toCurrency = strtoupper($toCurrency);
        if ($toCurrency === 'USD') {
            return ceil($usdAmount);
        }

        $rates = self::getRates();
        $rate = $rates[$toCurrency] ?? 1.0;

        // Apply 5% extra safety margin on non-USD currency conversions as USD is base price
        return ceil($usdAmount * $rate * 1.05);
    }

    /**
     * Convert an amount between supported currencies (via USD mid rate).
     * Used when a gateway (e.g. Razorpay) cannot charge the shopper's display currency.
     */
    public static function convertBetween(float $amount, string $fromCurrency, string $toCurrency): float
    {
        $from = strtoupper($fromCurrency);
        $to = strtoupper($toCurrency);
        if ($from === $to) {
            return round(max(0, $amount), 2);
        }

        $rates = self::getRates();
        $fromRate = (float) ($rates[$from] ?? ($from === 'USD' ? 1.0 : 1.0));
        $toRate = (float) ($rates[$to] ?? ($to === 'USD' ? 1.0 : 1.0));
        if ($fromRate <= 0) {
            $fromRate = 1.0;
        }
        if ($toRate <= 0) {
            $toRate = 1.0;
        }

        $usd = $from === 'USD' ? $amount : ($amount / $fromRate);
        if ($to === 'USD') {
            return round(max(0, $usd), 2);
        }

        // Small buffer when converting into a settlement currency for payments.
        return round(max(0, $usd * $toRate * 1.05), 2);
    }

    public static function format(float $usdAmount, ?string $currency = null): string
    {
        $currency = strtoupper($currency ?? session('display_currency', 'USD'));
        $converted = self::convert($usdAmount, $currency);

        return self::formatLocal($converted, $currency);
    }

    public static function formatLocal(float $localAmount, string $currency): string
    {
        $currency = strtoupper($currency);
        $symbols = ['USD' => '$', 'EUR' => '€', 'GBP' => '£', 'INR' => '₹', 'AUD' => 'A$', 'AED' => 'د.إ ', 'SGD' => 'S$', 'OMR' => 'ر.ع. '];
        $symbol = $symbols[$currency] ?? $currency . ' ';

        return $symbol . number_format($localAmount, 0);
    }

    public static function supportedCurrencies(): array
    {
        return [
            'USD' => 'US Dollar',
            'EUR' => 'Euro',
            'GBP' => 'British Pound',
            'INR' => 'Indian Rupee',
            'AUD' => 'Australian Dollar',
            'AED' => 'UAE Dirham',
            'SGD' => 'Singapore Dollar',
            'OMR' => 'Omani Rial',
        ];
    }
}
