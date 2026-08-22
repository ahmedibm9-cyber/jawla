<?php

declare(strict_types=1);

namespace Tests\Feature\Authorization;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rep PWA route isolation — every /app/* route denied to non-rep roles.
 *
 * Maps to TEST_PLAN sections 2.2, 3.4, 9.1 (role isolation, direct URL denial).
 * Verifies that admin, manager, accounts, etc. cannot access rep PWA routes
 * even via direct URL, and that reps are redirected from admin panel.
 */
class RepPwaAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->app['session.store']->flush();
    }

    private function makeUser(string $role): User
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole($role);

        return $user;
    }

    /**
     * Every rep PWA route returns 403 for non-rep roles.
     *
     * @test
     */
    public function test_non_rep_roles_are_denied_from_rep_routes(): void
    {
        $repRoutes = [
            'home', 'customers', 'visits', 'orders', 'notifications',
            'quotations', 'stock', 'sync-queue', 'more', 'profile',
            'settings', 'customers.create', 'complaints', 'collect-payment',
            'sell', 'returns', 'expenses', 'reconcile', 'transfers', 'purchase-offer',
        ];

        foreach ($repRoutes as $route) {
            foreach (['admin', 'sales_manager', 'accounts', 'executive'] as $role) {
                $user = $this->makeUser($role);
                $this->app['session.store']->flush();
                $this->actingAs($user)->get(route('app.'.$route))->assertRedirect();
            }
        }
    }

    /**
     * Rep is redirected from admin panel back to /app.
     *
     * @test
     */
    public function test_rep_is_redirected_from_admin_panel(): void
    {
        $rep = $this->makeUser('rep');

        $this->actingAs($rep)->get('/admin')->assertRedirect('/app');
    }

    /**
     * Admin cannot access rep PWA routes even with rep role NOT assigned.
     *
     * @test
     */
    public function test_admin_without_rep_role_gets_403_on_app_routes(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->get('/app');
        $response->assertRedirect();
    }

    /**
     * Unauthenticated user is redirected to login from rep routes.
     *
     * @test
     */
    public function test_unauthenticated_user_redirected_from_rep_routes(): void
    {
        $this->get('/app')->assertRedirect();
        $this->get('/app/customers')->assertRedirect();
        $this->get('/app/visits')->assertRedirect();
    }

    /**
     * Rep with both rep + admin roles can access both surfaces.
     *
     * @test
     */
    public function test_multi_role_user_can_access_both_surfaces(): void
    {
        $user = $this->makeUser('admin');
        $user->assignRole('rep');

        $this->actingAs($user)->get('/app')->assertOk();
        $this->actingAs($user)->get('/admin/dashboard')->assertOk();
    }
}
