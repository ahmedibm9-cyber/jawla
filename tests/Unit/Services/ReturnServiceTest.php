<?php

namespace Tests\Unit\Services;

use App\Enums\StockReason;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Contracts\StockService;
use App\Services\ReturnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReturnServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_return_restores_stock_and_reduces_balance(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $van = Warehouse::factory()->create([
            'company_id' => $company->id, 'type' => 'van', 'user_id' => $rep->id,
        ]);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'balance' => 500]);
        $product = Product::factory()->create(['company_id' => $company->id]);

        app(StockService::class)->increment(
            $van->id, $product->id, null, 50.0,
            StockReason::Initial, $product,
        );

        $stockBefore = app(StockService::class)->balance($van->id, $product->id);
        $this->assertSame(50.0, $stockBefore);

        $return = app(ReturnService::class)->create(
            companyId: $company->id,
            userId: $rep->id,
            customerId: $customer->id,
            items: [['product_id' => $product->id, 'quantity' => 5.0, 'unit_price' => 100.0]],
            reason: 'damaged goods',
        );

        $stockAfter = app(StockService::class)->balance($van->id, $product->id);
        $this->assertSame(55.0, $stockAfter);
        $this->assertSame(500.0 - 500.0, (float) $customer->fresh()->balance);
        $this->assertSame(500.0, (float) $return->total);
        $this->assertSame('submitted', $return->status);
        $this->assertNotNull($return->return_number);
        $this->assertCount(1, $return->items);
    }

    public function test_cancel_return_reverses_stock_and_restores_balance(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $van = Warehouse::factory()->create([
            'company_id' => $company->id, 'type' => 'van', 'user_id' => $rep->id,
        ]);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'balance' => 300]);
        $product = Product::factory()->create(['company_id' => $company->id]);

        app(StockService::class)->increment(
            $van->id, $product->id, null, 20.0,
            StockReason::Initial, $product,
        );

        $return = app(ReturnService::class)->create(
            companyId: $company->id,
            userId: $rep->id,
            customerId: $customer->id,
            items: [['product_id' => $product->id, 'quantity' => 3.0, 'unit_price' => 50.0]],
        );

        $stockAfterReturn = app(StockService::class)->balance($van->id, $product->id);
        $this->assertSame(23.0, $stockAfterReturn);
        $this->assertSame(300.0 - 150.0, (float) $customer->fresh()->balance);

        app(ReturnService::class)->cancel($return, $rep->id, 'test cancel');

        $stockAfterCancel = app(StockService::class)->balance($van->id, $product->id);
        $this->assertSame(20.0, $stockAfterCancel);
        $this->assertSame(300.0, (float) $customer->fresh()->balance);
        $this->assertSame('cancelled', $return->fresh()->status);
        $this->assertNotNull($return->fresh()->cancelled_at);
    }

    public function test_create_return_requires_a_van_warehouse_before_creating_any_financial_record(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'balance' => 200]);
        $product = Product::factory()->create(['company_id' => $company->id]);

        try {
            app(ReturnService::class)->create(
                companyId: $company->id,
                userId: $rep->id,
                customerId: $customer->id,
                items: [['product_id' => $product->id, 'quantity' => 2.0, 'unit_price' => 50.0]],
            );
            $this->fail('Expected a return without a van warehouse to be rejected.');
        } catch (\DomainException) {
        }

        $this->assertDatabaseCount('returns', 0);
        $this->assertSame(200.0, (float) $customer->fresh()->balance);
    }

    public function test_create_return_rejects_when_total_exceeds_customer_balance(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $van = Warehouse::factory()->create([
            'company_id' => $company->id, 'type' => 'van', 'user_id' => $rep->id,
        ]);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'balance' => 100]);
        $product = Product::factory()->create(['company_id' => $company->id]);

        app(StockService::class)->increment(
            $van->id, $product->id, null, 50.0,
            StockReason::Initial, $product,
        );

        $this->expectException(\App\Exceptions\Domain\DomainException::class);
        app(ReturnService::class)->create(
            companyId: $company->id,
            userId: $rep->id,
            customerId: $customer->id,
            items: [['product_id' => $product->id, 'quantity' => 5.0, 'unit_price' => 50.0]],
        );
    }

    public function test_create_return_rejects_foreign_customer_and_product_before_any_write(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        Warehouse::factory()->create(['company_id' => $company->id, 'type' => 'van', 'user_id' => $rep->id]);
        $foreignCompany = Company::factory()->create();
        $foreignCustomer = Customer::factory()->create(['company_id' => $foreignCompany->id, 'balance' => 500]);
        $foreignProduct = Product::factory()->create(['company_id' => $foreignCompany->id]);

        $this->expectException(\App\Exceptions\Domain\DomainException::class);
        try {
            app(ReturnService::class)->create(
                companyId: $company->id,
                userId: $rep->id,
                customerId: $foreignCustomer->id,
                items: [['product_id' => $foreignProduct->id, 'quantity' => 1.0, 'unit_price' => 50.0]],
            );
        } finally {
            $this->assertDatabaseCount('returns', 0);
            $this->assertDatabaseCount('return_items', 0);
        }
    }
}
