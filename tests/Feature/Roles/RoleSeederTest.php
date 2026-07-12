<?php

namespace Tests\Feature\Roles;

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_all_five_roles(): void
    {
        $this->seed(RoleSeeder::class);

        $this->assertEqualsCanonicalizing(
            ['system_viewer', 'hr_admin', 'sales_manager', 'warehouse_keeper', 'sales_rep'],
            Role::pluck('name')->toArray()
        );

        $this->assertSame(5, Role::count());
    }

    public function test_permission_matrix_matches_roles_matrix_doc(): void
    {
        $this->seed(RoleSeeder::class);

        $salesManager = Role::findByName('sales_manager');
        $this->assertTrue($salesManager->hasPermissionTo('approve-van-transfers'));
        $this->assertFalse($salesManager->hasPermissionTo('manage-main-warehouse-stock'));

        $salesRep = Role::findByName('sales_rep');
        $this->assertTrue($salesRep->hasPermissionTo('field-work'));
        $this->assertTrue($salesRep->hasPermissionTo('access-app-panel'));
        $this->assertFalse($salesRep->hasPermissionTo('access-admin-panel'));

        $systemViewer = Role::findByName('system_viewer');
        $this->assertTrue($systemViewer->hasPermissionTo('manage-products-prices'));
    }
}
