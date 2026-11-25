<?php

namespace App\Support;

/**
 * Maps shopper display currency → what each gateway can actually charge.
 *
 * Stripe: USD / AED / INR (card Checkout).
 * Razorpay: INR only on standard Indian merchant accounts.
 * PayPal: major currencies; AED is not supported → fall back to USD.
 */
class PaymentGatewayCurrency
{
    public const DISPLAY = ['USD', 'AED', 'INR'];

    public const PAYPAL = ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 'SGD', 'NZD', 'CHF', 'HKD'];

    public static function normalizeDisplay(?string $currency): string
    {
        $currency = strtoupper((string) $currency);
        return in_array($currency, self::DISPLAY, true) ? $currency : 'USD';
    }

    public static function forStripe(?string $displayCurrency): string
    {
        return self::normalizeDisplay($displayCurrency);
    }

    public static function forRazorpay(): string
    {
        return 'INR';
    }

    public static function forPaypal(?string $displayCurrency): string
    {
        $currency = strtoupper((string) $displayCurrency);

        return in_array($currency, self::PAYPAL, true) ? $currency : 'USD';
    }

    /** Stripe Checkout minimum charge in minor units (cents / fils / paise). */
    public static function stripeMinMinor(string $currency): int
    {
        return match (strtoupper($currency)) {
            'AED' => 200, // AED 2.00
            'INR' => 50,
            default => 50, // USD 0.50
        };
    }
}
