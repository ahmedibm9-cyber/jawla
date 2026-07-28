<?php

namespace Tests\Unit\Services;

use App\Enums\StockReason;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Contracts\InvoiceService;
use App\Services\Contracts\StockService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceSnapshotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function makeSeller(Company $company): User
    {
        $seller = User::factory()->create();
        $seller->companies()->attach($company->id);
        $seller->assignRole('sales_rep');

        return $seller;
    }

    public function test_snapshot_populated_on_invoice_create(): void
    {
        $company = Company::factory()->create([
            'name_ar' => 'شركة اختبار',
            'name_en' => 'Test Company',
            'tax_number' => '123456789',
            'vat_percent' => 14,
            'bank_name' => 'CIB',
            'bank_iban' => 'EG1234567890',
        ]);
        $customer = Customer::factory()->create([
            'company_id' => $company->id,
            'name_ar' => 'عميل تجريبي',
            'name_en' => 'Test Customer',
            'code' => 'C-001',
        ]);
        $seller = $this->makeSeller($company);
        $product = Product::factory()->create(['company_id' => $company->id, 'vat_applicable' => true, 'price' => 100.00]);
        $vanWarehouse = Warehouse::factory()->create(['company_id' => $company->id, 'type' => 'van', 'user_id' => $seller->id]);

        app(StockService::class)->increment(
            $vanWarehouse->id, $product->id, null, 50.0,
            StockReason::Initial, $product,
        );

        $invoice = app(InvoiceService::class)->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'user_id' => $seller->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5.0, 'unit_price' => 100.00],
            ],
        ]);

        $this->assertNotNull($invoice->snapshot_company);
        $this->assertSame('شركة اختبار', $invoice->snapshot_company['name_ar']);
        $this->assertSame('Test Company', $invoice->snapshot_company['name_en']);
        $this->assertSame('123456789', $invoice->snapshot_company['tax_number']);
        $this->assertSame('14.00', $invoice->snapshot_company['vat_percent']);

        $this->assertNotNull($invoice->snapshot_customer);
        $this->assertSame('عميل تجريبي', $invoice->snapshot_customer['name_ar']);
        $this->assertSame('Test Customer', $invoice->snapshot_customer['name_en']);
        $this->assertSame('C-001', $invoice->snapshot_customer['code']);

        $this->assertNotNull($invoice->snapshot_items);
        $this->assertCount(1, $invoice->snapshot_items);
        $this->assertSame($product->id, $invoice->snapshot_items[0]['product_id']);
        $this->assertSame(5, $invoice->snapshot_items[0]['quantity']);

        $this->assertNotNull($invoice->snapshot_totals);
        $this->assertSame('EGP', $invoice->snapshot_totals['currency']);
        $this->assertSame('14.00', $invoice->snapshot_totals['vat_percent']);
    }

    public function test_snapshot_totals_match_calculation(): void
    {
        $company = Company::factory()->create(['vat_percent' => 14]);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $seller = $this->makeSeller($company);
        $product = Product::factory()->create(['company_id' => $company->id, 'vat_applicable' => true, 'price' => 200.00]);
        $vanWarehouse = Warehouse::factory()->create(['company_id' => $company->id, 'type' => 'van', 'user_id' => $seller->id]);

        app(StockService::class)->increment(
            $vanWarehouse->id, $product->id, null, 50.0,
            StockReason::Initial, $product,
        );

        $invoice = app(InvoiceService::class)->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'user_id' => $seller->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 10.0, 'unit_price' => 200.00],
            ],
        ]);

        $this->assertSame('2000.00', $invoice->snapshot_totals['subtotal']);
        $this->assertSame('280.00', $invoice->snapshot_totals['vat_amount']);
        $this->assertSame('2280.00', $invoice->snapshot_totals['total']);
        $this->assertSame((float) $invoice->subtotal, (float) $invoice->snapshot_totals['subtotal']);
        $this->assertSame((float) $invoice->vat_amount, (float) $invoice->snapshot_totals['vat_amount']);
        $this->assertSame((float) $invoice->total, (float) $invoice->snapshot_totals['total']);
    }

    public function test_snapshot_multi_line_items(): void
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $seller = $this->makeSeller($company);
        $product1 = Product::factory()->create(['company_id' => $company->id, 'price' => 100.00]);
        $product2 = Product::factory()->create(['company_id' => $company->id, 'price' => 50.00]);
        $vanWarehouse = Warehouse::factory()->create(['company_id' => $company->id, 'type' => 'van', 'user_id' => $seller->id]);

        app(StockService::class)->increment(
            $vanWarehouse->id, $product1->id, null, 50.0, StockReason::Initial, $product1,
        );
        app(StockService::class)->increment(
            $vanWarehouse->id, $product2->id, null, 30.0, StockReason::Initial, $product2,
        );

        $invoice = app(InvoiceService::class)->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'user_id' => $seller->id,
            'items' => [
                ['product_id' => $product1->id, 'quantity' => 5.0, 'unit_price' => 100.00],
                ['product_id' => $product2->id, 'quantity' => 3.0, 'unit_price' => 50.00],
            ],
        ]);

        $this->assertCount(2, $invoice->snapshot_items);
        $this->assertSame($product1->id, $invoice->snapshot_items[0]['product_id']);
        $this->assertSame($product2->id, $invoice->snapshot_items[1]['product_id']);
    }

    public function test_zatca_qr_uses_snapshot_totals(): void
    {
        $company = Company::factory()->create([
            'name_ar' => 'شركة زاتكا',
            'tax_number' => '987654321',
        ]);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $seller = $this->makeSeller($company);
        $product = Product::factory()->create(['company_id' => $company->id, 'price' => 150.00]);
        $vanWarehouse = Warehouse::factory()->create(['company_id' => $company->id, 'type' => 'van', 'user_id' => $seller->id]);

        app(StockService::class)->increment(
            $vanWarehouse->id, $product->id, null, 50.0, StockReason::Initial, $product,
        );

        $invoice = app(InvoiceService::class)->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'user_id' => $seller->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2.0, 'unit_price' => 150.00],
            ],
        ]);

        $qrService = app(\App\Services\InvoiceQrService::class);
        $qrData = $qrService->generateForInvoice($invoice);

        // Egypt QR format: invoice_number|total
        $this->assertNotEmpty($qrData);
        $this->assertStringContainsString($invoice->invoice_number, $qrData);
        $this->assertStringContainsString($invoice->total, $qrData);

        // Snapshot preserves the values used for QR generation
        $this->assertSame('شركة زاتكا', $invoice->snapshot_company['name_ar']);
        $this->assertSame('987654321', $invoice->snapshot_company['tax_number']);
    }
}
