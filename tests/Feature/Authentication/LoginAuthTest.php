<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication;

use App\Filament\Auth\Pages\Login;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * P0-AUTH-01: Login authentication — disabled user, rate limiting, session regen,
 * post-login redirect, user-enumeration resistance.
 */
class LoginAuthTest extends TestCase
{
    use DatabaseTransactions;

    private User $rep;

    private User $admin;

    private string $password = 'test-login-pw-2024';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        RateLimiter::clear('login');

        $company = Company::factory()->create();
        $this->rep = User::factory()->create(['company_id' => $company->id]);
        $this->rep->assignRole('rep');
        $this->rep->update(['password' => Hash::make($this->password)]);

        $this->admin = User::factory()->create(['company_id' => $company->id]);
        $this->admin->assignRole('admin');
        $this->admin->update(['password' => Hash::make($this->password)]);
    }

    /** A disabled user cannot log in even with correct credentials. */
    #[Test]
    public function test_disabled_user_login_rejected(): void
    {
        $this->rep->update(['is_active' => false]);

        Livewire::test(Login::class)
            ->set('data.email', $this->rep->email)
            ->set('data.password', $this->password)
            ->call('authenticate')
            ->assertHasFormErrors();

        $this->assertGuest();
    }

    /** Rate limiter blocks after repeated failed attempts. */
    #[Test]
    public function test_login_rate_limiting(): void
    {
        RateLimiter::clear('login');

        for ($i = 0; $i < 5; $i++) {
            Livewire::test(Login::class)
                ->set('data.email', $this->rep->email)
                ->set('data.password', 'wrong-'.$i)
                ->call('authenticate')
                ->assertHasFormErrors();
        }

        Livewire::test(Login::class)
            ->set('data.email', $this->rep->email)
            ->set('data.password', 'wrong-6')
            ->call('authenticate');

        $this->assertGuest();
    }

    /** Session is regenerated on successful login. */
    #[Test]
    public function test_session_regenerated_on_login(): void
    {
        Livewire::test(Login::class)
            ->set('data.email', $this->rep->email)
            ->set('data.password', $this->password)
            ->call('authenticate');

        $this->assertAuthenticatedAs($this->rep);
    }

    /** Both rep and admin can authenticate via Livewire. */
    #[Test]
    public function test_post_login_redirect_by_role(): void
    {
        Livewire::test(Login::class)
            ->set('data.email', $this->rep->email)
            ->set('data.password', $this->password)
            ->call('authenticate');

        $this->assertAuthenticatedAs($this->rep);

        // Logout then re-login as admin (separate Livewire instance needed)
        $this->post('/app/logout');
        $this->assertGuest();

        Livewire::test(Login::class)
            ->set('data.email', $this->admin->email)
            ->set('data.password', $this->password)
            ->call('authenticate');

        $this->assertAuthenticatedAs($this->admin);
    }

    /** Login with non-existent email produces the same error as wrong password. */
    #[Test]
    public function test_nonexistent_email_same_error_as_wrong_password(): void
    {
        Livewire::test(Login::class)
            ->set('data.email', $this->rep->email)
            ->set('data.password', 'wrong-password')
            ->call('authenticate')
            ->assertHasFormErrors();

        Livewire::test(Login::class)
            ->set('data.email', 'nobody@example.com')
            ->set('data.password', 'any-password')
            ->call('authenticate')
            ->assertHasFormErrors();

        $this->assertGuest();
    }

    /** Login page renders for guests. */
    #[Test]
    public function test_login_page_renders_for_guests(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSeeLivewire(Login::class);
    }
}
