<?php

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Models\Customer;
use App\Models\PriceList;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use App\Services\PricingService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_effective_price_returns_product_base_when_no_pricelist(): void
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id, 'price' => 150.50]);

        $price = app(PricingService::class)->effectivePrice(
            $company->id,
            $customer->id,
            $product->id,
            '1.000',
        );

        $this->assertSame('150.50', $price);
    }

    public function test_effective_price_respects_pricelist_override(): void
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id, 'price' => 100]);
        $priceList = PriceList::create([
            'company_id' => $company->id,
            'type' => 'selling',
            'name' => 'Wholesale',
            'is_active' => true,
            'is_default' => false,
        ]);
        ProductPrice::create([
            'product_id' => $product->id,
            'price_list_id' => $priceList->id,
            'price' => 85.00,
            'min_quantity' => 10,
            'is_active' => true,
        ]);

        $price = app(PricingService::class)->effectivePrice(
            $company->id,
            $customer->id,
            $product->id,
            '15.000',
        );

        $this->assertSame('85.00', $price);
    }

    public function test_effective_price_returns_base_when_quantity_below_minimum(): void
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id, 'price' => 100]);
        $priceList = PriceList::create([
            'company_id' => $company->id,
            'type' => 'selling',
            'name' => 'Wholesale',
            'is_active' => true,
            'is_default' => false,
        ]);
        ProductPrice::create([
            'product_id' => $product->id,
            'price_list_id' => $priceList->id,
            'price' => 85.00,
            'min_quantity' => 10,
            'is_active' => true,
        ]);

        $price = app(PricingService::class)->effectivePrice(
            $company->id,
            $customer->id,
            $product->id,
            '5.000',
        );

        $this->assertSame('100.00', $price);
    }

    public function test_effective_price_throws_for_cross_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $companyA->id]);
        $product = Product::factory()->create(['company_id' => $companyB->id, 'price' => 100]);

        $this->expectException(\App\Exceptions\Domain\DomainException::class);
        app(PricingService::class)->effectivePrice(
            $companyA->id,
            $customer->id,
            $product->id,
            '1.000',
        );
    }

    public function test_range_for_rep_returns_zero_for_missing_product(): void
    {
        $range = app(PricingService::class)->rangeForRep(99999, 1);

        $this->assertSame('0.00', $range->base->toDecimal());
    }

    public function test_price_for_rep_validates_within_range(): void
    {
        $company = Company::factory()->create(['rep_discount_percent' => 10]);
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $product = Product::factory()->create(['company_id' => $company->id, 'price' => 100]);

        // Base price (100) should be valid
        $this->assertTrue(app(PricingService::class)->priceForRep($product->id, $rep->id, '100.00'));
        // Below floor should be invalid (floor = 100 - 10% = 90)
        $this->assertFalse(app(PricingService::class)->priceForRep($product->id, $rep->id, '80.00'));
    }
}
