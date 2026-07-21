<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression guard for LOGIN.1 — the rep-login fragility that broke twice
 * (investigation-rep-login-fragility-2026-07-21). Asserts the full canonical
 * /app/login lifecycle so a future edit near auth cannot silently re-break it:
 * guest redirect, rep authenticates, non-rep rejected, rep logout — all on
 * /app/login. See bmad-output/stories/LOGIN.1.consolidate-rep-login-path.story.md.
 */
class RepLoginLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    public function test_guest_visiting_a_rep_route_is_redirected_to_app_login(): void
    {
        $this->get('/app')->assertRedirect('/app/login');
    }

    public function test_rep_can_log_in_via_app_login_and_reaches_the_app(): void
    {
        $this->post('/app/login', [
            'email' => 'rep@jawla.test',
            'password' => 'password',
        ])->assertRedirect(route('app.home'));

        $this->assertAuthenticatedAs(User::where('email', 'rep@jawla.test')->firstOrFail());
    }

    public function test_non_rep_is_rejected_at_app_login(): void
    {
        $this->from('/app/login')->post('/app/login', [
            'email' => 'admin@jawla.test',
            'password' => 'password',
        ])->assertRedirect('/app/login')->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->from('/app/login')->post('/app/login', [
            'email' => 'rep@jawla.test',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_rep_logout_returns_to_app_login(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->firstOrFail();

        $this->actingAs($rep)->post('/app/logout')->assertRedirect('/app/login');
        $this->assertGuest();
    }
}
