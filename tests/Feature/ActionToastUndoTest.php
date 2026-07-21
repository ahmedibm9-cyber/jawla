<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Livewire\App\ActionToast;
use App\Livewire\App\SalesFlow;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InvoiceService;
use App\Support\ActiveCompanyContext;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Phase 6 / B1 — the global undo toast reverses a just-created rep write through
 * the existing transactional cancel() service (compensating transaction, not a
 * delete), guarded by ownership and not-already-cancelled.
 */
class ActionToastUndoTest extends TestCase
{
    use RefreshDatabase;

    private User $rep;

    private Customer $customer;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
        $this->rep = User::where('email', 'rep@jawla.test')->firstOrFail();
        $this->customer = Customer::where('status', 'approved')->firstOrFail();
        $this->product = Product::where('sku', 'PP-H030')->firstOrFail();
        $this->actingAs($this->rep);
        app(ActiveCompanyContext::class)->setFromUser($this->rep);
    }

    protected function tearDown(): void
    {
        app(ActiveCompanyContext::class)->disable();
        parent::tearDown();
    }

    private function vanStock(): float
    {
        $van = Warehouse::where('user_id', $this->rep->id)->where('type', 'van')->firstOrFail();

        return (float) Stock::where('warehouse_id', $van->id)
            ->where('product_id', $this->product->id)->value('quantity');
    }

    private function createSale(): Invoice
    {
        return app(InvoiceService::class)->create([
            'company_id' => $this->rep->company_id,
            'customer_id' => $this->customer->id,
            'items' => [[
                'product_id' => $this->product->id,
                'quantity' => 2,
                'unit_price' => 10,
            ]],
        ]);
    }

    public function test_undo_reverses_a_sale_and_restores_stock(): void
    {
        $before = $this->vanStock();
        $invoice = $this->createSale();
        $this->assertSame($before - 2, $this->vanStock());

        Livewire::test(ActionToast::class)
            ->call('show', 'sale', $invoice->id, 'Invoice created')
            ->assertSet('type', 'sale')
            ->call('undo')
            ->assertSet('undone', true)
            ->assertSet('type', null);

        $this->assertSame(InvoiceStatus::Cancelled, $invoice->fresh()->status);
        $this->assertNotNull($invoice->fresh()->cancelled_at);
        // Stock is restored by the compensating transaction.
        $this->assertSame($before, $this->vanStock());
    }

    public function test_undo_refuses_another_reps_record(): void
    {
        $invoice = $this->createSale();
        $other = User::factory()->create([
            'company_id' => $this->rep->company_id,
        ]);
        $this->actingAs($other);
        app(ActiveCompanyContext::class)->setFromUser($other);

        Livewire::test(ActionToast::class)
            ->call('show', 'sale', $invoice->id, 'Invoice created')
            ->call('undo')
            ->assertSet('undone', false)
            ->assertSet('error', fn ($v) => $v !== '');

        // The original rep's invoice is untouched.
        $this->assertNotSame(InvoiceStatus::Cancelled, $invoice->fresh()->status);
    }

    public function test_sales_flow_dispatches_action_completed_on_submit(): void
    {
        Livewire::test(SalesFlow::class)
            ->call('selectCustomer', $this->customer->id)
            ->call('addToCart', $this->product->id)
            ->call('submit')
            ->assertDispatched('action-completed', type: 'sale');
    }
}
