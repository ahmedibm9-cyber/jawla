<?php

namespace Tests\Unit\Services;

use App\Enums\StockReason;
use App\Exceptions\Domain\InsufficientStockException;
use App\Models\Company;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\Contracts\StockService as StockServiceContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_increment_increases_stock_and_writes_movement(): void
    {
        $company = Company::factory()->create();
        $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id]);

        $service = app(StockServiceContract::class);

        $movement = $service->increment(
            $warehouse->id,
            $product->id,
            null,
            10.500,
            StockReason::Initial,
            $product,
        );

        $this->assertSame(10.500, (float) Stock::where('warehouse_id', $warehouse->id)->value('quantity'));
        $this->assertInstanceOf(StockMovement::class, $movement);
        $this->assertSame(10.500, (float) $movement->quantity_change);
        $this->assertSame(StockReason::Initial, $movement->reason);
    }

    public function test_decrement_decreases_stock_and_writes_movement(): void
    {
        $company = Company::factory()->create();
        $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id]);

        $service = app(StockServiceContract::class);

        $service->increment($warehouse->id, $product->id, null, 100.000, StockReason::Initial, $product);
        $service->decrement($warehouse->id, $product->id, null, 30.500, StockReason::Sale, $product);

        $this->assertSame(69.500, (float) Stock::where('warehouse_id', $warehouse->id)->value('quantity'));
        $this->assertSame(2, StockMovement::count());
    }

    public function test_decrement_throws_insufficient_stock_exception_below_zero(): void
    {
        $company = Company::factory()->create();
        $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id]);

        $service = app(StockServiceContract::class);

        $this->expectException(InsufficientStockException::class);
        $service->decrement($warehouse->id, $product->id, null, 1.0, StockReason::Sale, $product);
    }

    public function test_decrement_does_not_create_partial_state_on_insufficient_stock(): void
    {
        $company = Company::factory()->create();
        $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id]);

        $service = app(StockServiceContract::class);

        $service->increment($warehouse->id, $product->id, null, 5.0, StockReason::Initial, $product);

        try {
            $service->decrement($warehouse->id, $product->id, null, 10.0, StockReason::Sale, $product);
        } catch (InsufficientStockException $e) {
        }

        $this->assertSame(5.0, (float) Stock::where('warehouse_id', $warehouse->id)->value('quantity'));
        $this->assertSame(1, StockMovement::count());
    }

    public function test_balance_returns_correct_quantity(): void
    {
        $company = Company::factory()->create();
        $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id]);

        $service = app(StockServiceContract::class);

        $service->increment($warehouse->id, $product->id, null, 50.250, StockReason::Initial, $product);

        $this->assertSame(50.250, $service->balance($warehouse->id, $product->id));
    }
}