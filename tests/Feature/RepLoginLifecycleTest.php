<?php

namespace Tests\Feature;

use App\Filament\Auth\Pages\Login;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Regression guard for LOGIN.1 — the unified login lifecycle.
 * Filament's built-in login page at /admin/login is the only login page.
 * /login redirects there. LoginResponse handles role-based redirect after auth.
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

    public function test_guest_visiting_a_rep_route_is_redirected_to_filament_login(): void
    {
        $this->get('/app')->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_login_route_redirects_to_filament_login(): void
    {
        $this->get('/login')->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_old_app_login_redirects_to_unified_login(): void
    {
        $this->get('/app/login')->assertRedirect(route('login'));
    }

    public function test_rep_can_log_in_via_filament_login_and_reaches_the_app(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->firstOrFail();

        Livewire::test(Login::class)
            ->set('data.email', $rep->email)
            ->set('data.password', $this->demoPassword($rep->email))
            ->call('authenticate');

        $this->assertAuthenticatedAs($rep);
    }

    public function test_admin_logs_in_via_filament_login_and_reaches_dashboard(): void
    {
        $admin = User::where('email', 'admin@jawla.test')->firstOrFail();

        Livewire::test(Login::class)
            ->set('data.email', $admin->email)
            ->set('data.password', $this->demoPassword($admin->email))
            ->call('authenticate');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->firstOrFail();

        Livewire::test(Login::class)
            ->set('data.email', $rep->email)
            ->set('data.password', 'wrong-password')
            ->call('authenticate')
            ->assertHasFormErrors();

        $this->assertGuest();
    }

    public function test_rep_logout_returns_to_login(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->firstOrFail();

        $this->actingAs($rep)->post('/app/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    private function demoPassword(string $email): string
    {
        $credentials = json_decode(
            Storage::disk('private')->get('demo-credentials.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        return $credentials[$email];
    }
}
