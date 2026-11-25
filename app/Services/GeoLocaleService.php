<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeoLocaleService
{
    /** @var array<string, string> */
    private array $countryCurrency = [
        'US' => 'USD', 'CA' => 'USD',
        'GB' => 'GBP', 'IE' => 'EUR',
        'IN' => 'INR',
        'AE' => 'AED', 'SA' => 'AED',
        'OM' => 'OMR',
        'AU' => 'AUD', 'NZ' => 'AUD',
        'SG' => 'SGD', 'MY' => 'SGD',
        'DE' => 'EUR', 'FR' => 'EUR', 'IT' => 'EUR', 'ES' => 'EUR', 'NL' => 'EUR',
        'BE' => 'EUR', 'AT' => 'EUR', 'PT' => 'EUR', 'FI' => 'EUR',
    ];

    public function resolve(Request $request): array
    {
        $ip = $this->clientIp($request);

        // Cloudflare sets CF-IPCountry on every request — use it first and
        // skip the blocking ip-api.com roundtrip entirely.
        $cfCountry = strtoupper(trim((string) $request->header('CF-IPCountry', '')));
        if ($cfCountry !== '' && $cfCountry !== 'XX' && $cfCountry !== 'T1') {
            return Cache::remember('geo_cf_'.md5($cfCountry), now()->addDay(), function () use ($cfCountry) {
                return [
                    'country' => $this->countryName($cfCountry),
                    'country_code' => $cfCountry,
                    'state' => '',
                    'city' => '',
                    'timezone' => $this->defaultTimezoneForCountry($cfCountry),
                    'currency' => $this->mapCurrency($cfCountry),
                ];
            });
        }

        if ($this->isPrivateIp($ip)) {
            return $this->defaults();
        }

        $endpoint = "http://ip-api.com/json/{$ip}";

        return Cache::remember('geo_locale_'.md5($ip), now()->addDay(), function () use ($endpoint) {
            try {
                $response = Http::timeout(1)
                    ->get($endpoint, [
                        'fields' => 'status,country,countryCode,regionName,city,timezone,currency',
                    ]);

                if (! $response->successful()) {
                    return $this->defaults();
                }

                $data = $response->json();
                if (($data['status'] ?? '') !== 'success') {
                    return $this->defaults();
                }

                $code = strtoupper((string) ($data['countryCode'] ?? 'US'));

                return [
                    'country' => (string) ($data['country'] ?? 'United States'),
                    'country_code' => $code,
                    'state' => (string) ($data['regionName'] ?? ''),
                    'city' => (string) ($data['city'] ?? ''),
                    'timezone' => ! empty($data['timezone']) ? (string) $data['timezone'] : $this->defaultTimezoneForCountry($code),
                    'currency' => $this->mapCurrency($code, $data['currency'] ?? null),
                ];
            } catch (\Throwable $e) {
                Log::debug('GeoLocale lookup failed: '.$e->getMessage());

                return $this->defaults();
            }
        });
    }

    public function applyToSession(Request $request): void
    {
        $currentIp = $this->clientIp($request);

        if (session('locale_detected') && session('last_ip') === $currentIp) {
            return;
        }

        $geo = $this->resolve($request);

        session([
            'country' => $geo['country'],
            'country_code' => $geo['country_code'],
            'state' => $geo['state'],
            'city' => $geo['city'],
            'locale_detected' => true,
            'last_ip' => $currentIp,
        ]);

        if (! session('currency_manual')) {
            session(['display_currency' => $geo['currency']]);
        }

        if (! session('timezone_manual')) {
            session(['timezone' => $geo['timezone']]);
        }
    }

    public function mapCurrency(string $countryCode, ?string $apiCurrency = null): string
    {
        $supported = array_keys(CurrencyService::supportedCurrencies());

        if ($apiCurrency && in_array(strtoupper($apiCurrency), $supported, true)) {
            return strtoupper($apiCurrency);
        }

        $mapped = $this->countryCurrency[strtoupper($countryCode)] ?? 'USD';

        return in_array($mapped, $supported, true) ? $mapped : 'USD';
    }

    public function commonCountries(): array
    {
        return [
            'US' => 'United States',
            'IN' => 'India',
            'GB' => 'United Kingdom',
            'AE' => 'United Arab Emirates',
            'AU' => 'Australia',
            'SG' => 'Singapore',
            'CA' => 'Canada',
            'DE' => 'Germany',
            'FR' => 'France',
            'NL' => 'Netherlands',
            'IE' => 'Ireland',
            'NZ' => 'New Zealand',
            'SA' => 'Saudi Arabia',
            'OM' => 'Oman',
            'MY' => 'Malaysia',
            'OTHER' => 'Other',
        ];
    }

    private function clientIp(Request $request): string
    {
        $forwarded = $request->header('X-Forwarded-For');
        if ($forwarded) {
            $parts = array_map('trim', explode(',', $forwarded));

            return $parts[0] ?: $request->ip();
        }

        return $request->ip() ?: '127.0.0.1';
    }

    private function isPrivateIp(string $ip): bool
    {
        return ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    private function defaults(): array
    {
        return [
            'country' => 'United States',
            'country_code' => 'US',
            'state' => '',
            'city' => '',
            'timezone' => 'UTC',
            'currency' => 'USD',
        ];
    }

    private function defaultTimezoneForCountry(string $countryCode): string
    {
        $map = [
            'US' => 'America/New_York',
            'GB' => 'Europe/London',
            'IN' => 'Asia/Kolkata',
            'AE' => 'Asia/Dubai',
            'AU' => 'Australia/Sydney',
            'CA' => 'America/Toronto',
            'SG' => 'Asia/Singapore',
            'IE' => 'Europe/Dublin',
            'NZ' => 'Pacific/Auckland',
            'MY' => 'Asia/Kuala_Lumpur',
            'SA' => 'Asia/Riyadh',
            'OM' => 'Asia/Muscat',
        ];

        return $map[$countryCode] ?? 'UTC';
    }

    private function countryName(string $code): string
    {
        return $this->commonCountries()[$code] ?? $code;
    }
}
