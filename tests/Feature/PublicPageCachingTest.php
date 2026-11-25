<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPageCachingTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_home_is_edge_cacheable_with_deferred_csrf(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $this->assertStringContainsString('public', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('s-maxage=600', (string) $response->headers->get('Cache-Control'));
        $this->assertSame('1', $response->headers->get('X-Cacheable'));

        // The session-bound CSRF token must not be baked into the shared cache.
        $this->assertStringContainsString('name="csrf-token" content="deferred"', $response->getContent());

        // No per-visitor session cookie may be attached to a cacheable response.
        $setCookies = $response->headers->all('set-cookie');
        foreach ($setCookies as $cookie) {
            $this->assertStringNotContainsString(config('session.cookie'), $cookie);
            $this->assertStringNotContainsString('XSRF-TOKEN', $cookie);
        }
    }

    public function test_csrf_token_endpoint_returns_a_real_token(): void
    {
        $response = $this->getJson('/csrf-token');

        $response->assertOk();
        $this->assertNotSame('deferred', $response->json('token'));
        $this->assertNotEmpty($response->json('token'));
    }

    public function test_authenticated_requests_are_not_cached(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user, 'web')->get('/');

        $response->assertOk();
        $this->assertNull($response->headers->get('X-Cacheable'));
        $this->assertStringNotContainsString('content="deferred"', (string) $response->getContent());
    }
}
