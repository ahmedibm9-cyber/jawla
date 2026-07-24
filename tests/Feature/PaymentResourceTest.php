<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * US-6.3 — View Payment Register
 *
 * Tests that admin can list payments and see the register page.
 */
class PaymentResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    public function test_admin_can_view_payment_register(): void
    {
        $admin = User::where('email', 'admin@jawla.test')->first();
        $this->actingAs($admin);

        $this->get('/admin/payments')->assertOk();
    }

    public function test_accounts_can_view_payment_register(): void
    {
        $accounts = User::where('email', 'accounts@jawla.test')->first();
        $this->actingAs($accounts);

        $this->get('/admin/payments')->assertOk();
    }

    public function test_sales_manager_can_view_payment_register(): void
    {
        $manager = User::where('email', 'manager@jawla.test')->first();
        $this->actingAs($manager);

        $this->get('/admin/payments')->assertOk();
    }

    public function test_rep_is_redirected_from_payment_register(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->first();
        $this->actingAs($rep);

        $this->get('/admin/payments')->assertRedirect();
    }

    public function test_warehouse_keeper_is_forbidden_from_payment_register(): void
    {
        $warehouseKeeper = User::where('email', 'warehouse@jawla.test')->first();
        $this->actingAs($warehouseKeeper);

        $this->get('/admin/payments')->assertForbidden();
    }
}
