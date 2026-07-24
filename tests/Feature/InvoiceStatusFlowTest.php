<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\StockReason;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Contracts\StockService;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * US-5.1 — Invoice Status State Machine
 *
 * Tests all transitions: Draft → Submitted → PartiallyPaid → Paid,
 * plus cancel and amend for all statuses.
 */
class InvoiceStatusFlowTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $rep;
    private Warehouse $van;
    private Customer $customer;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->rep = User::factory()->create(['company_id' => $this->company->id]);
        $this->van = Warehouse::factory()->create([
            'company_id' => $this->company->id, 'type' => 'van', 'user_id' => $this->rep->id,
        ]);
        $this->customer = Customer::factory()->create(['company_id' => $this->company->id, 'balance' => 0]);
        $this->product = Product::factory()->create(['company_id' => $this->company->id]);

        app(StockService::class)->increment(
            $this->van->id, $this->product->id, null, 100.0,
            StockReason::Initial, $this->product,
        );
    }

    public function test_create_creates_submitted(): void
    {
        $this->actingAs($this->rep);
        $invoice = app(InvoiceService::class)->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 5, 'unit_price' => 100]],
        ]);

        $this->assertEquals(InvoiceStatus::Submitted, $invoice->status);
    }

    public function test_amend_creates_draft(): void
    {
        $this->actingAs($this->rep);
        $invoice = app(InvoiceService::class)->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 5, 'unit_price' => 100]],
        ]);

        $draft = app(InvoiceService::class)->amend($invoice);

        $this->assertEquals(InvoiceStatus::Draft, $draft->status);
        $this->assertEquals(InvoiceStatus::Cancelled, $invoice->fresh()->status);
    }

    public function test_submit_transitions_draft_to_submitted(): void
    {
        $this->actingAs($this->rep);
        $invoice = app(InvoiceService::class)->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 5, 'unit_price' => 100]],
        ]);
        $draft = app(InvoiceService::class)->amend($invoice);

        $submitted = app(InvoiceService::class)->submit($draft);

        $this->assertEquals(InvoiceStatus::Submitted, $submitted->status);
        $this->assertNotNull($submitted->issued_at);
    }

    public function test_submit_rejects_non_draft(): void
    {
        $this->actingAs($this->rep);
        $invoice = app(InvoiceService::class)->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 5, 'unit_price' => 100]],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Only draft invoices can be submitted');
        app(InvoiceService::class)->submit($invoice);
    }

    public function test_cancel_transitions_to_cancelled(): void
    {
        $this->actingAs($this->rep);
        $invoice = app(InvoiceService::class)->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 5, 'unit_price' => 100]],
        ]);

        $results = app(InvoiceService::class)->cancel($invoice, $this->rep->id, 'Test cancel');

        $this->assertEquals(InvoiceStatus::Cancelled, $invoice->fresh()->status);
        $this->assertNotNull($invoice->fresh()->cancelled_at);
        $this->assertNotNull($results->id);
    }

    public function test_cancelling_already_cancelled_is_idempotent(): void
    {
        $this->actingAs($this->rep);
        $invoice = app(InvoiceService::class)->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 5, 'unit_price' => 100]],
        ]);

        app(InvoiceService::class)->cancel($invoice, $this->rep->id, 'First cancel');
        app(InvoiceService::class)->cancel($invoice, $this->rep->id, 'Second cancel');

        $this->assertEquals(InvoiceStatus::Cancelled, $invoice->fresh()->status);
    }

    public function test_full_payment_transitions_submitted_to_paid(): void
    {
        $this->actingAs($this->rep);
        $invoice = app(InvoiceService::class)->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 5, 'unit_price' => 100]],
        ]);

        app(PaymentService::class)->collect(
            customerId: $this->customer->id,
            invoiceId: $invoice->id,
            amount: $invoice->total,
            method: 'cash',
            userId: $this->rep->id,
            companyId: $this->company->id,
        );

        $this->assertEquals(InvoiceStatus::Paid, $invoice->fresh()->status);
        $this->assertEquals(0.0, (float) $invoice->fresh()->remaining_amount);
    }

    public function test_partial_payment_transitions_submitted_to_partially_paid(): void
    {
        $this->actingAs($this->rep);
        $invoice = app(InvoiceService::class)->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 10, 'unit_price' => 100]],
        ]);

        app(PaymentService::class)->collect(
            customerId: $this->customer->id,
            invoiceId: $invoice->id,
            amount: $invoice->total / 2,
            method: 'cash',
            userId: $this->rep->id,
            companyId: $this->company->id,
        );

        $this->assertEquals(InvoiceStatus::PartiallyPaid, $invoice->fresh()->status);
        $this->assertGreaterThan(0, (float) $invoice->fresh()->remaining_amount);
    }

    public function test_payment_reversal_transitions_paid_to_partially_paid(): void
    {
        $this->actingAs($this->rep);
        $invoice = app(InvoiceService::class)->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 5, 'unit_price' => 100]],
        ]);

        app(PaymentService::class)->collect(
            customerId: $this->customer->id,
            invoiceId: $invoice->id,
            amount: $invoice->total,
            method: 'cash',
            userId: $this->rep->id,
            companyId: $this->company->id,
        );

        $this->assertEquals(InvoiceStatus::Paid, $invoice->fresh()->status);

        $payment = $invoice->fresh()->payments()->first();
        app(PaymentService::class)->cancel($payment, $this->rep->id, 'Reversal test');

        $this->assertEquals(InvoiceStatus::PartiallyPaid, $invoice->fresh()->status);
    }
}
