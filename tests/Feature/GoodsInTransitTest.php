<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\GoodsInTransit;
use App\Models\GoodsInTransitItem;
use App\Models\LandedCost;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\GoodsInTransitService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoodsInTransitTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Warehouse $warehouse;

    private User $keeper;

    private Product $productA;

    private Product $productB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->company = Company::factory()->create();
        $this->warehouse = Warehouse::factory()->create(['company_id' => $this->company->id, 'type' => 'main']);
        $this->keeper = User::factory()->create(['company_id' => $this->company->id]);
        $this->keeper->assignRole('warehouse_keeper');
        $this->productA = Product::factory()->create(['company_id' => $this->company->id]);
        $this->productB = Product::factory()->create(['company_id' => $this->company->id]);
    }

    private function shipment(array $overrides = []): GoodsInTransit
    {
        return GoodsInTransit::create(array_merge([
            'company_id' => $this->company->id,
            'supplier_id' => Supplier::factory()->create(['company_id' => $this->company->id])->id,
            'shipment_number' => 'SHP-'.uniqid(),
            'status' => 'in_transit',
            'posting_date' => today(),
            'shipping_cost' => 0,
            'customs_cost' => 0,
            'clearance_cost' => 0,
            'freight_cost' => 0,
        ], $overrides));
    }

    private function service(): GoodsInTransitService
    {
        return app(GoodsInTransitService::class);
    }

    public function test_total_landed_cost_sums_columns_and_itemised_rows(): void
    {
        $git = $this->shipment([
            'shipping_cost' => 100, 'customs_cost' => 50, 'clearance_cost' => 25, 'freight_cost' => 25,
        ]);
        LandedCost::create(['goods_in_transit_id' => $git->id, 'cost_type' => 'insurance', 'amount' => 30]);

        $this->assertSame(230.0, $this->service()->totalLandedCost($git));
    }

    public function test_landed_cost_allocated_by_line_value(): void
    {
        // Total landed cost 300. Line A value = 10*100 = 1000; B = 10*300 = 3000.
        // A share 1/4 → 75; B share 3/4 → 225.
        $git = $this->shipment(['shipping_cost' => 300]);
        $a = GoodsInTransitItem::create(['goods_in_transit_id' => $git->id, 'product_id' => $this->productA->id, 'quantity' => 10, 'unit_price' => 100, 'currency' => 'USD']);
        $b = GoodsInTransitItem::create(['goods_in_transit_id' => $git->id, 'product_id' => $this->productB->id, 'quantity' => 10, 'unit_price' => 300, 'currency' => 'USD']);

        $alloc = $this->service()->allocate($git->fresh());

        $this->assertSame(75.0, $alloc[$a->id]['allocated']);
        $this->assertSame(225.0, $alloc[$b->id]['allocated']);
        // landed unit cost = unit_price + allocated/qty
        $this->assertSame(107.5, $alloc[$a->id]['landed_unit_cost']); // 100 + 75/10
        $this->assertSame(322.5, $alloc[$b->id]['landed_unit_cost']); // 300 + 225/10
    }

    public function test_receive_moves_stock_and_marks_received(): void
    {
        $git = $this->shipment();
        GoodsInTransitItem::create(['goods_in_transit_id' => $git->id, 'product_id' => $this->productA->id, 'quantity' => 40, 'unit_price' => 100, 'currency' => 'USD']);

        $this->service()->receive($git, $this->warehouse->id, $this->keeper->id);

        $this->assertSame('received', $git->fresh()->status);
        $stock = Stock::where('warehouse_id', $this->warehouse->id)->where('product_id', $this->productA->id)->first();
        $this->assertSame(40.0, (float) $stock->quantity);
        $this->assertSame(40.0, (float) $git->items()->first()->received_quantity);
        $this->assertDatabaseHas('stock_movements', ['reason' => 'transit_in', 'product_id' => $this->productA->id]);
    }

    public function test_receive_is_rejected_for_an_already_received_shipment(): void
    {
        $git = $this->shipment();
        GoodsInTransitItem::create(['goods_in_transit_id' => $git->id, 'product_id' => $this->productA->id, 'quantity' => 5, 'unit_price' => 10, 'currency' => 'USD']);
        $this->service()->receive($git, $this->warehouse->id, $this->keeper->id);

        $this->expectException(\RuntimeException::class);
        $this->service()->receive($git->fresh(), $this->warehouse->id, $this->keeper->id);
    }

    public function test_double_receive_does_not_double_count_stock(): void
    {
        $git = $this->shipment();
        GoodsInTransitItem::create(['goods_in_transit_id' => $git->id, 'product_id' => $this->productA->id, 'quantity' => 5, 'unit_price' => 10, 'currency' => 'USD']);
        $this->service()->receive($git, $this->warehouse->id, $this->keeper->id);

        try {
            $this->service()->receive($git->fresh(), $this->warehouse->id, $this->keeper->id);
        } catch (\RuntimeException) {
        }

        $this->assertSame(1, StockMovement::where('product_id', $this->productA->id)->where('reason', 'transit_in')->count());
        $this->assertSame(5.0, (float) Stock::where('warehouse_id', $this->warehouse->id)->where('product_id', $this->productA->id)->first()->quantity);
    }
}
