<?php

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Models\OutOfStockRequest;
use App\Models\Product;
use App\Models\User;
use App\Services\Contracts\AlarmService;
use App\Services\OutOfStockService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutOfStockServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_raise_creates_open_request(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $product = Product::factory()->create(['company_id' => $company->id]);

        $this->mock(AlarmService::class, fn ($mock) => $mock->shouldReceive('raise')->once());

        $request = app(OutOfStockService::class)->raise($rep, $product->id, 5.0, null, 'Need more');

        $this->assertSame('open', $request->status);
        $this->assertSame($company->id, $request->company_id);
        $this->assertSame($rep->id, $request->user_id);
        $this->assertSame($product->id, $request->product_id);
        $this->assertSame('5.000', $request->quantity_requested);
    }

    public function test_raise_is_idempotent_for_existing_open_request(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $product = Product::factory()->create(['company_id' => $company->id]);

        $this->mock(AlarmService::class, fn ($mock) => $mock->shouldReceive('raise')->once());

        $service = app(OutOfStockService::class);
        $first = $service->raise($rep, $product->id, 5.0);
        $second = $service->raise($rep, $product->id, 10.0);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, OutOfStockRequest::where('user_id', $rep->id)->where('product_id', $product->id)->where('status', 'open')->count());
    }

    public function test_raise_raises_alarm(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $product = Product::factory()->create(['company_id' => $company->id]);

        $alarmSpy = \Mockery::mock(AlarmService::class);
        $alarmSpy->shouldReceive('raise')
            ->once()
            ->with(
                'out_of_stock_request',
                \Mockery::type(OutOfStockRequest::class),
                \Mockery::on(fn (string $title) => str_contains($title, 'Out of stock')),
                \Mockery::on(fn (string $msg) => str_contains($msg, $product->sku)),
                'critical',
            );
        app()->instance(AlarmService::class, $alarmSpy);

        app(OutOfStockService::class)->raise($rep, $product->id, 2.0);
    }

    public function test_resolve_sets_fulfilled_status(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $product = Product::factory()->create(['company_id' => $company->id]);

        $this->mock(AlarmService::class, fn ($mock) => $mock->shouldReceive('raise')->once());

        $request = app(OutOfStockService::class)->raise($rep, $product->id, 3.0);
        $resolved = app(OutOfStockService::class)->resolve($request, $rep->id);

        $this->assertSame('fulfilled', $resolved->status);
    }

    public function test_resolve_does_nothing_for_non_open_status(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $product = Product::factory()->create(['company_id' => $company->id]);

        $this->mock(AlarmService::class, fn ($mock) => $mock->shouldReceive('raise')->once());

        $request = app(OutOfStockService::class)->raise($rep, $product->id, 1.0);
        $request->update(['status' => 'fulfilled']);

        $result = app(OutOfStockService::class)->resolve($request, $rep->id);

        $this->assertSame('fulfilled', $result->status);
    }
}
