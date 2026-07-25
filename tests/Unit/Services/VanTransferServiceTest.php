<?php

namespace Tests\Unit\Services;

use App\Enums\StockReason;
use App\Enums\VanTransferStatus;
use App\Exceptions\Domain\DomainException;
use App\Exceptions\Domain\InsufficientStockException;
use App\Models\Company;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\VanTransfer;
use App\Models\Warehouse;
use App\Services\Contracts\StockService;
use App\Services\Contracts\VanTransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VanTransferServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_van_transfer_with_items(): void
    {
        $company = Company::factory()->create();
        $fromUser = User::factory()->create(['company_id' => $company->id]);
        $toUser = User::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id]);

        $service = app(VanTransferService::class);

        $transfer = $service->create(
            companyId: $company->id,
            fromUserId: $fromUser->id,
            toUserId: $toUser->id,
            items: [['product_id' => $product->id, 'quantity' => 10.0]],
        );

        $this->assertInstanceOf(VanTransfer::class, $transfer);
        $this->assertSame(VanTransferStatus::Pending, $transfer->status);
        $this->assertSame($fromUser->id, $transfer->from_user_id);
        $this->assertSame($toUser->id, $transfer->to_user_id);
        $this->assertCount(1, $transfer->items);
        $this->assertSame(10.0, (float) $transfer->items->first()->quantity);
        $this->assertSame($product->id, $transfer->items->first()->product_id);
    }

    public function test_ship_van_transfer_decrements_stock(): void
    {
        $company = Company::factory()->create();
        $fromUser = User::factory()->create(['company_id' => $company->id]);
        $toUser = User::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id]);
        $mainWarehouse = Warehouse::factory()->create([
            'company_id' => $company->id, 'type' => 'main',
        ]);
        $inTransitWarehouse = Warehouse::factory()->create([
            'company_id' => $company->id, 'type' => 'main',
        ]);

        $service = app(VanTransferService::class);

        // Seed stock in main warehouse
        app(StockService::class)->increment(
            $mainWarehouse->id, $product->id, null, 50.0,
            StockReason::Initial, $product,
        );

        $transfer = $service->create(
            companyId: $company->id,
            fromUserId: $fromUser->id,
            toUserId: $toUser->id,
            items: [['product_id' => $product->id, 'quantity' => 10.0]],
            inTransitWarehouseId: $inTransitWarehouse->id,
        );

        $shipped = $service->ship($transfer->id, $mainWarehouse->id, $fromUser->id);

        $this->assertSame(VanTransferStatus::Shipped, $shipped->status);
        $this->assertNotNull($shipped->shipped_at);
        $this->assertSame(40.0, (float) app(StockService::class)->balance($mainWarehouse->id, $product->id));
        $this->assertSame(10.0, (float) app(StockService::class)->balance($inTransitWarehouse->id, $product->id));
        $this->assertDatabaseHas('stock_movements', [
            'warehouse_id' => $mainWarehouse->id,
            'product_id' => $product->id,
            'quantity_change' => -10.0,
        ]);
    }

    public function test_receive_van_transfer_moves_stock_to_van(): void
    {
        $company = Company::factory()->create();
        $fromUser = User::factory()->create(['company_id' => $company->id]);
        $toUser = User::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id]);
        $mainWarehouse = Warehouse::factory()->create([
            'company_id' => $company->id, 'type' => 'main',
        ]);
        $inTransitWarehouse = Warehouse::factory()->create([
            'company_id' => $company->id, 'type' => 'main',
        ]);
        $vanWarehouse = Warehouse::factory()->create([
            'company_id' => $company->id, 'type' => 'van', 'user_id' => $toUser->id,
        ]);

        $service = app(VanTransferService::class);

        app(StockService::class)->increment(
            $mainWarehouse->id, $product->id, null, 50.0,
            StockReason::Initial, $product,
        );

        $transfer = $service->create(
            companyId: $company->id,
            fromUserId: $fromUser->id,
            toUserId: $toUser->id,
            items: [['product_id' => $product->id, 'quantity' => 10.0]],
            inTransitWarehouseId: $inTransitWarehouse->id,
        );

        $service->ship($transfer->id, $mainWarehouse->id, $fromUser->id);
        $received = $service->receive($transfer->id, $toUser->id);

        $this->assertSame(VanTransferStatus::Received, $received->status);
        $this->assertNotNull($received->received_at);
        $this->assertSame(40.0, (float) app(StockService::class)->balance($mainWarehouse->id, $product->id));
        $this->assertSame(0.0, (float) app(StockService::class)->balance($inTransitWarehouse->id, $product->id));
        $this->assertSame(10.0, (float) app(StockService::class)->balance($vanWarehouse->id, $product->id));
    }

    public function test_reject_pending_transfer(): void
    {
        $company = Company::factory()->create();
        $fromUser = User::factory()->create(['company_id' => $company->id]);
        $toUser = User::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id]);

        $service = app(VanTransferService::class);

        $transfer = $service->create(
            companyId: $company->id,
            fromUserId: $fromUser->id,
            toUserId: $toUser->id,
            items: [['product_id' => $product->id, 'quantity' => 10.0]],
        );

        $rejected = $service->reject($transfer->id, $toUser->id);

        $this->assertSame(VanTransferStatus::Rejected, $rejected->status);
    }

    public function test_cancel_pending_transfer(): void
    {
        $company = Company::factory()->create();
        $fromUser = User::factory()->create(['company_id' => $company->id]);
        $toUser = User::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id]);

        $service = app(VanTransferService::class);

        $transfer = $service->create(
            companyId: $company->id,
            fromUserId: $fromUser->id,
            toUserId: $toUser->id,
            items: [['product_id' => $product->id, 'quantity' => 10.0]],
        );

        $cancelled = $service->cancel($transfer->id, $fromUser->id);

        $this->assertSame(VanTransferStatus::Cancelled, $cancelled->status);
    }

    public function test_cannot_ship_without_stock(): void
    {
        $company = Company::factory()->create();
        $fromUser = User::factory()->create(['company_id' => $company->id]);
        $toUser = User::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id]);
        $mainWarehouse = Warehouse::factory()->create([
            'company_id' => $company->id, 'type' => 'main',
        ]);
        $inTransitWarehouse = Warehouse::factory()->create([
            'company_id' => $company->id, 'type' => 'main',
        ]);

        $service = app(VanTransferService::class);

        $transfer = $service->create(
            companyId: $company->id,
            fromUserId: $fromUser->id,
            toUserId: $toUser->id,
            items: [['product_id' => $product->id, 'quantity' => 10.0]],
            inTransitWarehouseId: $inTransitWarehouse->id,
        );

        $this->expectException(InsufficientStockException::class);
        $service->ship($transfer->id, $mainWarehouse->id, $fromUser->id);
    }

    public function test_ship_creates_no_partial_state_on_failure(): void
    {
        $company = Company::factory()->create();
        $fromUser = User::factory()->create(['company_id' => $company->id]);
        $toUser = User::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id]);
        $product2 = Product::factory()->create(['company_id' => $company->id]);
        $mainWarehouse = Warehouse::factory()->create([
            'company_id' => $company->id, 'type' => 'main',
        ]);
        $inTransitWarehouse = Warehouse::factory()->create([
            'company_id' => $company->id, 'type' => 'main',
        ]);

        $service = app(VanTransferService::class);

        // Seed stock for product but NOT product2
        app(StockService::class)->increment(
            $mainWarehouse->id, $product->id, null, 50.0,
            StockReason::Initial, $product,
        );

        $transfer = $service->create(
            companyId: $company->id,
            fromUserId: $fromUser->id,
            toUserId: $toUser->id,
            items: [
                ['product_id' => $product->id, 'quantity' => 10.0],
                ['product_id' => $product2->id, 'quantity' => 5.0],
            ],
            inTransitWarehouseId: $inTransitWarehouse->id,
        );

        try {
            $service->ship($transfer->id, $mainWarehouse->id, $fromUser->id);
        } catch (InsufficientStockException $e) {
        }

        $this->assertSame(50.0, (float) app(StockService::class)->balance($mainWarehouse->id, $product->id));
        $this->assertSame(0, StockMovement::where('warehouse_id', $inTransitWarehouse->id)->count());
        $this->assertSame(VanTransferStatus::Pending, $transfer->fresh()->status);
    }

    public function test_cannot_ship_already_shipped_transfer(): void
    {
        $company = Company::factory()->create();
        $fromUser = User::factory()->create(['company_id' => $company->id]);
        $toUser = User::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id]);
        $mainWarehouse = Warehouse::factory()->create([
            'company_id' => $company->id, 'type' => 'main',
        ]);
        $inTransitWarehouse = Warehouse::factory()->create([
            'company_id' => $company->id, 'type' => 'main',
        ]);

        $service = app(VanTransferService::class);

        app(StockService::class)->increment(
            $mainWarehouse->id, $product->id, null, 50.0,
            StockReason::Initial, $product,
        );

        $transfer = $service->create(
            companyId: $company->id,
            fromUserId: $fromUser->id,
            toUserId: $toUser->id,
            items: [['product_id' => $product->id, 'quantity' => 10.0]],
            inTransitWarehouseId: $inTransitWarehouse->id,
        );

        $service->ship($transfer->id, $mainWarehouse->id, $fromUser->id);

        $this->expectException(\RuntimeException::class);
        $service->ship($transfer->id, $mainWarehouse->id, $fromUser->id);
    }

    public function test_cannot_ship_by_non_source_representative(): void
    {
        $company = Company::factory()->create();
        $fromUser = User::factory()->create(['company_id' => $company->id]);
        $toUser = User::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id]);
        $mainWarehouse = Warehouse::factory()->create([
            'company_id' => $company->id, 'type' => 'main',
        ]);
        $inTransitWarehouse = Warehouse::factory()->create([
            'company_id' => $company->id, 'type' => 'main',
        ]);

        $service = app(VanTransferService::class);

        app(StockService::class)->increment(
            $mainWarehouse->id, $product->id, null, 50.0,
            StockReason::Initial, $product,
        );

        $transfer = $service->create(
            companyId: $company->id,
            fromUserId: $fromUser->id,
            toUserId: $toUser->id,
            items: [['product_id' => $product->id, 'quantity' => 10.0]],
            inTransitWarehouseId: $inTransitWarehouse->id,
        );

        $this->expectException(DomainException::class);
        $service->ship($transfer->id, $mainWarehouse->id, $toUser->id);
    }

    public function test_cannot_receive_pending_transfer(): void
    {
        $company = Company::factory()->create();
        $fromUser = User::factory()->create(['company_id' => $company->id]);
        $toUser = User::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id]);

        $service = app(VanTransferService::class);

        $transfer = $service->create(
            companyId: $company->id,
            fromUserId: $fromUser->id,
            toUserId: $toUser->id,
            items: [['product_id' => $product->id, 'quantity' => 10.0]],
        );

        $this->expectException(\RuntimeException::class);
        $service->receive($transfer->id, $toUser->id);
    }

    public function test_cannot_receive_by_non_destination_representative(): void
    {
        $company = Company::factory()->create();
        $fromUser = User::factory()->create(['company_id' => $company->id]);
        $toUser = User::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id]);
        $mainWarehouse = Warehouse::factory()->create([
            'company_id' => $company->id, 'type' => 'main',
        ]);
        $inTransitWarehouse = Warehouse::factory()->create([
            'company_id' => $company->id, 'type' => 'main',
        ]);

        $service = app(VanTransferService::class);

        app(StockService::class)->increment(
            $mainWarehouse->id, $product->id, null, 50.0,
            StockReason::Initial, $product,
        );

        $transfer = $service->create(
            companyId: $company->id,
            fromUserId: $fromUser->id,
            toUserId: $toUser->id,
            items: [['product_id' => $product->id, 'quantity' => 10.0]],
            inTransitWarehouseId: $inTransitWarehouse->id,
        );

        $service->ship($transfer->id, $mainWarehouse->id, $fromUser->id);

        $this->expectException(DomainException::class);
        $service->receive($transfer->id, $fromUser->id);
    }

    public function test_cannot_cancel_shipped_transfer(): void
    {
        $company = Company::factory()->create();
        $fromUser = User::factory()->create(['company_id' => $company->id]);
        $toUser = User::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id]);
        $mainWarehouse = Warehouse::factory()->create([
            'company_id' => $company->id, 'type' => 'main',
        ]);
        $inTransitWarehouse = Warehouse::factory()->create([
            'company_id' => $company->id, 'type' => 'main',
        ]);

        $service = app(VanTransferService::class);

        app(StockService::class)->increment(
            $mainWarehouse->id, $product->id, null, 50.0,
            StockReason::Initial, $product,
        );

        $transfer = $service->create(
            companyId: $company->id,
            fromUserId: $fromUser->id,
            toUserId: $toUser->id,
            items: [['product_id' => $product->id, 'quantity' => 10.0]],
            inTransitWarehouseId: $inTransitWarehouse->id,
        );

        $service->ship($transfer->id, $mainWarehouse->id, $fromUser->id);

        $this->expectException(\RuntimeException::class);
        $service->cancel($transfer->id, $fromUser->id);
    }

    public function test_cannot_cancel_by_non_source_representative(): void
    {
        $company = Company::factory()->create();
        $fromUser = User::factory()->create(['company_id' => $company->id]);
        $toUser = User::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id]);

        $service = app(VanTransferService::class);

        $transfer = $service->create(
            companyId: $company->id,
            fromUserId: $fromUser->id,
            toUserId: $toUser->id,
            items: [['product_id' => $product->id, 'quantity' => 10.0]],
        );

        $this->expectException(DomainException::class);
        $service->cancel($transfer->id, $toUser->id);
    }
}
