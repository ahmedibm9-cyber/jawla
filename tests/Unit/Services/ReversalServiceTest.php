<?php

namespace Tests\Unit\Services;

use App\Enums\InvoiceStatus;
use App\Enums\StockReason;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Contracts\StockService;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\ReversalService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReversalServiceTest extends TestCase
{
    use DatabaseTransactions;

    private Company $company;

    private User $rep;

    private User $admin;

    private Warehouse $van;

    private Customer $customer;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->company = Company::factory()->create();
        $this->rep = User::factory()->create(['company_id' => $this->company->id]);
        $this->rep->assignRole('sales_rep');
        $this->admin = User::factory()->create(['company_id' => $this->company->id]);
        $this->admin->assignRole('sales_manager');
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

    public function test_reverse_invoice_restores_stock(): void
    {
        $this->product->update(['price' => 500]);
        $this->actingAs($this->rep);
        $invoice = app(InvoiceService::class)->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 10, 'unit_price' => 500]],
        ]);

        $stockBefore = app(StockService::class)->balance($this->van->id, $this->product->id);

        $this->actingAs($this->admin);
        $reversed = app(ReversalService::class)->reverseInvoice($invoice, 'Admin reversal');

        $stockAfter = app(StockService::class)->balance($this->van->id, $this->product->id);
        $this->assertGreaterThan($stockBefore, $stockAfter);
        $this->assertEquals(InvoiceStatus::Voided, $reversed->fresh()->status);
    }

    public function test_reverse_payment_restores_invoice_balance(): void
    {
        $this->product->update(['price' => 200]);
        $this->actingAs($this->rep);
        $invoice = app(InvoiceService::class)->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 5, 'unit_price' => 200]],
        ]);

        $paidAmount = $invoice->total;

        // Collect full payment
        app(PaymentService::class)->collect(
            customerId: $this->customer->id,
            invoiceId: $invoice->id,
            amount: $paidAmount,
            method: 'cash',
            userId: $this->rep->id,
            companyId: $this->company->id,
        );

        $this->assertEquals($paidAmount, $invoice->fresh()->paid_amount);

        // Reverse payment
        $this->actingAs($this->admin);
        app(ReversalService::class)->reversePayment(
            $invoice->payments()->first(),
            'Admin reversal',
        );

        $this->assertEquals(0, $invoice->fresh()->paid_amount);
        $this->assertEquals($paidAmount, $invoice->fresh()->remaining_amount);
    }

    public function test_reverse_invoice_restores_customer_balance(): void
    {
        $this->product->update(['price' => 100]);
        $this->customer->update(['balance' => 1000]);
        $balanceBefore = $this->customer->fresh()->balance;

        $this->actingAs($this->rep);
        $invoice = app(InvoiceService::class)->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 5, 'unit_price' => 100]],
        ]);

        // After sale, customer balance should have changed
        $this->assertNotEquals($balanceBefore, $this->customer->fresh()->balance);

        $this->actingAs($this->admin);
        app(ReversalService::class)->reverseInvoice($invoice, 'Test reversal');

        // After reversal, customer balance should be restored to original
        $this->assertEquals($balanceBefore, $this->customer->fresh()->balance);
    }
}
