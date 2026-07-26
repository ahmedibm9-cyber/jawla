<?php

namespace Tests\Feature\Inventory;

use App\Exceptions\Domain\DomainException;
use App\Models\Company;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockCountService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockCountIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_count_applies_only_the_locked_delta(): void
    {
        $this->seed(RoleSeeder::class);
        $company = Company::factory()->create();
        $keeper = User::factory()->create(['company_id' => $company->id]);
        $keeper->assignRole('warehouse_keeper');
        $manager = User::factory()->create(['company_id' => $company->id]);
        $manager->assignRole('sales_manager');
        $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id]);
        Stock::create(['warehouse_id' => $warehouse->id, 'product_id' => $product->id, 'quantity' => 10]);

        $service = app(StockCountService::class);
        $session = $service->open($warehouse, $keeper, [$product->id]);
        $service->record($session, $session->items->first()->id, '12.500', $keeper);
        $service->submit($session, $keeper, 'Cycle count variance');
        $applied = $service->approveAndApply($session, $manager);

        $this->assertSame('applied', $applied->status);
        $this->assertSame('12.500', Stock::where('warehouse_id', $warehouse->id)->value('quantity'));
        $this->assertDatabaseHas('stock_movements', [
            'reference_type' => $session->getMorphClass(),
            'reference_id' => $session->id,
            'quantity_change' => '2.500',
        ]);
    }

    public function test_intervening_movement_rejects_the_entire_count(): void
    {
        $this->seed(RoleSeeder::class);
        $company = Company::factory()->create();
        $keeper = User::factory()->create(['company_id' => $company->id]);
        $keeper->assignRole('warehouse_keeper');
        $manager = User::factory()->create(['company_id' => $company->id]);
        $manager->assignRole('sales_manager');
        $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id]);
        Stock::create(['warehouse_id' => $warehouse->id, 'product_id' => $product->id, 'quantity' => 10]);

        $service = app(StockCountService::class);
        $session = $service->open($warehouse, $keeper, [$product->id]);
        $service->record($session, $session->items->first()->id, '12.000', $keeper);
        $service->submit($session, $keeper, 'Counted before delivery');
        Stock::where('warehouse_id', $warehouse->id)->update(['quantity' => 11]);

        $this->expectException(DomainException::class);
        try {
            $service->approveAndApply($session, $manager);
        } finally {
            $this->assertSame('11.000', Stock::where('warehouse_id', $warehouse->id)->value('quantity'));
            $this->assertSame('pending_approval', $session->fresh()->status);
            $this->assertDatabaseCount('stock_movements', 0);
        }
    }
}
