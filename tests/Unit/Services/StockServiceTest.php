<?php

namespace Tests\Unit\Services;

use App\Enums\StockReason;
use App\Exceptions\Domain\InsufficientStockException;
use App\Models\Company;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
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

    public function test_reconcile_adjusts_stock_to_counted_quantity_when_higher(): void
    {
        $company = Company::factory()->create();
        $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['company_id' => $company->id]);
        $service = app(StockServiceContract::class);

        $service->increment($warehouse->id, $product->id, null, 50.0, StockReason::Initial, $product);
        $movement = $service->reconcile($warehouse->id, $product->id, null, 60.0, 'Stock count', $user->id);

        $this->assertSame(60.0, $service->balance($warehouse->id, $product->id));
        $this->assertSame(10.0, (float) $movement->quantity_change);
    }

    public function test_reconcile_adjusts_stock_to_counted_quantity_when_lower(): void
    {
        $company = Company::factory()->create();
        $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['company_id' => $company->id]);
        $service = app(StockServiceContract::class);

        $service->increment($warehouse->id, $product->id, null, 50.0, StockReason::Initial, $product);
        $movement = $service->reconcile($warehouse->id, $product->id, null, 40.0, 'Stock count', $user->id);

        $this->assertSame(40.0, $service->balance($warehouse->id, $product->id));
        $this->assertSame(-10.0, (float) $movement->quantity_change);
    }

    public function test_reconcile_no_change_when_counted_matches_current(): void
    {
        $company = Company::factory()->create();
        $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['company_id' => $company->id]);
        $service = app(StockServiceContract::class);

        $service->increment($warehouse->id, $product->id, null, 50.0, StockReason::Initial, $product);
        $movement = $service->reconcile($warehouse->id, $product->id, null, 50.0, 'Stock count', $user->id);

        $this->assertSame(50.0, $service->balance($warehouse->id, $product->id));
        $this->assertSame(0.0, (float) $movement->quantity_change);
    }

    public function test_reconcile_from_zero_stock(): void
    {
        $company = Company::factory()->create();
        $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['company_id' => $company->id]);
        $service = app(StockServiceContract::class);

        $movement = $service->reconcile($warehouse->id, $product->id, null, 25.0, 'Initial count', $user->id);

        $this->assertSame(25.0, $service->balance($warehouse->id, $product->id));
        $this->assertSame(25.0, (float) $movement->quantity_change);
    }

    public function test_reconcile_creates_stock_movement_record(): void
    {
        $company = Company::factory()->create();
        $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['company_id' => $company->id]);
        $service = app(StockServiceContract::class);

        $service->increment($warehouse->id, $product->id, null, 50.0, StockReason::Initial, $product);
        $movement = $service->reconcile($warehouse->id, $product->id, null, 60.0, 'Stock count', $user->id);

        $this->assertSame(StockReason::Adjustment, $movement->reason);
        $this->assertSame($warehouse->id, $movement->warehouse_id);
        $this->assertSame($product->id, $movement->product_id);
        $this->assertSame($user->id, $movement->user_id);
        $this->assertSame('reconciliation', $movement->reference_type);
    }
}
