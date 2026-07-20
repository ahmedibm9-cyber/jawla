<?php

namespace Tests\Feature;

use App\Enums\StockReason;
use App\Enums\VanTransferStatus;
use App\Livewire\App\VanTransfers;
use App\Models\Company;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Models\VanTransfer;
use App\Models\Warehouse;
use App\Services\Contracts\StockService;
use App\Services\Contracts\VanTransferService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VanTransferRepPageTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $fromRep;

    private User $toRep;

    private Product $product;

    private Warehouse $fromVan;

    private Warehouse $toVan;

    private Warehouse $inTransit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->company = Company::factory()->create();
        $this->fromRep = $this->makeRep();
        $this->toRep = $this->makeRep();
        $this->product = Product::factory()->create(['company_id' => $this->company->id, 'name_ar' => 'صنف']);

        $this->fromVan = Warehouse::factory()->create(['company_id' => $this->company->id, 'type' => 'van', 'user_id' => $this->fromRep->id]);
        $this->toVan = Warehouse::factory()->create(['company_id' => $this->company->id, 'type' => 'van', 'user_id' => $this->toRep->id]);
        $this->inTransit = Warehouse::factory()->create(['company_id' => $this->company->id, 'type' => 'main']);

        app(StockService::class)->increment($this->fromVan->id, $this->product->id, null, 50.0, StockReason::Initial, $this->product);
    }

    private function makeRep(): User
    {
        $u = User::factory()->create(['company_id' => $this->company->id]);
        $u->assignRole('rep');

        return $u;
    }

    private function shippedTransferToTarget(): VanTransfer
    {
        $svc = app(VanTransferService::class);
        $transfer = $svc->create(
            companyId: $this->company->id,
            fromUserId: $this->fromRep->id,
            toUserId: $this->toRep->id,
            items: [['product_id' => $this->product->id, 'quantity' => 12.0]],
            inTransitWarehouseId: $this->inTransit->id,
        );

        return $svc->ship($transfer->id, $this->fromVan->id, $this->fromRep->id);
    }

    public function test_incoming_shipped_transfer_is_listed_with_receive_action(): void
    {
        $this->shippedTransferToTarget();

        Livewire::actingAs($this->toRep)
            ->test(VanTransfers::class)
            ->assertOk()
            ->assertSee('صنف')
            ->assertSee(__('app.receive_transfer'));
    }

    public function test_rep_receives_transfer_and_stock_lands_in_their_van(): void
    {
        $transfer = $this->shippedTransferToTarget();

        Livewire::actingAs($this->toRep)
            ->test(VanTransfers::class)
            ->call('receive', $transfer->id)
            ->assertSet('successMessage', __('app.transfer_received'));

        $this->assertSame(VanTransferStatus::Received, $transfer->fresh()->status);

        $stock = Stock::where('warehouse_id', $this->toVan->id)->where('product_id', $this->product->id)->first();
        $this->assertNotNull($stock);
        $this->assertSame(12.0, (float) $stock->quantity);
    }

    public function test_rep_cannot_receive_a_transfer_addressed_to_someone_else(): void
    {
        $transfer = $this->shippedTransferToTarget();
        $stranger = $this->makeRep();

        Livewire::actingAs($stranger)
            ->test(VanTransfers::class)
            ->call('receive', $transfer->id)
            ->assertSet('errorMessage', __('app.transfer_not_found'));

        $this->assertSame(VanTransferStatus::Shipped, $transfer->fresh()->status);
    }
}
