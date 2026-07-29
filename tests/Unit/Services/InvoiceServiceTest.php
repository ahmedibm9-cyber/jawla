<?php

namespace Tests\Unit\Services;

use App\Enums\InvoiceStatus;
use App\Enums\StockReason;
use App\Exceptions\Domain\DomainException;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Contracts\StockService;
use App\Services\InvoiceService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_create_builds_invoice_with_correct_totals(): void
    {
        $company = Company::factory()->create(['vat_percent' => 14]);
        $rep = $this->salesRep($company);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'status' => 'approved']);
        $product = Product::factory()->create(['company_id' => $company->id, 'price' => 100]);
        $van = Warehouse::factory()->create(['company_id' => $company->id, 'type' => 'van', 'user_id' => $rep->id, 'is_active' => true]);

        $this->actingAs($rep);
        app(StockService::class)->increment($van->id, $product->id, null, 10, StockReason::Initial, $product);

        $invoice = app(InvoiceService::class)->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 100,
        ]);

        $this->assertSame(InvoiceStatus::Issued, $invoice->status);
        $this->assertNotNull($invoice->invoice_number);
        $this->assertSame(200.0, (float) $invoice->subtotal);
        $this->assertSame(28.0, (float) $invoice->vat_amount);
        $this->assertSame(228.0, (float) $invoice->total);
        $this->assertSame(228.0, (float) $invoice->remaining_amount);
        $this->assertSame(0.0, (float) $invoice->paid_amount);
    }

    public function test_create_decrements_van_stock(): void
    {
        $company = Company::factory()->create(['vat_percent' => 0]);
        $rep = $this->salesRep($company);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'status' => 'approved']);
        $product = Product::factory()->create(['company_id' => $company->id, 'price' => 50]);
        $van = Warehouse::factory()->create(['company_id' => $company->id, 'type' => 'van', 'user_id' => $rep->id, 'is_active' => true]);

        $this->actingAs($rep);
        app(StockService::class)->increment($van->id, $product->id, null, 10, StockReason::Initial, $product);

        app(InvoiceService::class)->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 50,
        ]);

        $stock = Stock::where('warehouse_id', $van->id)->where('product_id', $product->id)->first();
        $this->assertSame(7.0, (float) $stock->quantity);
    }

    public function test_create_increments_customer_balance(): void
    {
        $company = Company::factory()->create(['vat_percent' => 0]);
        $rep = $this->salesRep($company);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'balance' => 0, 'status' => 'approved']);
        $product = Product::factory()->create(['company_id' => $company->id, 'price' => 200]);
        $van = Warehouse::factory()->create(['company_id' => $company->id, 'type' => 'van', 'user_id' => $rep->id, 'is_active' => true]);

        $this->actingAs($rep);
        app(StockService::class)->increment($van->id, $product->id, null, 10, StockReason::Initial, $product);

        app(InvoiceService::class)->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 200,
        ]);

        $this->assertSame(200.0, (float) $customer->fresh()->balance);
    }

    public function test_create_rejects_pending_customer(): void
    {
        $company = Company::factory()->create();
        $rep = $this->salesRep($company);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'status' => 'pending']);
        $product = Product::factory()->create(['company_id' => $company->id, 'price' => 100]);
        Warehouse::factory()->create(['company_id' => $company->id, 'type' => 'van', 'user_id' => $rep->id, 'is_active' => true]);

        $this->actingAs($rep);

        $this->expectException(DomainException::class);
        app(InvoiceService::class)->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100,
        ]);
    }

    public function test_create_rejects_without_van_warehouse(): void
    {
        $company = Company::factory()->create();
        $rep = $this->salesRep($company);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'status' => 'approved']);
        $product = Product::factory()->create(['company_id' => $company->id, 'price' => 100]);

        $this->actingAs($rep);

        $this->expectException(\DomainException::class);
        app(InvoiceService::class)->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100,
        ]);
    }

    public function test_cancel_voids_invoice_and_restores_stock(): void
    {
        $company = Company::factory()->create(['vat_percent' => 0]);
        $rep = $this->salesRep($company);
        $rep->assignRole('sales_manager');
        $customer = Customer::factory()->create(['company_id' => $company->id, 'status' => 'approved']);
        $product = Product::factory()->create(['company_id' => $company->id, 'price' => 100]);
        $van = Warehouse::factory()->create(['company_id' => $company->id, 'type' => 'van', 'user_id' => $rep->id, 'is_active' => true]);

        $this->actingAs($rep);
        app(StockService::class)->increment($van->id, $product->id, null, 10, StockReason::Initial, $product);

        $invoice = app(InvoiceService::class)->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 100,
        ]);

        $result = app(InvoiceService::class)->cancel($invoice, $rep->id, 'Customer returned items');

        $this->assertSame(InvoiceStatus::Voided, $result->status);
        $this->assertNotNull($result->cancelled_at);

        $stock = Stock::where('warehouse_id', $van->id)->where('product_id', $product->id)->first();
        $this->assertSame(10.0, (float) $stock->quantity);
    }

    public function test_cancel_reverses_customer_balance(): void
    {
        $company = Company::factory()->create(['vat_percent' => 0]);
        $rep = $this->salesRep($company);
        $rep->assignRole('sales_manager');
        $customer = Customer::factory()->create(['company_id' => $company->id, 'balance' => 0, 'status' => 'approved']);
        $product = Product::factory()->create(['company_id' => $company->id, 'price' => 100]);
        $van = Warehouse::factory()->create(['company_id' => $company->id, 'type' => 'van', 'user_id' => $rep->id, 'is_active' => true]);

        $this->actingAs($rep);
        app(StockService::class)->increment($van->id, $product->id, null, 10, StockReason::Initial, $product);

        $invoice = app(InvoiceService::class)->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100,
        ]);

        $this->assertSame(100.0, (float) $customer->fresh()->balance);

        app(InvoiceService::class)->cancel($invoice, $rep->id, 'Test cancel');

        $this->assertSame(0.0, (float) $customer->fresh()->balance);
    }
}
