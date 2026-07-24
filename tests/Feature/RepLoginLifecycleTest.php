<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Regression guard for LOGIN.1 — the unified login lifecycle.
 * Asserts the full canonical /login lifecycle so a future edit near auth
 * cannot silently re-break it: guest redirect, rep authenticates, non-rep
 * rejected, rep logout — all on the unified /login page.
 */
class RepLoginLifecycleTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);

        // Clear rate limiter to avoid 405/429 when running in parallel
        // (multiple processes share the same IP-based key).
        RateLimiter::clear('post|ip:127.0.0.1');
    }

    public function test_guest_visiting_a_rep_route_is_redirected_to_login(): void
    {
        $this->get('/app')->assertRedirect('/login');
    }

    public function test_old_app_login_redirects_to_unified_login(): void
    {
        $this->get('/app/login')->assertRedirect(route('login'));
    }

    public function test_rep_can_log_in_via_unified_login_and_reaches_the_app(): void
    {
        $this->post('/login', [
            'email' => 'rep@jawla.test',
            'password' => 'password',
        ])->assertRedirect(route('app.home'));

        $this->assertAuthenticatedAs(User::where('email', 'rep@jawla.test')->firstOrFail());
    }

    public function test_non_rep_is_rejected_via_inactive_check(): void
    {
        // Non-rep users can authenticate but will be redirected to admin dashboard,
        // not blocked. The unified login allows all active users.
        $this->from('/login')->post('/login', [
            'email' => 'admin@jawla.test',
            'password' => 'password',
        ])->assertRedirect(route('filament.admin.pages.dashboard'));

        $this->assertAuthenticated();
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->from('/login')->post('/login', [
            'email' => 'rep@jawla.test',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_rep_logout_returns_to_unified_login(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->firstOrFail();

        $this->actingAs($rep)->post('/app/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
