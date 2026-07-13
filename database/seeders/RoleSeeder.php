<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Admin
            'full_access',
            // Sales Manager
            'visit_assignments.manage', 'customers.approve', 'customers.view_all',
            'pricing.set_range', 'alarms.view_all', 'alarms.respond',
            'complaints.manage', 'reports.sales', 'reports.visits', 'reports.view',
            'invoices.view_all', 'invoices.approve', 'purchase_requests.veto',
            'van_transfers.approve',
            // Accounts
            'products.manage_prices', 'products.view_cost', 'products.manage_cost',
            'goods_in_transit.manage_landed_cost', 'invoices.view_all',
            'invoices.cancel', 'payments.view_all', 'reports.financial',
            'customers.view_all', 'tax_templates.manage',
            // Purchasing
            'purchase_requests.view_all', 'supplier_quotations.manage',
            'purchase_orders.manage', 'goods_in_transit.manage',
            'goods_in_transit.receive', 'stock.view', 'suppliers.manage',
            'reports.purchasing',
            // Warehouse Keeper
            'stock.import', 'stock.adjust', 'stock.export', 'stock.view',
            'batches.manage', 'goods_in_transit.receive', 'reports.stock',
            // Executive
            'alarms.view', 'reports.dashboard_view', 'reports.sales_view',
            'reports.visits_view', 'reports.stock_view', 'reports.intercompany_view',
            // Rep
            'sessions.manage', 'visits.view_assigned', 'visits.execute',
            'visits.custom', 'customers.add', 'customers.view_own',
            'products.view', 'products.view_stock', 'pricing.request',
            'pricing.negotiate', 'proformas.create', 'invoices.create',
            'invoices.view_own', 'payments.collect', 'returns.create',
            'expenses.log', 'purchase_requests.submit', 'alarms.flag_out_of_stock',
            'complaints.submit', 'cashbox.view', 'van_transfers.request',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $rolePermissions = [
            'admin' => ['full_access'],
            'sales_manager' => [
                'visit_assignments.manage', 'customers.approve', 'customers.view_all',
                'pricing.set_range', 'alarms.view_all', 'alarms.respond',
                'complaints.manage', 'reports.sales', 'reports.visits', 'reports.view',
                'invoices.view_all', 'invoices.approve', 'purchase_requests.veto',
                'van_transfers.approve',
            ],
            'accounts' => [
                'products.manage_prices', 'products.view_cost', 'products.manage_cost',
                'goods_in_transit.manage_landed_cost', 'invoices.view_all',
                'invoices.cancel', 'payments.view_all', 'reports.financial',
                'customers.view_all', 'tax_templates.manage',
            ],
            'purchasing' => [
                'purchase_requests.view_all', 'supplier_quotations.manage',
                'purchase_orders.manage', 'goods_in_transit.manage',
                'goods_in_transit.receive', 'stock.view', 'suppliers.manage',
                'reports.purchasing',
            ],
            'warehouse_keeper' => [
                'stock.import', 'stock.adjust', 'stock.export', 'stock.view',
                'batches.manage', 'goods_in_transit.receive', 'reports.stock',
            ],
            'executive' => [
                'alarms.view', 'reports.dashboard_view', 'reports.sales_view',
                'reports.visits_view', 'reports.stock_view', 'reports.intercompany_view',
            ],
            'rep' => [
                'sessions.manage', 'visits.view_assigned', 'visits.execute',
                'visits.custom', 'customers.add', 'customers.view_own',
                'products.view', 'products.view_stock', 'pricing.request',
                'pricing.negotiate', 'proformas.create', 'invoices.create',
                'invoices.view_own', 'payments.collect', 'returns.create',
                'expenses.log', 'purchase_requests.submit', 'alarms.flag_out_of_stock',
                'complaints.submit', 'cashbox.view', 'van_transfers.request',
            ],
        ];

        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($perms);
        }
    }
}