<?php

namespace Tests\Feature\Finance;

use App\Enums\StockReason;
use App\Exceptions\Domain\DomainException;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Contracts\StockService;
use App\Services\InvoiceService;
use App\Services\PricingService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthoritativePricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_rejects_tampered_price_and_uses_effective_customer_override(): void
    {
        $this->seed(RoleSeeder::class);
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $manager = User::factory()->create(['company_id' => $company->id]);
        $manager->assignRole('sales_manager');
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id, 'price' => 100]);
        $van = Warehouse::factory()->create([
            'company_id' => $company->id,
            'type' => 'van',
            'user_id' => $rep->id,
        ]);
        app(StockService::class)->increment($van->id, $product->id, null, 10, StockReason::Initial, $product);
        app(PricingService::class)->createCustomerOverride(
            $manager,
            $company->id,
            $customer->id,
            $product->id,
            '90.00',
            today(),
            today()->addWeek(),
            'Contract price approved by sales director',
        );
        $this->actingAs($rep);

        try {
            app(InvoiceService::class)->create([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 1]],
            ]);
            $this->fail('Tampered price was accepted.');
        } catch (DomainException) {
            $this->assertDatabaseCount('invoices', 0);
        }

        $invoice = app(InvoiceService::class)->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 90]],
        ]);

        $this->assertSame('90.00', $invoice->items()->firstOrFail()->unit_price);
        $this->assertDatabaseHas('product_prices', [
            'customer_id' => $customer->id,
            'price' => '90.00',
            'reason' => 'Contract price approved by sales director',
            'created_by' => $manager->id,
        ]);
    }

    public function test_non_manager_cannot_create_price_override(): void
    {
        $this->seed(RoleSeeder::class);
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id]);

        $this->expectException(DomainException::class);
        app(PricingService::class)->createCustomerOverride(
            $rep,
            $company->id,
            $customer->id,
            $product->id,
            '50.00',
            today(),
            today()->addDay(),
            'Unauthorized',
        );
    }
}
