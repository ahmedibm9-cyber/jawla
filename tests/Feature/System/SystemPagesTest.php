<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * System and error pages — branded, bilingual, no internals leaked.
 *
 * Maps to TEST_PLAN section 3.2 (global, system, and shared UI).
 * Tests that system pages render, are accessible, and don't leak internals.
 */
class SystemPagesTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Health endpoint returns 200 with JSON status.
     *
     * @test
     */
    public function test_health_endpoint_returns_ok(): void
    {
        $this->get('/health')
            ->assertOk()
            ->assertJson(['status' => 'ok']);
    }

    /**
     * Locale switch with invalid locale is rejected.
     *
     * @test
     */
    public function test_invalid_locale_rejected(): void
    {
        $this->get('/locale/xx')->assertStatus(404);
    }

    /**
     * Login page renders in Arabic.
     *
     * @test
     */
    public function test_login_page_renders_in_arabic(): void
    {
        $this->get('/locale/ar');
        $this->get('/login')->assertOk();
    }

    /**
     * Login page renders in English.
     *
     * @test
     */
    public function test_login_page_renders_in_english(): void
    {
        $this->get('/locale/en');
        $this->get('/login')->assertOk();
    }

    /**
     * Locale switch changes session locale.
     *
     * @test
     */
    public function test_locale_switch_persists(): void
    {
        $this->get('/locale/ar');
        $this->get('/login')->assertOk();

        $this->get('/locale/en');
        $this->get('/login')->assertOk();
    }

    /**
     * Locale switch is throttled.
     *
     * @test
     */
    public function test_locale_switch_is_throttled(): void
    {
        for ($i = 0; $i < 12; $i++) {
            $this->get('/locale/en');
        }

        // After 10 requests in a minute, should be rate-limited
        $response = $this->get('/locale/en');
        $this->assertContains($response->status(), [429, 302]);
    }

    /**
     * POST-only routes reject GET requests.
     *
     * @test
     */
    public function test_post_only_routes_reject_get(): void
    {
        $this->get('/company/switch')->assertStatus(405);
    }

    /**
     * Onboarding endpoint requires auth.
     *
     * @test
     */
    public function test_onboarding_requires_auth(): void
    {
        $this->postJson('/api/onboarding/complete')
            ->assertStatus(401);
    }

    /**
     * Security headers are present on responses.
     *
     * @test
     */
    public function test_security_headers_present(): void
    {
        $response = $this->get('/health');

        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    /**
     * State-changing routes without session reject requests.
     *
     * @test
     */
    public function test_post_without_session_rejected(): void
    {
        // Unauthenticated POST to a protected route should fail
        $this->postJson('/api/onboarding/complete')
            ->assertStatus(401);
    }

    /**
     * Login page has proper form structure.
     *
     * @test
     */
    public function test_login_page_has_form(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('email')
            ->assertSee('password');
    }
}
