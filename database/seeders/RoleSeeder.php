<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Permission matrix per docs/ROLES_MATRIX.md.
     */
    private const MATRIX = [
        'system_viewer' => [
            'manage-users-roles', 'manage-company-settings', 'manage-products-prices',
            'manage-main-warehouse-stock', 'load-unload-vans', 'approve-van-transfers',
            'manage-routes', 'manage-customers', 'view-all-reps-data',
            'approve-cancel-invoices', 'reverse-activities', 'create-rep-tasks',
            'access-admin-panel',
        ],
        'hr_admin' => [
            'manage-users-roles', 'manage-company-settings', 'access-admin-panel',
        ],
        'sales_manager' => [
            'approve-van-transfers', 'manage-routes', 'manage-customers',
            'view-all-reps-data', 'approve-cancel-invoices', 'reverse-activities',
            'create-rep-tasks', 'access-admin-panel',
        ],
        'warehouse_keeper' => [
            'manage-main-warehouse-stock', 'load-unload-vans',
            'approve-van-transfers', 'access-admin-panel',
        ],
        'sales_rep' => [
            'field-work', 'access-app-panel',
        ],
    ];

    public function run(): void
    {
        $permissions = array_unique(array_merge(...array_values(self::MATRIX)));

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        foreach (self::MATRIX as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePermissions);
        }
    }
}
