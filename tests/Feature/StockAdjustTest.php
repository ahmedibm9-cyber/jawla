<?php

namespace Tests\Feature;

use App\Enums\StockReason;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Contracts\StockService;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockAdjustTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    public function test_admin_can_view_stock_balances(): void
    {
        $admin = User::where('email', 'admin@jawla.test')->first();
        $this->actingAs($admin);

        $this->get('/admin/stocks')->assertOk();
    }

    public function test_warehouse_keeper_can_view_stock_balances(): void
    {
        $warehouseKeeper = User::where('email', 'warehouse@jawla.test')->first();
        $this->actingAs($warehouseKeeper);

        $this->get('/admin/stocks')->assertOk();
    }

    public function test_stock_adjustment_increases_quantity(): void
    {
        $admin = User::where('email', 'admin@jawla.test')->first();
        $warehouse = Warehouse::where('type', 'main')->first();
        $product = Product::first();

        $this->actingAs($admin);

        $stockService = app(StockService::class);
        $stockService->increment(
            $warehouse->id, $product->id, null, 100.0,
            StockReason::Initial, $product,
        );

        $stockBefore = $stockService->balance($warehouse->id, $product->id);

        $stockService->increment(
            $warehouse->id, $product->id, null, 25.0,
            StockReason::Adjustment, $product,
        );

        $stockAfter = $stockService->balance($warehouse->id, $product->id);
        $this->assertEquals($stockBefore + 25.0, $stockAfter);
    }

    public function test_stock_adjustment_creates_movement_record(): void
    {
        $admin = User::where('email', 'admin@jawla.test')->first();
        $warehouse = Warehouse::where('type', 'main')->first();
        $product = Product::first();

        $this->actingAs($admin);

        $stockService = app(StockService::class);
        $stockService->increment(
            $warehouse->id, $product->id, null, 50.0,
            StockReason::Adjustment, $product,
        );

        // StockService uses 'reason' column which may store the StockReason enum value
        $this->assertDatabaseHas('stock_movements', [
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_rep_is_redirected_from_admin_stock_page(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->first();
        $this->actingAs($rep);

        // Reps get redirected by EnsureRepRole middleware
        $this->get('/admin/stocks')->assertRedirect();
    }
}
