<?php

namespace Tests\Unit\Services;

use App\Enums\InvoiceStatus;
use App\Enums\StockReason;
use App\Exceptions\Domain\DomainException;
use App\Models\CashBox;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Contracts\StockService;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function salesRep(Company $company): User
    {
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');

        return $rep;
    }

    public function test_collect_payment_creates_payment(): void
    {
        $company = Company::factory()->create();
        $rep = $this->salesRep($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $payment = app(PaymentService::class)->collect(
            companyId: $company->id,
            userId: $rep->id,
            customerId: $customer->id,
            amount: 100.0,
            method: 'cash',
        );

        $this->assertSame(100.0, (float) $payment->amount);
        $this->assertSame('cash', $payment->method);
        $this->assertNotNull($payment->collected_at);
    }

    public function test_collect_payment_credits_cashbox(): void
    {
        $company = Company::factory()->create();
        $rep = $this->salesRep($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        app(PaymentService::class)->collect(
            companyId: $company->id,
            userId: $rep->id,
            customerId: $customer->id,
            amount: 250.0,
            method: 'cash',
        );

        $cashBox = CashBox::where('user_id', $rep->id)->first();
        $this->assertNotNull($cashBox);
        $this->assertSame(250.0, (float) $cashBox->balance);
    }

    public function test_collect_payment_rejects_a_customer_from_another_company_before_any_write(): void
    {
        $company = Company::factory()->create();
        $rep = $this->salesRep($company);
        $foreignCustomer = Customer::factory()->create(['company_id' => Company::factory()->create()->id]);

        $this->expectException(DomainException::class);
        try {
            app(PaymentService::class)->collect(
                companyId: $company->id,
                userId: $rep->id,
                customerId: $foreignCustomer->id,
                amount: 100,
                method: 'cash',
            );
        } finally {
            $this->assertDatabaseCount('payments', 0);
            $this->assertDatabaseCount('cash_boxes', 0);
        }
    }

    public function test_collect_payment_adopts_a_legacy_cashbox_instead_of_creating_a_second_one(): void
    {
        $company = Company::factory()->create();
        $rep = $this->salesRep($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $legacyCashBox = CashBox::withoutGlobalScopes()->create([
            'company_id' => null,
            'user_id' => $rep->id,
            'balance' => 0,
        ]);

        app(PaymentService::class)->collect(
            companyId: $company->id,
            userId: $rep->id,
            customerId: $customer->id,
            amount: 250.0,
            method: 'cash',
        );

        $this->assertDatabaseCount('cash_boxes', 1);
        $this->assertSame($company->id, $legacyCashBox->fresh()->company_id);
        $this->assertSame(250.0, (float) $legacyCashBox->fresh()->balance);
    }

    public function test_collect_payment_updates_invoice_status_to_paid(): void
    {
        $company = Company::factory()->create();
        $rep = $this->salesRep($company);
        $van = Warehouse::factory()->create([
            'company_id' => $company->id, 'type' => 'van', 'user_id' => $rep->id,
        ]);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id, 'price' => 500]);

        $this->actingAs($rep);

        app(StockService::class)->increment(
            $van->id, $product->id, null, 50.0,
            StockReason::Initial, $product,
        );

        $invoice = app(InvoiceService::class)->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 500,
        ]);

        $total = (float) $invoice->total;

        app(PaymentService::class)->collect(
            companyId: $company->id,
            userId: $rep->id,
            customerId: $customer->id,
            amount: $total,
            method: 'cash',
            invoiceId: $invoice->id,
        );

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Paid, $invoice->status);
        $this->assertSame(0.0, (float) $invoice->remaining_amount);
        $this->assertSame($total, (float) $invoice->paid_amount);
    }

    public function test_cancel_payment_reverses_cashbox_and_restores_invoice(): void
    {
        $this->seed(RoleSeeder::class);
        $company = Company::factory()->create();
        $rep = $this->salesRep($company);
        $rep->assignRole('sales_manager');
        $van = Warehouse::factory()->create([
            'company_id' => $company->id, 'type' => 'van', 'user_id' => $rep->id,
        ]);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id, 'price' => 1000]);

        $this->actingAs($rep);

        app(StockService::class)->increment(
            $van->id, $product->id, null, 50.0,
            StockReason::Initial, $product,
        );

        $invoice = app(InvoiceService::class)->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 1000,
        ]);

        $total = (float) $invoice->total;

        $payment = app(PaymentService::class)->collect(
            companyId: $company->id,
            userId: $rep->id,
            customerId: $customer->id,
            amount: $total,
            method: 'cash',
            invoiceId: $invoice->id,
        );

        $cashBoxBefore = (float) CashBox::where('user_id', $rep->id)->first()->balance;
        $this->assertSame($total, $cashBoxBefore);

        app(PaymentService::class)->cancel($payment, $rep->id, 'test cancel');

        $cashBoxAfter = (float) CashBox::where('user_id', $rep->id)->first()->balance;
        $this->assertSame(0.0, $cashBoxAfter);

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Issued, $invoice->status);
        $this->assertSame(0.0, (float) $invoice->paid_amount);
        $this->assertSame($total, (float) $invoice->remaining_amount);

        $this->assertNotNull($payment->fresh()->cancelled_at);
    }

    public function test_collect_payment_preserves_overpayment_as_customer_credit(): void
    {
        $company = Company::factory()->create();
        $rep = $this->salesRep($company);
        $van = Warehouse::factory()->create(['company_id' => $company->id, 'type' => 'van', 'user_id' => $rep->id]);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id, 'price' => 100]);
        $this->actingAs($rep);
        app(StockService::class)->increment($van->id, $product->id, null, 10, StockReason::Initial, $product);
        $invoice = app(InvoiceService::class)->create([
            'company_id' => $company->id, 'customer_id' => $customer->id,
            'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100,
        ]);

        $payment = app(PaymentService::class)->collect(
            $company->id, $rep->id, $customer->id, (float) $invoice->total + 1, 'cash', $invoice->id,
        );

        $this->assertDatabaseCount('payments', 1);
        $this->assertSame('1.00', $payment->unallocated_amount);
        $this->assertDatabaseHas('customer_credits', ['payment_id' => $payment->id, 'amount' => '1.00']);
    }

    public function test_non_cash_method_does_not_credit_cashbox(): void
    {
        $company = Company::factory()->create();
        $rep = $this->salesRep($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        app(PaymentService::class)->collect(
            companyId: $company->id,
            userId: $rep->id,
            customerId: $customer->id,
            amount: 100.0,
            method: 'cheque',
        );

        $cashBox = CashBox::where('user_id', $rep->id)->first();
        $this->assertNotNull($cashBox);
        $this->assertSame(0.0, (float) $cashBox->balance);
    }
}
