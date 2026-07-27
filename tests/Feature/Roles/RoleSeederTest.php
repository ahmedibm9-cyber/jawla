<?php

namespace Tests\Feature\Roles;

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_all_roles(): void
    {
        $this->seed(RoleSeeder::class);

        foreach (['sales_rep', 'sales_manager', 'hr_admin', 'warehouse_keeper', 'system_viewer'] as $role) {
            $this->assertTrue(Role::where('name', $role)->exists(), "Missing canonical role [{$role}].");
        }
    }

    public function test_admin_has_full_access(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = Role::findByName('admin');
        $totalPermissions = Permission::count();
        $this->assertEquals($totalPermissions, $admin->permissions->count(), 'Admin should have all permissions.');
    }

    public function test_sales_rep_permissions_match_approved_contract(): void
    {
        $this->seed(RoleSeeder::class);

        $rep = Role::findByName('sales_rep');
        $this->assertTrue($rep->hasPermissionTo('visits.execute'));
        $this->assertTrue($rep->hasPermissionTo('invoices.create'));
        $this->assertTrue($rep->hasPermissionTo('payments.collect'));
        $this->assertFalse($rep->hasPermissionTo('products.view_cost'));
    }

    public function test_accounts_can_view_cost_but_rep_cannot(): void
    {
        $this->seed(RoleSeeder::class);

        $accounts = Role::findByName('accounts');
        $this->assertTrue($accounts->hasPermissionTo('products.view_cost'));

        $rep = Role::findByName('sales_rep');
        $this->assertFalse($rep->hasPermissionTo('products.view_cost'));
    }

    public function test_system_viewer_has_no_mutation_permissions(): void
    {
        $this->seed(RoleSeeder::class);

        $viewer = Role::findByName('system_viewer');

        $this->assertTrue($viewer->hasPermissionTo('reports.view'));
        $this->assertFalse($viewer->hasPermissionTo('invoices.create'));
        $this->assertFalse($viewer->hasPermissionTo('stock.adjust'));
    }
}
