<?php

namespace App\Support;

use App\Services\CurrencyService;

/**
 * Renders a price span that is identical for every visitor (USD/base) so the
 * containing page can be edge-cached by Cloudflare. Per-country values are
 * emitted as data attributes; resources/js/currency.js swaps the visible text
 * once the visitor's country is known.
 */
class PriceRenderer
{
    /**
     * @param  float|int|string  $usd  Base (USD) amount.
     * @param  array<string,float|int>  $local  Optional explicit per-currency
     *                                          overrides, e.g. ['INR' => 500].
     * @param  string  $class  Extra classes for the wrapper span.
     * @param  string  $currencyClass  Classes only applied to the currency part (unused hook).
     */
    public function render($usd, array $local = [], string $class = '', string $currencyClass = ''): string
    {
        $usd = (float) $usd;

        $attrs = [
            'data-price' => $this->trim($usd),
            'data-price-usd' => $this->trim($usd),
        ];

        foreach ($local as $currency => $amount) {
            $code = strtolower((string) $currency);
            $attrs['data-price-'.$code] = $this->trim((float) $amount);
        }

        if ($class !== '') {
            $attrs['class'] = $class;
        }
        if ($currencyClass !== '') {
            $attrs['data-currency-class'] = $currencyClass;
        }

        $attributeString = '';
        foreach ($attrs as $name => $value) {
            $attributeString .= ' '.$name.'="'.e($value).'"';
        }

        // Server-side default text is the USD representation so the markup is
        // stable and cacheable. JS replaces it for non-USD visitors.
        $text = CurrencyService::formatLocal($usd, 'USD');

        return '<span'.$attributeString.'>'.$text.'</span>';
    }

    private function trim(float $amount): string
    {
        $formatted = rtrim(rtrim(number_format($amount, 2, '.', ''), '0'), '.');

        return $formatted === '' ? '0' : $formatted;
    }
}
