<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\StockReason;
use App\Exceptions\Domain\InsufficientStockException;
use App\Models\CashBox;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Contracts\StockService;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InvoiceFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    public function test_invoice_creation_decrements_stock_and_updates_balance_atomically(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->first();
        $van = Warehouse::where('user_id', $rep->id)->first();
        $customer = Customer::where('status', 'approved')->first();
        $product = Product::where('sku', 'VIR-PP-H030')->first();

        // Reset stock for this product to a known value (DemoSeeder's
        // 40-invoice loop is non-deterministic and may leave it depleted).
        DB::table('stocks')->updateOrInsert(
            ['warehouse_id' => $van->id, 'product_id' => $product->id, 'batch_id' => null],
            ['quantity' => 10],
        );

        $stockBefore = (float) Stock::where('warehouse_id', $van->id)
            ->where('product_id', $product->id)->value('quantity');

        $this->actingAs($rep);

        $invoice = app(InvoiceService::class)->create([
            'company_id' => $rep->company_id,
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'unit_price' => 1000,
        ]);

        $this->assertSame(InvoiceStatus::Submitted, $invoice->status);
        $this->assertSame($stockBefore - 5, (float) Stock::where('warehouse_id', $van->id)
            ->where('product_id', $product->id)->value('quantity'));
        $this->assertSame((float) $invoice->total, (float) $customer->fresh()->balance);
    }

    public function test_invoice_rejects_oversell_with_bilingual_error(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->first();
        $van = Warehouse::where('user_id', $rep->id)->first();
        $customer = Customer::where('status', 'approved')->first();
        $product = Product::where('sku', 'VIR-PP-H030')->first();

        $stockBefore = (float) Stock::where('warehouse_id', $van->id)
            ->where('product_id', $product->id)->value('quantity');

        $this->actingAs($rep);
        // sanity: van warehouse known to InvoiceService via auth
        $vanCheck = Warehouse::where('user_id', $rep->id)->where('type', 'van')->first();
        $this->assertNotNull($vanCheck, 'van warehouse for rep not found');
        $this->assertSame($van->id, $vanCheck->id, 'van mismatch');
        // The seeder leaves the van with ample stock; the oversell amounts below
        // are taken relative to $stockBefore so this always exceeds availability.
        $this->assertGreaterThan(0, $stockBefore, "stockBefore must be positive, got: {$stockBefore}");

        // Sanity: StockService directly should throw
        try {
            app(StockService::class)->decrement(
                $van->id, $product->id, null, $stockBefore + 40.0, StockReason::Sale, $product, $rep->id
            );
            $this->fail('StockService.decrement refused to throw on oversell');
        } catch (InsufficientStockException $e) {
            // confirmed StockService path works
        }

        // InvoiceService.create should bubble the exception up through DB::transaction
        $threw = false;
        try {
            app(InvoiceService::class)->create([
                'company_id' => $rep->company_id,
                'customer_id' => $customer->id,
                'product_id' => $product->id,
                'quantity' => $stockBefore + 10,
                'unit_price' => 1000,
            ]);
        } catch (InsufficientStockException $e) {
            $threw = true;
        } catch (\Throwable $e) {
            $this->fail('Got different exception: '.get_class($e).' — '.$e->getMessage());
        }
        $this->assertTrue($threw, 'Expected InsufficientStockException from InvoiceService.create');

        $stockAfter = (float) Stock::where('warehouse_id', $van->id)
            ->where('product_id', $product->id)->value('quantity');
        $this->assertSame($stockBefore, $stockAfter, 'Stock must not change on oversell rollback');
    }

    public function test_invoice_requires_the_seller_van_warehouse_before_creating_any_financial_record(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->firstOrFail();
        $customer = Customer::where('status', 'approved')->firstOrFail();
        $product = Product::where('sku', 'VIR-PP-H030')->firstOrFail();
        Warehouse::where('user_id', $rep->id)->where('type', 'van')->update(['type' => 'main']);
        $this->actingAs($rep);

        $invoicesBefore = Invoice::count();

        try {
            app(InvoiceService::class)->create([
                'company_id' => $rep->company_id,
                'customer_id' => $customer->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 100,
            ]);
            $this->fail('Expected a sale without a van warehouse to be rejected.');
        } catch (\DomainException $exception) {
            $this->assertNotSame('', $exception->getMessage());
        }

        $this->assertSame($invoicesBefore, Invoice::count(), 'No new invoices should be created on a failed sale');
        $this->assertSame(0.0, (float) $customer->fresh()->balance);
    }

    public function test_invoice_rejects_a_customer_from_another_company_before_any_write(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->firstOrFail();
        $product = Product::where('sku', 'VIR-PP-H030')->firstOrFail();
        $foreignCustomer = Customer::factory()->create(['company_id' => Company::factory()->create()->id]);
        $this->actingAs($rep);
        $invoicesBefore = Invoice::count();
        $movementsBefore = Stock::count();

        $this->expectException(\App\Exceptions\Domain\DomainException::class);
        try {
            app(InvoiceService::class)->create([
                'company_id' => $rep->company_id,
                'customer_id' => $foreignCustomer->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 100,
            ]);
        } finally {
            $this->assertSame($invoicesBefore, Invoice::count());
            $this->assertSame($movementsBefore, Stock::count());
        }
    }

    public function test_invoice_rejects_a_product_from_another_company_before_any_write(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->firstOrFail();
        $customer = Customer::where('status', 'approved')->firstOrFail();
        $foreignProduct = Product::factory()->create(['company_id' => Company::factory()->create()->id]);
        $this->actingAs($rep);
        $invoicesBefore = Invoice::count();
        $movementsBefore = Stock::count();

        $this->expectException(\App\Exceptions\Domain\DomainException::class);
        try {
            app(InvoiceService::class)->create([
                'company_id' => $rep->company_id,
                'customer_id' => $customer->id,
                'product_id' => $foreignProduct->id,
                'quantity' => 1,
                'unit_price' => 100,
            ]);
        } finally {
            $this->assertSame($invoicesBefore, Invoice::count());
            $this->assertSame($movementsBefore, Stock::count());
        }
    }

    public function test_payment_collection_credits_cashbox_and_closes_invoice(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->first();
        $this->actingAs($rep);
        $van = Warehouse::where('user_id', $rep->id)->first();
        $customer = Customer::where('status', 'approved')->first();
        // Use a product guaranteed to have stock: seed directly via
        // DB table to avoid nested-transaction issues with StockService.
        $product = Product::where('sku', 'VIR-PE-LD200')->first();
        DB::table('stocks')->updateOrInsert(
            ['warehouse_id' => $van->id, 'product_id' => $product->id, 'batch_id' => null],
            ['quantity' => 10],
        );

        $invoice = app(InvoiceService::class)->create([
            'company_id' => $rep->company_id,
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 1200,
        ]);

        $total = (float) $invoice->total;

        $payment = app(PaymentService::class)->collect(
            companyId: $rep->company_id,
            userId: $rep->id,
            customerId: $customer->id,
            amount: $total,
            method: 'cash',
            invoiceId: $invoice->id,
        );

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Paid, $invoice->status);
        $this->assertSame(0.0, (float) $invoice->remaining_amount);

        $cashBox = CashBox::where('user_id', $rep->id)->first();
        $this->assertSame($total, (float) $cashBox->balance);
    }

    public function test_cancel_invoice_reverses_stock_and_balance(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->first();
        $this->actingAs($rep);
        $van = Warehouse::where('user_id', $rep->id)->first();
        $customer = Customer::where('status', 'approved')->first();
        $product = Product::where('sku', 'VIR-PP-H030')->first();

        // Seed sufficient stock directly (avoids DemoSeeder's non-deterministic
        // 40-invoice loop which may leave stock below the 3 units we need).
        DB::table('stocks')->updateOrInsert(
            ['warehouse_id' => $van->id, 'product_id' => $product->id, 'batch_id' => null],
            ['quantity' => 10],
        );

        $stockBefore = (float) Stock::where('warehouse_id', $van->id)
            ->where('product_id', $product->id)->value('quantity');

        $invoice = app(InvoiceService::class)->create([
            'company_id' => $rep->company_id,
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 1000,
        ]);

        $balanceAfter = (float) $customer->fresh()->balance;
        $this->assertGreaterThan(0, $balanceAfter);

        app(InvoiceService::class)->cancel($invoice, $rep->id, 'test cancel');

        $stockAfter = (float) Stock::where('warehouse_id', $van->id)
            ->where('product_id', $product->id)->value('quantity');
        $this->assertSame($stockBefore, $stockAfter);
        $this->assertSame(0.0, (float) $customer->fresh()->balance);
        $this->assertSame(InvoiceStatus::Cancelled, $invoice->fresh()->status);
    }
}
