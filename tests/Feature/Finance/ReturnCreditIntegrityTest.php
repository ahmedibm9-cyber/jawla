<?php

namespace Tests\Feature\Finance;

use App\Enums\InvoiceStatus;
use App\Exceptions\Domain\DomainException;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerCredit;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\ReturnItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ReturnService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReturnCreditIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $rep;

    private Customer $customer;

    private Product $product;

    private Invoice $invoice;

    private InvoiceItem $line;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->company = Company::factory()->create();
        $this->rep = User::factory()->create(['company_id' => $this->company->id]);
        $this->rep->assignRole('sales_rep');
        Warehouse::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'van',
            'user_id' => $this->rep->id,
        ]);
        Warehouse::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'quarantine',
            'name_en' => 'Quarantine',
            'name_ar' => 'الحجر',
        ]);
        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'balance' => 0,
        ]);
        $this->product = Product::factory()->create(['company_id' => $this->company->id]);
        $this->invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'user_id' => $this->rep->id,
            'status' => InvoiceStatus::Paid,
            'subtotal' => 500,
            'vat_amount' => 70,
            'total' => 570,
            'paid_amount' => 570,
            'remaining_amount' => 0,
        ]);
        $this->line = InvoiceItem::create([
            'invoice_id' => $this->invoice->id,
            'product_id' => $this->product->id,
            'quantity' => 5,
            'unit_price' => 100,
            'line_total' => 500,
            'tax_amount' => 70,
        ]);
    }

    public function test_paid_invoice_return_uses_locked_original_values_and_creates_customer_credit(): void
    {
        $otherProduct = Product::factory()->create(['company_id' => $this->company->id]);

        $return = app(ReturnService::class)->create(
            companyId: $this->company->id,
            userId: $this->rep->id,
            customerId: $this->customer->id,
            againstInvoiceId: $this->invoice->id,
            items: [[
                'invoice_item_id' => $this->line->id,
                'quantity' => 2,
                'condition' => 'damaged',
                'product_id' => $otherProduct->id,
                'unit_price' => 0.01,
            ]],
            reason: 'Damaged in transit',
        );

        $item = $return->items()->firstOrFail();
        $this->assertSame($this->line->id, $item->invoice_item_id);
        $this->assertSame($this->product->id, $item->product_id);
        $this->assertSame('100.00', $item->unit_price);
        $this->assertSame('228.00', $return->total);
        $this->assertDatabaseHas('customer_credits', [
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'return_id' => $return->id,
            'amount' => '228.00',
            'remaining_amount' => '228.00',
            'status' => 'available',
        ]);
        $this->assertSame('2.000', $this->product->stocks()
            ->whereHas('warehouse', fn ($query) => $query->where('type', 'quarantine'))
            ->value('quantity'));
    }

    public function test_return_quantity_cannot_exceed_sold_less_prior_returns(): void
    {
        $service = app(ReturnService::class);
        $service->create(
            $this->company->id,
            $this->rep->id,
            $this->customer->id,
            [['invoice_item_id' => $this->line->id, 'quantity' => 4, 'condition' => 'sellable']],
            $this->invoice->id,
        );

        $this->expectException(DomainException::class);
        try {
            $service->create(
                $this->company->id,
                $this->rep->id,
                $this->customer->id,
                [['invoice_item_id' => $this->line->id, 'quantity' => 2, 'condition' => 'sellable']],
                $this->invoice->id,
            );
        } finally {
            $this->assertSame('4.000', ReturnItem::sum('quantity'));
            $this->assertSame(1, CustomerCredit::count());
        }
    }

    public function test_unreferenced_return_is_rejected_before_writes(): void
    {
        $this->expectException(DomainException::class);
        try {
            app(ReturnService::class)->create(
                $this->company->id,
                $this->rep->id,
                $this->customer->id,
                [['invoice_item_id' => $this->line->id, 'quantity' => 1, 'condition' => 'sellable']],
            );
        } finally {
            $this->assertDatabaseCount('returns', 0);
            $this->assertDatabaseCount('return_items', 0);
            $this->assertDatabaseCount('customer_credits', 0);
        }
    }
}
