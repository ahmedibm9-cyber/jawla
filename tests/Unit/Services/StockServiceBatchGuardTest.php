<?php

namespace Tests\Unit\Services;

use App\Enums\StockReason;
use App\Exceptions\Domain\DomainException;
use App\Models\Batch;
use App\Models\Company;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Contracts\StockService as StockServiceContract;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockServiceBatchGuardTest extends TestCase
{
    use RefreshDatabase;

    private StockServiceContract $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(StockServiceContract::class);
    }

    private function makeCompanyWarehouseProduct(): array
    {
        $company = Company::factory()->create();
        $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id, 'track_batch' => true, 'track_expiry' => true]);

        return [$company, $warehouse, $product];
    }

    public function test_decrement_rejects_nonexistent_batch(): void
    {
        [, $warehouse, $product] = $this->makeCompanyWarehouseProduct();

        $this->expectException(DomainException::class);
        $this->service->decrement($warehouse->id, $product->id, 99999, 1.0, StockReason::Sale, $product);
    }

    public function test_decrement_rejects_inactive_batch(): void
    {
        [, $warehouse, $product] = $this->makeCompanyWarehouseProduct();
        $batch = Batch::factory()->create(['product_id' => $product->id, 'is_active' => false]);

        $this->expectException(DomainException::class);
        $this->service->decrement($warehouse->id, $product->id, $batch->id, 1.0, StockReason::Sale, $product);
    }

    public function test_decrement_rejects_expired_batch(): void
    {
        [, $warehouse, $product] = $this->makeCompanyWarehouseProduct();
        $batch = Batch::factory()->create([
            'product_id' => $product->id,
            'expiry_date' => Carbon::yesterday(),
        ]);

        $this->expectException(DomainException::class);
        $this->service->decrement($warehouse->id, $product->id, $batch->id, 1.0, StockReason::Sale, $product);
    }

    public function test_decrement_rejects_batch_for_different_product(): void
    {
        [, $warehouse, $product] = $this->makeCompanyWarehouseProduct();
        $otherProduct = Product::factory()->create(['company_id' => $warehouse->company_id]);
        $batch = Batch::factory()->create(['product_id' => $otherProduct->id]);

        $this->expectException(DomainException::class);
        $this->service->decrement($warehouse->id, $product->id, $batch->id, 1.0, StockReason::Sale, $product);
    }

    public function test_decrement_rejects_cross_company_batch(): void
    {
        [$company, $warehouse] = $this->makeCompanyWarehouseProduct();
        $otherCompany = Company::factory()->create();
        $otherProduct = Product::factory()->create(['company_id' => $otherCompany->id]);
        $batch = Batch::factory()->create(['product_id' => $otherProduct->id]);

        $this->expectException(DomainException::class);
        $this->service->decrement($warehouse->id, $otherProduct->id, $batch->id, 1.0, StockReason::Sale, $otherProduct);
    }

    public function test_decrement_rejects_fefo_violation(): void
    {
        [, $warehouse, $product] = $this->makeCompanyWarehouseProduct();

        $earlyBatch = Batch::factory()->create([
            'product_id' => $product->id,
            'expiry_date' => Carbon::today()->addDays(5),
        ]);
        $lateBatch = Batch::factory()->create([
            'product_id' => $product->id,
            'expiry_date' => Carbon::today()->addDays(30),
        ]);

        // Stock the earlier-expiry batch
        $this->service->increment($warehouse->id, $product->id, $earlyBatch->id, 10.0, StockReason::Initial, $product);

        // Try to pick the later-expiry batch — should fail
        $this->expectException(DomainException::class);
        $this->service->decrement($warehouse->id, $product->id, $lateBatch->id, 1.0, StockReason::Sale, $product);
    }

    public function test_decrement_allows_fefo_compliant_batch(): void
    {
        [, $warehouse, $product] = $this->makeCompanyWarehouseProduct();

        $earlyBatch = Batch::factory()->create([
            'product_id' => $product->id,
            'expiry_date' => Carbon::today()->addDays(5),
        ]);
        $lateBatch = Batch::factory()->create([
            'product_id' => $product->id,
            'expiry_date' => Carbon::today()->addDays(30),
        ]);

        // Stock both batches
        $this->service->increment($warehouse->id, $product->id, $earlyBatch->id, 10.0, StockReason::Initial, $product);
        $this->service->increment($warehouse->id, $product->id, $lateBatch->id, 10.0, StockReason::Initial, $product);

        // Pick the earlier-expiry batch — should succeed
        $movement = $this->service->decrement($warehouse->id, $product->id, $earlyBatch->id, 5.0, StockReason::Sale, $product);
        $this->assertSame(-5.0, (float) $movement->quantity_change);
    }

    public function test_decrement_allows_later_batch_when_earlier_has_zero_stock(): void
    {
        [, $warehouse, $product] = $this->makeCompanyWarehouseProduct();

        $earlyBatch = Batch::factory()->create([
            'product_id' => $product->id,
            'expiry_date' => Carbon::today()->addDays(5),
        ]);
        $lateBatch = Batch::factory()->create([
            'product_id' => $product->id,
            'expiry_date' => Carbon::today()->addDays(30),
        ]);

        // Stock only the later batch
        $this->service->increment($warehouse->id, $product->id, $lateBatch->id, 10.0, StockReason::Initial, $product);

        // Pick later batch — should succeed since earlier has no stock
        $movement = $this->service->decrement($warehouse->id, $product->id, $lateBatch->id, 3.0, StockReason::Sale, $product);
        $this->assertSame(-3.0, (float) $movement->quantity_change);
    }

    public function test_increment_allows_any_active_batch(): void
    {
        [, $warehouse, $product] = $this->makeCompanyWarehouseProduct();
        $batch = Batch::factory()->create([
            'product_id' => $product->id,
            'expiry_date' => Carbon::today()->addDays(5),
        ]);

        $movement = $this->service->increment($warehouse->id, $product->id, $batch->id, 20.0, StockReason::Initial, $product);
        $this->assertSame(20.0, (float) $movement->quantity_change);
    }

    public function test_transfer_validates_batch_on_both_sides(): void
    {
        [$company, $warehouse] = $this->makeCompanyWarehouseProduct();
        $toWarehouse = Warehouse::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id]);

        // Stock the from-warehouse with no batch
        $this->service->increment($warehouse->id, $product->id, null, 100.0, StockReason::Initial, $product);

        // Transfer with invalid batch — should fail
        $this->expectException(DomainException::class);
        $this->service->transfer($warehouse->id, $toWarehouse->id, $product->id, 99999, 10.0, $product);
    }

    public function test_reconcile_validates_batch(): void
    {
        [, $warehouse, $product] = $this->makeCompanyWarehouseProduct();
        $user = User::factory()->create(['company_id' => $warehouse->company_id]);

        $this->expectException(DomainException::class);
        $this->service->reconcile($warehouse->id, $product->id, 99999, 10.0, 'count', $user->id);
    }
}
