<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Exceptions\Domain\DomainException;
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
 * Committed rep writes do not expose a generic Undo. Corrections are performed
 * by privileged, reasoned compensating transactions.
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
        $this->product = Product::where('sku', 'VIR-PP-H030')->firstOrFail();
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
                'unit_price' => $this->product->price,
            ]],
        ]);
    }

    public function test_rep_cannot_reverse_a_committed_sale(): void
    {
        $before = $this->vanStock();
        $invoice = $this->createSale();
        $this->assertSame($before - 2, $this->vanStock());

        $this->expectException(DomainException::class);
        try {
            app(InvoiceService::class)->cancel($invoice, $this->rep->id, 'Rep undo');
        } finally {
            $this->assertSame(InvoiceStatus::Issued, $invoice->fresh()->status);
            $this->assertNull($invoice->fresh()->cancelled_at);
            $this->assertSame($before - 2, $this->vanStock());
        }
    }

    public function test_completion_toast_contains_no_undo_control(): void
    {
        app()->setLocale('en');
        Livewire::test(ActionToast::class)
            ->call('show', 'Invoice created')
            ->assertSee('Invoice created')
            ->assertDontSee('Undo');
    }

    public function test_sales_flow_does_not_dispatch_committed_action_undo(): void
    {
        Livewire::test(SalesFlow::class)
            ->call('selectCustomer', $this->customer->id)
            ->call('addToCart', $this->product->id)
            ->call('submit')
            ->assertNotDispatched('action-completed');
    }
}
