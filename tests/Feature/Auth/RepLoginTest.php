<?php

namespace Tests\Feature\Auth;

use App\Filament\Auth\Pages\Login;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RepLoginTest extends TestCase
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

    public function test_sales_rep_can_log_in_via_admin_login(): void
    {
        $rep = $this->makeUser('rep');

        Livewire::test(Login::class)
            ->set('data.email', $rep->email)
            ->set('data.password', 'password')
            ->call('authenticate');

        $this->assertAuthenticatedAs($rep);
    }

    public function test_non_rep_is_blocked_from_app_routes(): void
    {
        $hrAdmin = $this->makeUser('executive');

        $response = $this->actingAs($hrAdmin)->get('/app');

        $response->assertForbidden();
    }

    public function test_rep_is_redirected_from_admin_panel(): void
    {
        $rep = $this->makeUser('rep');

        $response = $this->actingAs($rep)->get('/admin');

        $response->assertRedirect('/app');
    }

    public function test_admin_login_rejects_wrong_password(): void
    {
        $rep = $this->makeUser('rep');

        Livewire::test(Login::class)
            ->set('data.email', $rep->email)
            ->set('data.password', 'wrong-password')
            ->call('authenticate')
            ->assertHasFormErrors();

        $this->assertGuest();
    }
}
