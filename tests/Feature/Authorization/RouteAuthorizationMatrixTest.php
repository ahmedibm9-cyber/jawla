<?php

declare(strict_types=1);

namespace Tests\Feature\Authorization;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RouteAuthorizationMatrixTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function makeUser(string $role): User
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole($role);

        return $user;
    }

    /**
     * @test
     */
    public function test_rep_is_redirected_from_admin_panel(): void
    {
        $rep = $this->makeUser('rep');
        $this->actingAs($rep)->get('/admin/dashboard')->assertRedirect('/app');
    }

    /**
     * @test
     */
    public function test_admin_can_access_admin_panel(): void
    {
        $admin = $this->makeUser('admin');
        $response = $this->actingAs($admin)->get('/admin/dashboard');
        $this->assertNotEquals(403, $response->status());
    }

    /**
     * @test
     */
    public function test_non_rep_roles_denied_from_rep_pwa(): void
    {
        foreach (['/app', '/app/customers', '/app/visits'] as $route) {
            foreach (['admin', 'sales_manager', 'accounts'] as $role) {
                $user = $this->makeUser($role);
                $response = $this->actingAs($user)->get($route);
                $this->assertContains($response->status(), [403, 302], "Role {$role} blocked from {$route}");
            }
        }
    }

    /**
     * @test
     */
    public function test_rep_can_access_rep_pwa(): void
    {
        $rep = $this->makeUser('rep');
        $this->actingAs($rep)->get('/app')->assertOk();
        $this->actingAs($rep)->get('/app/customers')->assertOk();
    }

    /**
     * @test
     */
    public function test_unauthenticated_user_redirected(): void
    {
        foreach (['/admin/dashboard', '/app', '/app/customers'] as $route) {
            $this->get($route)->assertRedirect();
        }
    }
}
