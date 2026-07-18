<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Auth\Pages\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function makeUser(string $role, string $password = 'password'): User
    {
        $user = User::factory()->create([
            'company_id' => Company::factory()->create()->id,
            'password' => $password,
        ]);

        $user->assignRole($role);

        return $user;
    }

    public function test_admin_role_can_log_into_admin_panel(): void
    {
        $user = $this->makeUser('admin');

        Livewire::test(Login::class)
            ->set('data.email', $user->email)
            ->set('data.password', 'password')
            ->call('authenticate');

        $this->assertAuthenticatedAs($user);
    }

    public function test_sales_rep_is_redirected_to_app_from_admin_panel(): void
    {
        $rep = $this->makeUser('rep');

        $response = $this->actingAs($rep)->get('/alarms');

        $response->assertRedirect('/app');
    }

    public function test_inactive_user_cannot_log_into_admin_panel(): void
    {
        $user = $this->makeUser('admin', 'password');
        $user->update(['is_active' => false]);

        Livewire::test(Login::class)
            ->set('data.email', $user->email)
            ->set('data.password', 'password')
            ->call('authenticate')
            ->assertHasFormErrors();

        $this->assertGuest();
    }

    public function test_admin_login_rejects_wrong_password(): void
    {
        $user = $this->makeUser('admin');

        Livewire::test(Login::class)
            ->set('data.email', $user->email)
            ->set('data.password', 'wrong-password')
            ->call('authenticate')
            ->assertHasFormErrors();

        $this->assertGuest();
    }

    public function test_admin_login_is_rate_limited_after_five_attempts(): void
    {
        $user = $this->makeUser('admin');

        for ($i = 0; $i < 5; $i++) {
            Livewire::test(Login::class)
                ->set('data.email', $user->email)
                ->set('data.password', 'wrong-password')
                ->call('authenticate');
        }

        $this->assertGuest();

        Livewire::test(Login::class)
            ->set('data.email', $user->email)
            ->set('data.password', 'password')
            ->call('authenticate');

        $this->assertGuest();
    }
}
