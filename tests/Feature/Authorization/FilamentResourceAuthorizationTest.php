<?php

declare(strict_types=1);

namespace Tests\Feature\Authorization;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Filament resource CRUD authorization — every resource × every role.
 *
 * Maps to TEST_PLAN section 3.6 (Filament resource coverage, P0 assertions).
 */
class FilamentResourceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

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
     * Resources that should be completely invisible to non-authorized roles.
     *
     * @test
     */
    public function test_restricted_resources_denied_to_unauthorized_roles(): void
    {
        $restricted = [
            '/admin/users' => ['sales_manager', 'accounts', 'purchasing', 'warehouse_keeper', 'executive', 'rep'],
            '/admin/companies' => ['sales_manager', 'accounts', 'purchasing', 'warehouse_keeper', 'executive', 'hr_admin', 'rep'],
            '/admin/invoices' => ['purchasing', 'warehouse_keeper', 'executive', 'rep'],
            '/admin/payments' => ['purchasing', 'warehouse_keeper', 'executive', 'rep'],
            '/admin/return-records' => ['rep'],
            '/admin/daily-visit-assignments' => ['accounts', 'purchasing', 'warehouse_keeper', 'executive', 'rep'],
            '/admin/purchase-requests' => ['accounts', 'executive', 'rep'],
            '/admin/purchase-orders' => ['sales_manager', 'accounts', 'warehouse_keeper', 'executive', 'rep'],
            '/admin/price-quotation-requests' => ['accounts', 'purchasing', 'warehouse_keeper', 'executive', 'rep'],
            '/admin/batches' => ['sales_manager', 'accounts', 'executive', 'rep'],
            '/admin/van-transfers' => ['sales_manager', 'accounts', 'purchasing', 'executive', 'rep'],
            '/admin/goods-in-transits' => ['sales_manager', 'accounts', 'executive', 'rep'],
            '/admin/complaints' => ['accounts', 'purchasing', 'warehouse_keeper', 'executive', 'rep'],
            '/admin/alarms' => ['purchasing', 'warehouse_keeper', 'rep'],
            '/admin/tasks' => ['sales_manager', 'accounts', 'purchasing', 'warehouse_keeper', 'executive', 'rep'],
            '/admin/expenses' => ['sales_manager', 'purchasing', 'warehouse_keeper', 'executive', 'rep'],
            '/admin/cash-reconciliations' => ['sales_manager', 'purchasing', 'warehouse_keeper', 'executive', 'rep'],
            '/admin/stocks' => ['sales_manager', 'accounts', 'purchasing', 'executive', 'rep'],
            '/admin/activity-log' => ['sales_manager', 'accounts', 'purchasing', 'warehouse_keeper', 'executive', 'rep'],
            '/admin/api-tokens' => ['sales_manager', 'accounts', 'purchasing', 'warehouse_keeper', 'executive', 'hr_admin', 'rep'],
            '/admin/stock-import' => ['sales_manager', 'accounts', 'purchasing', 'executive', 'rep'],
            '/admin/session-management' => ['sales_manager', 'accounts', 'purchasing', 'warehouse_keeper', 'executive', 'rep'],
            '/admin/supplier-comparison' => ['sales_manager', 'accounts', 'warehouse_keeper', 'executive', 'rep'],
        ];

        foreach ($restricted as $url => $deniedRoles) {
            foreach ($deniedRoles as $role) {
                $user = $this->makeUser($role);
                $response = $this->actingAs($user)->get($url);
                $this->assertGreaterThan(299, $response->status(), "Role {$role} should not access {$url}");
            }
        }
    }

    /**
     * system_viewer can see read-only resources but cannot access create pages.
     *
     * @test
     */
    public function test_system_viewer_cannot_access_create_pages(): void
    {
        $viewer = $this->makeUser('system_viewer');

        $createPages = [
            '/admin/customers/create',
            '/admin/products/create',
            '/admin/users/create',
            '/admin/routes/create',
            '/admin/purchase-requests/create',
        ];

        foreach ($createPages as $url) {
            $this->actingAs($viewer)->get($url)->assertForbidden();
        }
    }

    /**
     * system_viewer can access read-only list pages.
     *
     * @test
     */
    public function test_system_viewer_can_access_readonly_list_pages(): void
    {
        $viewer = $this->makeUser('system_viewer');

        $readOnlyPages = [
            '/admin/customers',
            '/admin/invoices',
            '/admin/payments',
            '/admin/stocks',
            '/admin/alarms',
            '/admin/reports-page',
        ];

        foreach ($readOnlyPages as $url) {
            $this->actingAs($viewer)->get($url)->assertOk();
        }
    }

    /**
     * Rep cannot access any admin resource.
     *
     * @test
     */
    public function test_rep_cannot_access_any_admin_resource(): void
    {
        $rep = $this->makeUser('rep');

        $adminResources = [
            '/admin/customers',
            '/admin/products',
            '/admin/invoices',
            '/admin/payments',
            '/admin/users',
            '/admin/routes',
            '/admin/stocks',
            '/admin/batches',
            '/admin/purchase-orders',
            '/admin/alarms',
            '/admin/tasks',
            '/admin/expenses',
        ];

        foreach ($adminResources as $url) {
            $response = $this->actingAs($rep)->get($url);
            $this->assertGreaterThan(299, $response->status(), "Rep should not access {$url}");
        }
    }
}
