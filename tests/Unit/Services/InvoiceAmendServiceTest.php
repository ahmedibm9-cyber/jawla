<?php

namespace Tests\Unit\Services;

use App\Enums\InvoiceStatus;
use App\Enums\StockReason;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Contracts\StockService;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceAmendServiceTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $rep;
    private Warehouse $van;
    private Customer $customer;
    private Product $product;
    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->rep = User::factory()->create(['company_id' => $this->company->id]);
        $this->van = Warehouse::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'van',
            'user_id' => $this->rep->id,
        ]);
        $this->customer = Customer::factory()->create(['company_id' => $this->company->id, 'balance' => 0]);
        $this->product = Product::factory()->create(['company_id' => $this->company->id]);

        app(StockService::class)->increment(
            $this->van->id, $this->product->id, null, 100.0,
            StockReason::Initial, $this->product,
        );

        $this->actingAs($this->rep);
        $this->invoice = app(InvoiceService::class)->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 10, 'unit_price' => 500]],
        ]);
    }

    public function test_amend_cancels_original_invoice(): void
    {
        $originalId = $this->invoice->id;

        $draft = app(InvoiceService::class)->amend($this->invoice);

        $this->assertNotEquals($originalId, $draft->id);
        $this->assertEquals(InvoiceStatus::Cancelled, $this->invoice->fresh()->status);
        $this->assertNotNull($this->invoice->fresh()->cancelled_at);
    }

    public function test_amend_creates_new_draft_with_amended_from_link(): void
    {
        $draft = app(InvoiceService::class)->amend($this->invoice);

        $this->assertEquals(InvoiceStatus::Draft, $draft->status);
        $this->assertEquals($this->invoice->id, $draft->amended_from);
    }

    public function test_amend_copies_all_line_items(): void
    {
        $originalItemCount = $this->invoice->items()->count();

        $draft = app(InvoiceService::class)->amend($this->invoice);

        $this->assertEquals($originalItemCount, $draft->items()->count());
        $this->assertEquals(
            $this->invoice->items()->pluck('product_id')->sort()->values()->toArray(),
            $draft->items()->pluck('product_id')->sort()->values()->toArray(),
        );
    }

    public function test_amend_preserves_financial_totals(): void
    {
        $draft = app(InvoiceService::class)->amend($this->invoice);

        $this->assertEquals($this->invoice->subtotal, $draft->subtotal);
        $this->assertEquals($this->invoice->vat_amount, $draft->vat_amount);
        $this->assertEquals($this->invoice->total, $draft->total);
        $this->assertEquals(0, $draft->paid_amount);
        $this->assertEquals($draft->total, $draft->remaining_amount);
    }

    public function test_amend_reverses_van_stock(): void
    {
        $stockBefore = app(StockService::class)->balance($this->van->id, $this->product->id);

        app(InvoiceService::class)->amend($this->invoice);

        $stockAfter = app(StockService::class)->balance($this->van->id, $this->product->id);
        $this->assertEquals($stockBefore + 10, $stockAfter);
    }
}
