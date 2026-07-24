<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    public function test_admin_can_access_po_register(): void
    {
        $admin = User::where('email', 'admin@jawla.test')->first();
        $this->actingAs($admin);

        $this->get('/admin/purchase-orders')->assertOk();
    }

    public function test_sales_manager_can_access_po_register(): void
    {
        $manager = User::where('email', 'manager@jawla.test')->first();
        $this->actingAs($manager);

        $this->get('/admin/purchase-orders')->assertOk();
    }

    public function test_purchasing_can_access_po_register(): void
    {
        $purchasing = User::where('email', 'purchasing@jawla.test')->first();
        $this->actingAs($purchasing);

        $this->get('/admin/purchase-orders')->assertOk();
    }

    public function test_rep_is_redirected_from_admin_panel(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->first();
        $this->actingAs($rep);

        // Reps get redirected to /app by EnsureRepRole middleware, not 403
        $this->get('/admin/purchase-orders')->assertRedirect();
    }
}
