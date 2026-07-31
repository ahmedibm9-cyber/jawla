<?php

declare(strict_types=1);

namespace Tests\Feature\EdgeCases;

use App\Enums\InvoiceStatus;
use App\Exceptions\Domain\InsufficientStockException;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerCredit;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\ReturnService;
use App\Support\ActiveCompanyContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * P0 edge-case integration tests: money flow, stock mutations, authorization.
 *
 * ponytail: DatabaseTransactions + RoleSeeder + factories for parallel safety.
 * vat_percent=0 on company to get predictable totals (VAT logic tested elsewhere).
 */
class MoneyFlowEdgeCasesTest extends TestCase
{
    use DatabaseTransactions;

    private Company $company;

    private User $rep;

    private User $manager;

    private User $warehouseKeeper;

    private Customer $customer;

    private Product $product;

    private Warehouse $van;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        // ponytail: Create all factory data BEFORE setting ActiveCompanyContext.
        // setCompanyId() disables unscoped mode, which blocks subsequent factory writes.
        $this->company = Company::factory()->create(['vat_percent' => 0]);

        $this->rep = User::factory()->create(['company_id' => $this->company->id]);
        $this->rep->assignRole('rep');

        $this->manager = User::factory()->create(['company_id' => $this->company->id]);
        $this->manager->assignRole('sales_manager');

        $this->warehouseKeeper = User::factory()->create(['company_id' => $this->company->id]);
        $this->warehouseKeeper->assignRole('warehouse_keeper');

        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'approved',
        ]);

        $this->product = Product::factory()->create([
            'company_id' => $this->company->id,
            'price' => 100.00,
        ]);

        $this->van = Warehouse::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->rep->id,
            'type' => 'van',
        ]);

        // Ensure stock for the product in the van
        DB::table('stocks')->updateOrInsert(
            ['warehouse_id' => $this->van->id, 'product_id' => $this->product->id, 'batch_id' => null],
            ['quantity' => 50],
        );

        // Set context AFTER all factory data is created
        app(ActiveCompanyContext::class)->setCompanyId($this->company->id);
    }

    protected function tearDown(): void
    {
        app(ActiveCompanyContext::class)->disable();
        parent::tearDown();
    }

    // ─── Invoice edge cases ──────────────────────────────────────────

    /** Invoice with negative price is rejected by pricing service. */
    #[Test]
    public function test_invoice_rejects_negative_price(): void
    {
        $this->actingAs($this->rep);

        $this->expectException(\Throwable::class);
        app(InvoiceService::class)->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'quantity' => 5,
            'unit_price' => -100.00,
        ]);
    }

    /** Invoice for a customer from another company is rejected. */
    #[Test]
    public function test_invoice_rejects_cross_company_customer(): void
    {
        // ponytail: Use raw DB inserts to bypass BelongsToCompany model events.
        $otherCompanyId = DB::table('companies')->insertGetId([
            'name_ar' => 'شركة أخرى', 'name_en' => 'Other Co',
            'tax_number' => 'TAX-'.uniqid(), 'currency' => 'EGP',
            'vat_percent' => 0, 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $otherCustomerId = DB::table('customers')->insertGetId([
            'company_id' => $otherCompanyId, 'code' => 'CROSS-'.uniqid(),
            'name_ar' => 'عميل', 'name_en' => 'Cross Co Customer',
            'status' => 'approved', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->rep);

        $this->expectException(\Throwable::class);
        app(InvoiceService::class)->create([
            'company_id' => $this->company->id,
            'customer_id' => $otherCustomerId,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'unit_price' => 100.00,
        ]);
    }

    /** Invoice stock is restored on failure (atomic rollback). */
    #[Test]
    public function test_invoice_stock_restored_on_oversell_failure(): void
    {
        $stockBefore = (float) Stock::where('warehouse_id', $this->van->id)
            ->where('product_id', $this->product->id)
            ->value('quantity');

        $this->actingAs($this->rep);

        try {
            app(InvoiceService::class)->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'product_id' => $this->product->id,
                'quantity' => $stockBefore + 100,
                'unit_price' => 100.00,
            ]);
            $this->fail('Expected InsufficientStockException');
        } catch (InsufficientStockException) {
            // expected
        }

        $stockAfter = (float) Stock::where('warehouse_id', $this->van->id)
            ->where('product_id', $this->product->id)
            ->value('quantity');
        $this->assertSame($stockBefore, $stockAfter, 'Stock must not change on failed invoice');
    }

    // ─── Payment edge cases ──────────────────────────────────────────

    /** Overpayment creates a CustomerCredit record. */
    #[Test]
    public function test_overpayment_creates_customer_credit(): void
    {
        $this->actingAs($this->rep);

        $invoice = app(InvoiceService::class)->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'unit_price' => 100.00,
        ]);

        $this->assertSame(200.00, (float) $invoice->total);

        // Pay 300 on a 200 invoice
        $payment = app(PaymentService::class)->collect(
            companyId: $this->company->id,
            userId: $this->rep->id,
            customerId: $this->customer->id,
            amount: 300.00,
            method: 'cash',
            invoiceId: $invoice->id,
        );

        $this->assertSame(300.00, (float) $payment->amount);
        $this->assertSame(200.00, (float) $payment->allocated_amount);
        $this->assertSame(100.00, (float) $payment->unallocated_amount);

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Paid, $invoice->status);
        $this->assertSame(0.00, (float) $invoice->remaining_amount);

        // Customer credit should exist for the overage
        $credit = CustomerCredit::where('customer_id', $this->customer->id)
            ->where('payment_id', $payment->id)
            ->first();
        $this->assertNotNull($credit);
        $this->assertSame(100.00, (float) $credit->amount);
    }

    /** Payment amount of zero is rejected. */
    #[Test]
    public function test_zero_payment_rejected(): void
    {
        $this->actingAs($this->rep);

        $this->expectException(\Throwable::class);
        app(PaymentService::class)->collect(
            companyId: $this->company->id,
            userId: $this->rep->id,
            customerId: $this->customer->id,
            amount: 0,
            method: 'cash',
        );
    }

    /** Payment amount of negative is rejected. */
    #[Test]
    public function test_negative_payment_rejected(): void
    {
        $this->actingAs($this->rep);

        $this->expectException(\Throwable::class);
        app(PaymentService::class)->collect(
            companyId: $this->company->id,
            userId: $this->rep->id,
            customerId: $this->customer->id,
            amount: -50,
            method: 'cash',
        );
    }

    /** Payment to a cancelled invoice is rejected. */
    #[Test]
    public function test_payment_to_cancelled_invoice_rejected(): void
    {
        $this->actingAs($this->rep);

        $invoice = app(InvoiceService::class)->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'unit_price' => 100.00,
        ]);

        $invoice->update(['status' => InvoiceStatus::Cancelled]);

        $this->expectException(\Throwable::class);
        app(PaymentService::class)->collect(
            companyId: $this->company->id,
            userId: $this->rep->id,
            customerId: $this->customer->id,
            amount: 100.00,
            method: 'cash',
            invoiceId: $invoice->id,
        );
    }

    /** Duplicate payment with same intent_id returns existing payment (idempotent). */
    #[Test]
    public function test_duplicate_payment_is_idempotent(): void
    {
        $this->actingAs($this->rep);

        $invoice = app(InvoiceService::class)->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'unit_price' => 100.00,
        ]);

        $intentId = 'test-intent-'.uniqid();

        $p1 = app(PaymentService::class)->collect(
            companyId: $this->company->id,
            userId: $this->rep->id,
            customerId: $this->customer->id,
            amount: 100.00,
            method: 'cash',
            invoiceId: $invoice->id,
            intentId: $intentId,
        );

        $p2 = app(PaymentService::class)->collect(
            companyId: $this->company->id,
            userId: $this->rep->id,
            customerId: $this->customer->id,
            amount: 100.00,
            method: 'cash',
            invoiceId: $invoice->id,
            intentId: $intentId,
        );

        $this->assertSame($p1->id, $p2->id, 'Duplicate payment must return existing record');
        $this->assertSame(1, Payment::where('intent_id', $intentId)->count());
    }

    // ─── Return edge cases ───────────────────────────────────────────

    /** Return without an invoice reference is rejected. */
    #[Test]
    public function test_return_requires_invoice(): void
    {
        $this->actingAs($this->rep);

        $this->expectException(\Throwable::class);
        app(ReturnService::class)->create(
            companyId: $this->company->id,
            userId: $this->rep->id,
            customerId: $this->customer->id,
            items: [['product_id' => $this->product->id, 'quantity' => 1]],
            againstInvoiceId: null,
        );
    }

    /** Return with empty items is rejected. */
    #[Test]
    public function test_return_rejects_empty_items(): void
    {
        $this->actingAs($this->rep);

        $invoice = app(InvoiceService::class)->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'quantity' => 5,
            'unit_price' => 100.00,
        ]);

        $this->expectException(\Throwable::class);
        app(ReturnService::class)->create(
            companyId: $this->company->id,
            userId: $this->rep->id,
            customerId: $this->customer->id,
            items: [],
            againstInvoiceId: $invoice->id,
        );
    }

    /** Return quantity exceeding purchased quantity is rejected. */
    #[Test]
    public function test_return_rejects_quantity_exceeding_purchase(): void
    {
        $this->actingAs($this->rep);

        $invoice = app(InvoiceService::class)->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'unit_price' => 100.00,
        ]);

        $item = $invoice->items->first();

        $this->expectException(\Throwable::class);
        app(ReturnService::class)->create(
            companyId: $this->company->id,
            userId: $this->rep->id,
            customerId: $this->customer->id,
            items: [['invoice_item_id' => $item->id, 'quantity' => 5]],
            againstInvoiceId: $invoice->id,
        );
    }

    /** Return from a cancelled invoice is rejected. */
    #[Test]
    public function test_return_rejects_cancelled_invoice(): void
    {
        $this->actingAs($this->rep);

        $invoice = app(InvoiceService::class)->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'quantity' => 5,
            'unit_price' => 100.00,
        ]);

        $invoice->update(['status' => InvoiceStatus::Cancelled]);

        $item = $invoice->items->first();

        $this->expectException(\Throwable::class);
        app(ReturnService::class)->create(
            companyId: $this->company->id,
            userId: $this->rep->id,
            customerId: $this->customer->id,
            items: [['invoice_item_id' => $item->id, 'quantity' => 1]],
            againstInvoiceId: $invoice->id,
        );
    }

    /** Return stock is restored to van after successful return. */
    #[Test]
    public function test_return_restores_van_stock(): void
    {
        $this->actingAs($this->rep);

        $invoice = app(InvoiceService::class)->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'quantity' => 3,
            'unit_price' => 100.00,
        ]);

        $stockAfterSale = (float) Stock::where('warehouse_id', $this->van->id)
            ->where('product_id', $this->product->id)
            ->value('quantity');

        $item = $invoice->items->first();

        $return = app(ReturnService::class)->create(
            companyId: $this->company->id,
            userId: $this->rep->id,
            customerId: $this->customer->id,
            items: [['invoice_item_id' => $item->id, 'quantity' => 1]],
            againstInvoiceId: $invoice->id,
        );

        $this->assertNotNull($return);

        $stockAfterReturn = (float) Stock::where('warehouse_id', $this->van->id)
            ->where('product_id', $this->product->id)
            ->value('quantity');

        $this->assertSame($stockAfterSale + 1, $stockAfterReturn, 'Return must restore 1 unit to van stock');
    }

    // ─── Authorization edge cases ────────────────────────────────────

    /** Rep from company A cannot pay company B's invoice. */
    #[Test]
    public function test_rep_cannot_access_other_company_invoice(): void
    {
        // ponytail: Raw DB inserts to bypass BelongsToCompany model events entirely.
        $otherCompanyId = DB::table('companies')->insertGetId([
            'name_ar' => 'شركة ب', 'name_en' => 'Company B',
            'tax_number' => 'TAX-'.uniqid(), 'currency' => 'EGP',
            'vat_percent' => 0, 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $otherRepId = DB::table('users')->insertGetId([
            'name' => 'Other Rep', 'email' => 'other-rep-'.uniqid().'@test.com',
            'password' => bcrypt('password'), 'company_id' => $otherCompanyId,
            'employee_code' => 'EMP-'.uniqid(), 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('customers')->insert([
            'company_id' => $otherCompanyId, 'code' => 'CUST-'.uniqid(),
            'name_ar' => 'عميل', 'name_en' => 'Other Customer',
            'status' => 'approved', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $otherCustomerId = DB::table('customers')->where('company_id', $otherCompanyId)->first()->id;
        DB::table('products')->insert([
            'company_id' => $otherCompanyId, 'name_ar' => 'منتج', 'name_en' => 'Product B',
            'sku' => 'SKU-'.uniqid(), 'price' => 50.00, 'vat_applicable' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $otherProductId = DB::table('products')->where('company_id', $otherCompanyId)->first()->id;
        DB::table('warehouses')->insert([
            'company_id' => $otherCompanyId, 'user_id' => $otherRepId,
            'name_ar' => 'شاحنة', 'name_en' => 'Van B', 'type' => 'van', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $otherVanId = DB::table('warehouses')->where('user_id', $otherRepId)->first()->id;
        DB::table('stocks')->insert([
            'warehouse_id' => $otherVanId, 'product_id' => $otherProductId,
            'quantity' => 100,
        ]);

        // Create invoice in other company context
        $ctx = app(ActiveCompanyContext::class);
        $otherInvoice = $ctx->runWithCompany($otherCompanyId, function () use ($otherCompanyId, $otherCustomerId, $otherProductId) {
            return app(InvoiceService::class)->create([
                'company_id' => $otherCompanyId,
                'customer_id' => $otherCustomerId,
                'product_id' => $otherProductId,
                'quantity' => 1,
                'unit_price' => 50.00,
            ]);
        });

        // Context restored to company A
        $this->actingAs($this->rep);

        $this->expectException(\Throwable::class);
        app(PaymentService::class)->collect(
            companyId: $this->company->id,
            userId: $this->rep->id,
            customerId: $this->customer->id,
            amount: 50.00,
            method: 'cash',
            invoiceId: $otherInvoice->id,
        );
    }

    /** Non-rep user cannot create an invoice. */
    #[Test]
    public function test_non_rep_cannot_create_invoice(): void
    {
        $accounts = User::factory()->create(['company_id' => $this->company->id]);
        $accounts->assignRole('accounts');

        $this->actingAs($accounts);

        $this->expectException(\Throwable::class);
        app(InvoiceService::class)->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'unit_price' => 100.00,
        ]);
    }
}
