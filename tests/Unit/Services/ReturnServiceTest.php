<?php

namespace Tests\Unit\Services;

use App\Enums\InvoiceStatus;
use App\Enums\StockReason;
use App\Exceptions\Domain\DomainException;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Contracts\StockService;
use App\Services\ReturnService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReturnServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_invoice_linked_return_restores_stock_and_reduces_receivable(): void
    {
        [$company, $rep, $van, $customer, $product, $invoice, $line] = $this->saleFixture(
            quantity: '5.000',
            unitPrice: '100.00',
            customerBalance: '500.00',
        );
        app(StockService::class)->increment(
            $van->id, $product->id, null, 50.0, StockReason::Initial, $product,
        );

        $return = app(ReturnService::class)->create(
            companyId: $company->id,
            userId: $rep->id,
            customerId: $customer->id,
            items: [['invoice_item_id' => $line->id, 'quantity' => '5.000', 'condition' => 'sellable']],
            againstInvoiceId: $invoice->id,
            reason: 'Customer return',
        );

        $this->assertSame(55.0, app(StockService::class)->balance($van->id, $product->id));
        $this->assertSame(0.0, (float) $customer->fresh()->balance);
        $this->assertSame('500.00', $return->total);
        $this->assertSame('submitted', $return->status);
        $this->assertDatabaseHas('credit_notes', ['return_id' => $return->id, 'total' => '500.00']);
    }

    public function test_manager_compensating_return_reversal_restores_stock_and_receivable_once(): void
    {
        $this->seed(RoleSeeder::class);
        [$company, $rep, $van, $customer, $product, $invoice, $line] = $this->saleFixture(
            quantity: '3.000',
            unitPrice: '50.00',
            customerBalance: '300.00',
        );
        $manager = User::factory()->create(['company_id' => $company->id]);
        $manager->assignRole('sales_manager');
        app(StockService::class)->increment(
            $van->id, $product->id, null, 20.0, StockReason::Initial, $product,
        );
        $return = app(ReturnService::class)->create(
            $company->id,
            $rep->id,
            $customer->id,
            [['invoice_item_id' => $line->id, 'quantity' => '3.000', 'condition' => 'sellable']],
            $invoice->id,
        );

        $service = app(ReturnService::class);
        $service->cancel($return, $manager->id, 'Approved correction');
        $service->cancel($return, $manager->id, 'Approved correction');

        $this->assertSame(20.0, app(StockService::class)->balance($van->id, $product->id));
        $this->assertSame(300.0, (float) $customer->fresh()->balance);
        $this->assertSame('cancelled', $return->fresh()->status);
        $this->assertDatabaseHas('credit_notes', ['return_id' => $return->id, 'status' => 'reversed']);
        $this->assertDatabaseCount('reversals', 1);
    }

    public function test_return_requires_a_van_before_creating_financial_records(): void
    {
        [$company, $rep, $van, $customer, $product, $invoice, $line] = $this->saleFixture();
        $van->update(['is_active' => false]);

        $this->expectException(DomainException::class);
        try {
            app(ReturnService::class)->create(
                $company->id,
                $rep->id,
                $customer->id,
                [['invoice_item_id' => $line->id, 'quantity' => '1.000']],
                $invoice->id,
            );
        } finally {
            $this->assertDatabaseCount('returns', 0);
            $this->assertSame('100.00', $customer->fresh()->balance);
        }
    }

    public function test_return_quantity_cannot_exceed_original_sold_quantity(): void
    {
        [$company, $rep, , $customer, , $invoice, $line] = $this->saleFixture(
            quantity: '2.000',
            unitPrice: '50.00',
        );

        $this->expectException(DomainException::class);
        app(ReturnService::class)->create(
            $company->id,
            $rep->id,
            $customer->id,
            [['invoice_item_id' => $line->id, 'quantity' => '3.000']],
            $invoice->id,
        );
    }

    public function test_return_rejects_foreign_customer_before_any_write(): void
    {
        [$company, $rep, , , , $invoice, $line] = $this->saleFixture();
        $foreignCompany = Company::factory()->create();
        $foreignCustomer = Customer::factory()->create(['company_id' => $foreignCompany->id]);

        $this->expectException(DomainException::class);
        try {
            app(ReturnService::class)->create(
                $company->id,
                $rep->id,
                $foreignCustomer->id,
                [['invoice_item_id' => $line->id, 'quantity' => '1.000']],
                $invoice->id,
            );
        } finally {
            $this->assertDatabaseCount('returns', 0);
            $this->assertDatabaseCount('return_items', 0);
        }
    }

    private function saleFixture(
        string $quantity = '1.000',
        string $unitPrice = '100.00',
        string $customerBalance = '100.00',
    ): array {
        $this->seed(RoleSeeder::class);
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $van = Warehouse::factory()->create([
            'company_id' => $company->id,
            'type' => 'van',
            'user_id' => $rep->id,
        ]);
        $customer = Customer::factory()->create([
            'company_id' => $company->id,
            'balance' => $customerBalance,
        ]);
        $product = Product::factory()->create(['company_id' => $company->id]);
        $total = bcmul($quantity, $unitPrice, 2);
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'user_id' => $rep->id,
            'status' => InvoiceStatus::Issued,
            'subtotal' => $total,
            'vat_amount' => '0.00',
            'total' => $total,
            'paid_amount' => '0.00',
            'remaining_amount' => $total,
        ]);
        $line = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_total' => $total,
        ]);

        return [$company, $rep, $van, $customer, $product, $invoice, $line];
    }
}
