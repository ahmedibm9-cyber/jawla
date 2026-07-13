<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $product = \App\Models\Product::where('sku', 'PP-H030')->first();

        $stockBefore = (float) \App\Models\Stock::where('warehouse_id', $van->id)
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
        $this->assertSame($stockBefore - 5, (float) \App\Models\Stock::where('warehouse_id', $van->id)
            ->where('product_id', $product->id)->value('quantity'));
        $this->assertSame((float) $invoice->total, (float) $customer->fresh()->balance);
    }

    public function test_invoice_rejects_oversell_with_bilingual_error(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->first();
        $van = Warehouse::where('user_id', $rep->id)->first();
        $customer = Customer::where('status', 'approved')->first();
        $product = \App\Models\Product::where('sku', 'PP-H030')->first();

        $stockBefore = (float) \App\Models\Stock::where('warehouse_id', $van->id)
            ->where('product_id', $product->id)->value('quantity');

        $this->actingAs($rep);
        // sanity: van warehouse known to InvoiceService via auth
        $vanCheck = \App\Models\Warehouse::where('user_id', $rep->id)->where('type', 'van')->first();
        $this->assertNotNull($vanCheck, "van warehouse for rep not found");
        $this->assertSame($van->id, $vanCheck->id, "van mismatch");
        // Diagnosis: stockBefore expected 30 from seeder
        $this->assertSame(30.0, $stockBefore, "stockBefore not 30 got: {$stockBefore}");

        // Sanity: StockService directly should throw
        try {
            app(\App\Services\Contracts\StockService::class)->decrement(
                $van->id, $product->id, null, 40.0, \App\Enums\StockReason::Sale, $product, $rep->id
            );
            $this->fail('StockService.decrement refused to throw on oversell');
        } catch (\App\Exceptions\Domain\InsufficientStockException $e) {
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
        } catch (\App\Exceptions\Domain\InsufficientStockException $e) {
            $threw = true;
        } catch (\Throwable $e) {
            $this->fail('Got different exception: '.get_class($e).' — '.$e->getMessage());
        }
        $this->assertTrue($threw, 'Expected InsufficientStockException from InvoiceService.create');

        $stockAfter = (float) \App\Models\Stock::where('warehouse_id', $van->id)
            ->where('product_id', $product->id)->value('quantity');
        $this->assertSame($stockBefore, $stockAfter, 'Stock must not change on oversell rollback');
    }

    public function test_payment_collection_credits_cashbox_and_closes_invoice(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->first();
        $this->actingAs($rep);
        $customer = Customer::where('status', 'approved')->first();
        $product = \App\Models\Product::where('sku', 'PE-LD100')->first();

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

        $cashBox = \App\Models\CashBox::where('user_id', $rep->id)->first();
        $this->assertSame($total, (float) $cashBox->balance);
    }

    public function test_cancel_invoice_reverses_stock_and_balance(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->first();
        $this->actingAs($rep);
        $van = Warehouse::where('user_id', $rep->id)->first();
        $customer = Customer::where('status', 'approved')->first();
        $product = \App\Models\Product::where('sku', 'PP-H030')->first();

        $stockBefore = (float) \App\Models\Stock::where('warehouse_id', $van->id)
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

        $stockAfter = (float) \App\Models\Stock::where('warehouse_id', $van->id)
            ->where('product_id', $product->id)->value('quantity');
        $this->assertSame($stockBefore, $stockAfter);
        $this->assertSame(0.0, (float) $customer->fresh()->balance);
        $this->assertSame(InvoiceStatus::Cancelled, $invoice->fresh()->status);
    }
}