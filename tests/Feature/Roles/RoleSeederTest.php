<?php

namespace Tests\Feature\Roles;

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_all_roles(): void
    {
        $this->seed(RoleSeeder::class);

        $this->assertEqualsCanonicalizing(
            ['admin', 'super_admin', 'sales_manager', 'accounts', 'purchasing', 'warehouse_keeper', 'executive', 'rep'],
            Role::pluck('name')->toArray()
        );

        $this->assertSame(8, Role::count());
    }

    public function test_admin_has_full_access(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = Role::findByName('admin');
        $this->assertTrue($admin->hasPermissionTo('full_access'));
    }

    public function test_rep_permissions_match_guide(): void
    {
        $this->seed(RoleSeeder::class);

        $rep = Role::findByName('rep');
        $this->assertTrue($rep->hasPermissionTo('visits.execute'));
        $this->assertTrue($rep->hasPermissionTo('invoices.create'));
        $this->assertTrue($rep->hasPermissionTo('payments.collect'));
        $this->assertFalse($rep->hasPermissionTo('full_access'));
        $this->assertFalse($rep->hasPermissionTo('products.view_cost'));
    }

    public function test_accounts_can_view_cost_but_rep_cannot(): void
    {
        $this->seed(RoleSeeder::class);

        $accounts = Role::findByName('accounts');
        $this->assertTrue($accounts->hasPermissionTo('products.view_cost'));

        $rep = Role::findByName('rep');
        $this->assertFalse($rep->hasPermissionTo('products.view_cost'));
    }
}
