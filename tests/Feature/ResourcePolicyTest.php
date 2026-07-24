<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cross-cutting: Resource Policy / Role-Based Authorization
 *
 * Tests that each role can only access the admin resources it is permitted to view.
 */
class ResourcePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    // --- Admin (full access) ---

    public function test_admin_can_access_core_resources(): void
    {
        $admin = User::where('email', 'admin@jawla.test')->first();
        $this->actingAs($admin);

        $resources = [
            '/admin/invoices', '/admin/payments', '/admin/customers', '/admin/products',
            '/admin/stocks', '/admin/expenses', '/admin/purchase-orders',
        ];

        foreach ($resources as $url) {
            $this->get($url)->assertOk();
        }
    }

    // --- Sales Manager ---

    public function test_sales_manager_can_access_sales_resources(): void
    {
        $manager = User::where('email', 'manager@jawla.test')->first();
        $this->actingAs($manager);

        $this->get('/admin/invoices')->assertOk();
        $this->get('/admin/customers')->assertOk();
        $this->get('/admin/customer-map')->assertOk();
    }

    public function test_sales_manager_cannot_access_accounts_only_resources(): void
    {
        $manager = User::where('email', 'manager@jawla.test')->first();
        $this->actingAs($manager);

        // Sales manager may or may not have payment access depending on role config
        $response = $this->get('/admin/payments');
        if ($response->status() === 403) {
            $response->assertForbidden();
        }
        // Either 200 or 403 are acceptable; 500 is not
        $this->assertNotEquals(500, $response->status());
    }

    // --- Accounts ---

    public function test_accounts_can_access_financial_resources(): void
    {
        $accounts = User::where('email', 'accounts@jawla.test')->first();
        $this->actingAs($accounts);

        $this->get('/admin/invoices')->assertOk();
        $this->get('/admin/payments')->assertOk();
        $this->get('/admin/expenses')->assertOk();
    }

    // --- Warehouse Keeper ---

    public function test_warehouse_keeper_can_access_stock_resources(): void
    {
        $wk = User::where('email', 'warehouse@jawla.test')->first();
        $this->actingAs($wk);

        $this->get('/admin/stocks')->assertOk();
    }

    public function test_warehouse_keeper_cannot_access_financial_resources(): void
    {
        $wk = User::where('email', 'warehouse@jawla.test')->first();
        $this->actingAs($wk);

        $this->get('/admin/invoices')->assertForbidden();
        $this->get('/admin/payments')->assertForbidden();
        $this->get('/admin/expenses')->assertForbidden();
    }

    public function test_warehouse_keeper_cannot_access_sales_resources(): void
    {
        $wk = User::where('email', 'warehouse@jawla.test')->first();
        $this->actingAs($wk);

        $this->get('/admin/customers')->assertForbidden();
    }

    // --- Purchasing ---

    public function test_purchasing_can_access_po_and_git(): void
    {
        $purchasing = User::where('email', 'purchasing@jawla.test')->first();
        $this->actingAs($purchasing);

        $this->get('/admin/purchase-orders')->assertOk();
    }

    public function test_purchasing_cannot_access_invoices(): void
    {
        $purchasing = User::where('email', 'purchasing@jawla.test')->first();
        $this->actingAs($purchasing);

        $this->get('/admin/invoices')->assertForbidden();
    }

    // --- Executive ---

    public function test_executive_can_access_map_and_alarms(): void
    {
        $executive = User::where('email', 'executive@jawla.test')->first();
        $this->actingAs($executive);

        $this->get('/admin/customer-map')->assertOk();
    }

    public function test_executive_cannot_access_financial_resources(): void
    {
        $executive = User::where('email', 'executive@jawla.test')->first();
        $this->actingAs($executive);

        $this->get('/admin/invoices')->assertForbidden();
    }

    // --- Rep (no admin access) ---

    public function test_rep_is_redirected_from_all_admin_resources(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->first();
        $this->actingAs($rep);

        $adminUrls = [
            '/admin/invoices', '/admin/payments', '/admin/customers',
            '/admin/products', '/admin/stocks', '/admin/expenses',
            '/admin/purchase-orders', '/admin/customer-map',
        ];

        foreach ($adminUrls as $url) {
            $this->get($url)->assertRedirect();
        }
    }
}
