<?php

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Models\GoodsInTransit;
use App\Models\GoodsInTransitItem;
use App\Models\LandedCost;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\GoodsInTransitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoodsInTransitServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_total_landed_cost_sums_all_costs(): void
    {
        $company = Company::factory()->create();
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $git = GoodsInTransit::create([
            'company_id' => $company->id,
            'supplier_id' => $supplier->id,
            'shipment_number' => 'SH-001',
            'status' => 'in_transit',
            'shipping_cost' => 1000,
            'customs_cost' => 500,
            'clearance_cost' => 200,
            'freight_cost' => 300,
            'posting_date' => now(),
        ]);

        $service = app(GoodsInTransitService::class);

        $this->assertSame(2000.0, $service->totalLandedCost($git));
    }

    public function test_total_landed_cost_includes_landed_costs_rows(): void
    {
        $company = Company::factory()->create();
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $git = GoodsInTransit::create([
            'company_id' => $company->id,
            'supplier_id' => $supplier->id,
            'shipment_number' => 'SH-002',
            'status' => 'in_transit',
            'shipping_cost' => 100,
            'customs_cost' => 0,
            'clearance_cost' => 0,
            'freight_cost' => 0,
            'posting_date' => now(),
        ]);
        LandedCost::create([
            'goods_in_transit_id' => $git->id,
            'type' => 'handling',
            'amount' => 50,
            'description' => 'Warehouse handling',
        ]);

        $service = app(GoodsInTransitService::class);

        $this->assertSame(150.0, $service->totalLandedCost($git));
    }

    public function test_allocate_distributes_cost_proportionally(): void
    {
        $company = Company::factory()->create();
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $product1 = Product::factory()->create(['company_id' => $company->id]);
        $product2 = Product::factory()->create(['company_id' => $company->id]);

        $git = GoodsInTransit::create([
            'company_id' => $company->id,
            'supplier_id' => $supplier->id,
            'shipment_number' => 'SH-003',
            'status' => 'in_transit',
            'shipping_cost' => 1000,
            'customs_cost' => 0,
            'clearance_cost' => 0,
            'freight_cost' => 0,
            'posting_date' => now(),
        ]);

        $item1 = GoodsInTransitItem::create([
            'goods_in_transit_id' => $git->id,
            'product_id' => $product1->id,
            'quantity' => 10,
            'unit_price' => 100,
        ]);
        $item2 = GoodsInTransitItem::create([
            'goods_in_transit_id' => $git->id,
            'product_id' => $product2->id,
            'quantity' => 5,
            'unit_price' => 100,
        ]);

        $allocation = app(GoodsInTransitService::class)->allocate($git);

        // Item1 line value = 10*100 = 1000 (2/3 of 1500), Item2 = 5*100 = 500 (1/3)
        $this->assertArrayHasKey($item1->id, $allocation);
        $this->assertArrayHasKey($item2->id, $allocation);
        $this->assertGreaterThan($allocation[$item2->id]['allocated'], $allocation[$item1->id]['allocated']);
    }

    public function test_allocate_handles_zero_landed_cost(): void
    {
        $company = Company::factory()->create();
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id]);

        $git = GoodsInTransit::create([
            'company_id' => $company->id,
            'supplier_id' => $supplier->id,
            'shipment_number' => 'SH-004',
            'status' => 'in_transit',
            'shipping_cost' => 0,
            'customs_cost' => 0,
            'clearance_cost' => 0,
            'freight_cost' => 0,
            'posting_date' => now(),
        ]);

        $item = GoodsInTransitItem::create([
            'goods_in_transit_id' => $git->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'unit_price' => 100,
        ]);

        $allocation = app(GoodsInTransitService::class)->allocate($git);

        $this->assertSame(0.0, $allocation[$item->id]['allocated']);
        $this->assertSame(100.0, $allocation[$item->id]['landed_unit_cost']);
    }
}
