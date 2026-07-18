<?php

namespace Tests\Unit\Services;

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
            \App\Enums\StockReason::Initial, $product,
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
            \App\Enums\StockReason::Initial, $product,
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

    public function test_create_return_works_without_van_warehouse(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'balance' => 200]);
        $product = Product::factory()->create(['company_id' => $company->id]);

        $return = app(ReturnService::class)->create(
            companyId: $company->id,
            userId: $rep->id,
            customerId: $customer->id,
            items: [['product_id' => $product->id, 'quantity' => 2.0, 'unit_price' => 50.0]],
        );

        $this->assertSame('submitted', $return->status);
        $this->assertSame(200.0 - 100.0, (float) $customer->fresh()->balance);
        $this->assertCount(1, $return->items);
    }
}
