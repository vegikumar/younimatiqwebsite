<?php

namespace Tests\Feature;

use App\Support\PriceRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CountryAgnosticPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_renderer_emits_base_and_bespoke_values(): void
    {
        $html = app(PriceRenderer::class)->render(22, ['INR' => 1000], 'text-5xl');

        $this->assertStringContainsString('data-price="22"', $html);
        $this->assertStringContainsString('data-price-usd="22"', $html);
        $this->assertStringContainsString('data-price-inr="1000"', $html);
        $this->assertStringContainsString('class="text-5xl"', $html);
        // Server-side default text is the USD representation.
        $this->assertStringContainsString('>$22</span>', $html);
    }

    public function test_price_renderer_formats_default_usd_without_decimals(): void
    {
        $html = app(PriceRenderer::class)->render(18);

        $this->assertStringContainsString('data-price-usd="18"', $html);
        $this->assertStringContainsString('>$18</span>', $html);
    }

    public function test_pricing_page_is_cacheable_and_contains_every_currency_variant(): void
    {
        $response = $this->get('/pricing');

        $response->assertOk();
        $this->assertSame('1', $response->headers->get('X-Cacheable'));

        $content = $response->getContent();
        // Both the India layout and the default layout ship in the same HTML.
        $this->assertStringContainsString('data-currency-only="INR"', $content);
        $this->assertStringContainsString('data-currency-only="OMR"', $content);
        $this->assertStringContainsString('data-currency-only="AED"', $content);
        $this->assertStringContainsString('data-currency-except="INR OMR AED"', $content);
    }

    public function test_landing_page_is_cacheable_and_country_agnostic(): void
    {
        $response = $this->get('/math');

        $response->assertOk();
        $this->assertSame('1', $response->headers->get('X-Cacheable'));

        $content = $response->getContent();
        $this->assertStringContainsString('data-currency-only="INR"', $content);
        $this->assertStringContainsString('data-price-usd', $content);
        // The India variant is isolated behind a currency-only wrapper, so the
        // bespoke INR price is not shown to default-currency visitors.
        $this->assertStringContainsString('data-price-inr="1000"', $content);
    }

    public function test_transactional_pages_are_not_cached(): void
    {
        $response = $this->get('/courses');

        $response->assertOk();
        $this->assertNull($response->headers->get('X-Cacheable'));
        $this->assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
    }

    public function test_geo_country_endpoint_exposes_currency_and_rates(): void
    {
        $response = $this->getJson('/geo/country');

        $response->assertOk();
        $this->assertArrayHasKey('currency', $response->json());
        $this->assertArrayHasKey('rates', $response->json());
        $this->assertArrayHasKey('symbols', $response->json());
        $this->assertSame('$', $response->json('symbols.USD'));
    }
}
