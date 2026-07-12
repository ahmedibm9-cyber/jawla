<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_sales_rep_can_log_into_app(): void
    {
        $rep = $this->makeUser('sales_rep');

        $response = $this->post('/app/login', [
            'email' => $rep->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('app.home'));
        $this->assertAuthenticatedAs($rep);
    }

    public function test_rep_login_rejects_non_rep_role(): void
    {
        $hrAdmin = $this->makeUser('hr_admin');

        $response = $this->post('/app/login', [
            'email' => $hrAdmin->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_non_rep_is_blocked_from_app_routes(): void
    {
        $hrAdmin = $this->makeUser('hr_admin');

        $response = $this->actingAs($hrAdmin)->get('/app');

        $response->assertForbidden();
    }

    public function test_rep_is_blocked_from_admin_panel(): void
    {
        $rep = $this->makeUser('sales_rep');

        $response = $this->actingAs($rep)->get('/admin');

        $response->assertForbidden();
    }

    public function test_rep_login_rejects_wrong_password(): void
    {
        $rep = $this->makeUser('sales_rep');

        $response = $this->post('/app/login', [
            'email' => $rep->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_rep_login_is_rate_limited(): void
    {
        $rep = $this->makeUser('sales_rep');

        for ($i = 0; $i < 5; $i++) {
            $this->post('/app/login', [
                'email' => $rep->email,
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post('/app/login', [
            'email' => $rep->email,
            'password' => 'password',
        ]);

        $response->assertStatus(429);
        $this->assertGuest();
    }
}
