<?php

declare(strict_types=1);

namespace Tests\Feature\EdgeCases;

use App\Enums\StockReason;
use App\Exceptions\Domain\DomainException;
use App\Exceptions\Domain\InsufficientStockException;
use App\Models\Batch;
use App\Models\Company;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use App\Support\ActiveCompanyContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * P0 edge-case integration tests: stock mutations, batch expiry, FEFO.
 *
 * ponytail: StockService operates on raw models — no HTTP layer needed.
 * All factories created BEFORE setCompanyId() to avoid BelongsToCompany lock.
 */
class StockMutationEdgeCaseTest extends TestCase
{
    use DatabaseTransactions;

    private Company $company;

    private User $warehouseKeeper;

    private Warehouse $warehouse;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->company = Company::factory()->create(['vat_percent' => 0]);
        $this->warehouseKeeper = User::factory()->create(['company_id' => $this->company->id]);
        $this->warehouseKeeper->assignRole('warehouse_keeper');

        $this->warehouse = Warehouse::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->warehouseKeeper->id,
            'type' => 'van',
        ]);

        $this->product = Product::factory()->create([
            'company_id' => $this->company->id,
            'price' => 50.00,
        ]);

        app(ActiveCompanyContext::class)->setCompanyId($this->company->id);
    }

    protected function tearDown(): void
    {
        app(ActiveCompanyContext::class)->disable();
        parent::tearDown();
    }

    /** Decrement below zero throws InsufficientStockException. */
    #[Test]
    public function test_decrement_below_zero_throws(): void
    {
        $this->seedStock(10);

        $this->expectException(InsufficientStockException::class);
        app(StockService::class)->decrement(
            $this->warehouse->id,
            $this->product->id,
            null,
            15,
            StockReason::Sale,
            $this->product,
            $this->warehouseKeeper->id,
        );
    }

    /** Decrement to exactly zero succeeds. */
    #[Test]
    public function test_decrement_to_zero_succeeds(): void
    {
        $this->seedStock(10);

        $movement = app(StockService::class)->decrement(
            $this->warehouse->id,
            $this->product->id,
            null,
            10,
            StockReason::Sale,
            $this->product,
            $this->warehouseKeeper->id,
        );

        $this->assertSame(-10.0, (float) $movement->quantity_change);
        $this->assertSame(0.0, (float) Stock::where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $this->product->id)
            ->sum('quantity'));
    }

    /** Increment creates stock record if none exists. */
    #[Test]
    public function test_increment_creates_stock_record(): void
    {
        $movement = app(StockService::class)->increment(
            $this->warehouse->id,
            $this->product->id,
            null,
            25,
            StockReason::Purchase,
            $this->product,
            $this->warehouseKeeper->id,
        );

        $this->assertSame(25.0, (float) $movement->quantity_change);
        $this->assertSame(25.0, (float) Stock::where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $this->product->id)
            ->sum('quantity'));
    }

    /** Reconcile rejects when stock changed since the expected-qty snapshot. */
    #[Test]
    public function test_reconcile_rejects_stale_snapshot(): void
    {
        $this->seedStock(10);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Stock changed after the count snapshot');
        app(StockService::class)->reconcile(
            $this->warehouse->id,
            $this->product->id,
            null,
            countedQty: 20,
            reason: 'cycle count',
            userId: $this->warehouseKeeper->id,
            expectedQty: 5.0, // current is 10, expected 5 → mismatch
        );
    }

    /** Reconcile with matching expected qty succeeds and creates adjustment movement. */
    #[Test]
    public function test_reconcile_matching_expected_succeeds(): void
    {
        $this->seedStock(10);

        $movement = app(StockService::class)->reconcile(
            $this->warehouse->id,
            $this->product->id,
            null,
            countedQty: 8,
            reason: 'cycle count',
            userId: $this->warehouseKeeper->id,
            expectedQty: 10.0,
        );

        $this->assertSame(StockReason::Adjustment, $movement->reason);
        $this->assertSame(-2.0, (float) $movement->quantity_change);
        $this->assertSame(8.0, (float) Stock::where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $this->product->id)
            ->sum('quantity'));
    }

    /** Reconcile without expected qty always succeeds. */
    #[Test]
    public function test_reconcile_without_expected_succeeds(): void
    {
        $this->seedStock(10);

        $movement = app(StockService::class)->reconcile(
            $this->warehouse->id,
            $this->product->id,
            null,
            countedQty: 50,
            reason: 'cycle count',
            userId: $this->warehouseKeeper->id,
            expectedQty: null,
        );

        $this->assertSame(40.0, (float) $movement->quantity_change);
    }

    /** Balance returns correct sum across multiple warehouses. */
    #[Test]
    public function test_balance_warehouse_scoped(): void
    {
        $warehouse2 = Warehouse::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->warehouseKeeper->id,
        ]);

        $this->seedStock(10);

        DB::table('stocks')->insert([
            'warehouse_id' => $warehouse2->id,
            'product_id' => $this->product->id,
            'batch_id' => null,
            'quantity' => 20,
        ]);

        $balance1 = app(StockService::class)->balance($this->warehouse->id, $this->product->id);
        $balance2 = app(StockService::class)->balance($warehouse2->id, $this->product->id);

        $this->assertSame(10.0, $balance1);
        $this->assertSame(20.0, $balance2);
    }

    /** Batch with past expiry date is rejected on decrement. */
    #[Test]
    public function test_expired_batch_rejected(): void
    {
        $batch = Batch::factory()->expired()->create([
            'product_id' => $this->product->id,
        ]);

        DB::table('stocks')->insert([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'batch_id' => $batch->id,
            'quantity' => 10,
        ]);

        $this->expectException(DomainException::class);
        app(StockService::class)->decrement(
            $this->warehouse->id,
            $this->product->id,
            $batch->id,
            5,
            StockReason::Sale,
            $this->product,
            $this->warehouseKeeper->id,
        );
    }

    /** FEFO: decrement from non-first-expiry batch when earlier batch has stock is rejected. */
    #[Test]
    public function test_fefo_violation_rejected(): void
    {
        // Batch A expires first, Batch B later
        $batchA = Batch::factory()->expiringInDays(30)->create([
            'product_id' => $this->product->id,
        ]);
        $batchB = Batch::factory()->expiringInDays(90)->create([
            'product_id' => $this->product->id,
        ]);

        // Both have stock
        DB::table('stocks')->insert([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'batch_id' => $batchA->id,
            'quantity' => 10,
        ]);
        DB::table('stocks')->insert([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'batch_id' => $batchB->id,
            'quantity' => 10,
        ]);

        // Try to decrement from B while A still has stock — FEFO violation
        $this->expectException(DomainException::class);
        app(StockService::class)->decrement(
            $this->warehouse->id,
            $this->product->id,
            $batchB->id,
            5,
            StockReason::Sale,
            $this->product,
            $this->warehouseKeeper->id,
        );
    }

    /** FEFO: decrement from later-expiry batch succeeds when earlier batch is empty. */
    #[Test]
    public function test_fefo_passes_when_earlier_batch_empty(): void
    {
        $batchA = Batch::factory()->expiringInDays(30)->create([
            'product_id' => $this->product->id,
        ]);
        $batchB = Batch::factory()->expiringInDays(90)->create([
            'product_id' => $this->product->id,
        ]);

        // Only batch B has stock
        DB::table('stocks')->insert([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'batch_id' => $batchB->id,
            'quantity' => 10,
        ]);

        $movement = app(StockService::class)->decrement(
            $this->warehouse->id,
            $this->product->id,
            $batchB->id,
            5,
            StockReason::Sale,
            $this->product,
            $this->warehouseKeeper->id,
        );

        $this->assertSame(-5.0, (float) $movement->quantity_change);
    }

    /** Transfer moves stock between warehouses atomically. */
    #[Test]
    public function test_transfer_moves_stock_atomically(): void
    {
        $warehouse2 = Warehouse::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->warehouseKeeper->id,
        ]);

        $this->seedStock(20);

        app(StockService::class)->transfer(
            $this->warehouse->id,
            $warehouse2->id,
            $this->product->id,
            null,
            8,
            $this->product,
            $this->warehouseKeeper->id,
        );

        $this->assertSame(12.0, (float) app(StockService::class)->balance($this->warehouse->id, $this->product->id));
        $this->assertSame(8.0, (float) app(StockService::class)->balance($warehouse2->id, $this->product->id));
    }

    /** Transfer that would over-decrement source rolls back both sides. */
    #[Test]
    public function test_transfer_over_decrement_rolls_back(): void
    {
        $warehouse2 = Warehouse::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->warehouseKeeper->id,
        ]);

        $this->seedStock(5);

        try {
            app(StockService::class)->transfer(
                $this->warehouse->id,
                $warehouse2->id,
                $this->product->id,
                null,
                10,
                $this->product,
                $this->warehouseKeeper->id,
            );
            $this->fail('Expected InsufficientStockException');
        } catch (InsufficientStockException) {
            // expected
        }

        // Source unchanged, destination empty
        $this->assertSame(5.0, (float) app(StockService::class)->balance($this->warehouse->id, $this->product->id));
        $this->assertSame(0.0, (float) app(StockService::class)->balance($warehouse2->id, $this->product->id));
    }

    /** Stock movements are recorded for audit trail. */
    #[Test]
    public function test_stock_movements_recorded(): void
    {
        $this->seedStock(30);

        app(StockService::class)->decrement(
            $this->warehouse->id,
            $this->product->id,
            null,
            12,
            StockReason::Sale,
            $this->product,
            $this->warehouseKeeper->id,
        );

        $movement = StockMovement::where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $this->product->id)
            ->first();

        $this->assertNotNull($movement);
        $this->assertSame(StockReason::Sale->value, $movement->reason);
        $this->assertSame(-12.0, (float) $movement->quantity_change);
        $this->assertSame($this->warehouseKeeper->id, $movement->user_id);
    }

    private function seedStock(float $quantity): void
    {
        DB::table('stocks')->insert([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'batch_id' => null,
            'quantity' => $quantity,
        ]);
    }
}
