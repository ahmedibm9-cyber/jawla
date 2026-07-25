<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression guard for the hardened response headers set by
 * App\Http\Middleware\SecurityHeaders (registered on the web group and the
 * Filament admin panel). A missing/weakened header should fail CI.
 */
class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string> header => expected substring (empty = presence only)
     */
    private function requiredHeaders(): array
    {
        return [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Strict-Transport-Security' => 'max-age=',
            'Content-Security-Policy' => "default-src 'self'",
            'Permissions-Policy' => 'geolocation=(self)',
            'Cross-Origin-Opener-Policy' => 'same-origin',
        ];
    }

    public function test_web_group_sets_all_security_headers(): void
    {
        $response = $this->get('/admin/login');
        $response->assertOk();

        foreach ($this->requiredHeaders() as $header => $needle) {
            $this->assertTrue($response->headers->has($header), "Missing security header: {$header}");
            $this->assertStringContainsString($needle, (string) $response->headers->get($header), "Weakened header: {$header}");
        }
    }

    public function test_csp_locks_down_framing_and_base_uri(): void
    {
        $csp = (string) $this->get('/admin/login')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
        $this->assertStringContainsString("form-action 'self'", $csp);
    }
}
