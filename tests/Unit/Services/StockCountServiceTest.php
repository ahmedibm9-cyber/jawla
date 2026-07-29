<?php

use App\Exceptions\Domain\DomainException;
use App\Models\Company;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Contracts\StockService;
use App\Services\StockCountService;
use App\Support\ActiveCompanyContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->company = Company::factory()->create();
    $this->warehouse = Warehouse::factory()->create(['company_id' => $this->company->id, 'type' => 'van']);
    $this->product = Product::factory()->create(['company_id' => $this->company->id]);

    $this->stockMock = Mockery::mock(StockService::class);
    $this->stockMock->shouldReceive('balance')->andReturn(10.0);
    $this->stockMock->shouldReceive('reconcile')->andReturn(
        new StockMovement([
            'id' => 1,
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity_change' => 0,
        ])
    );
    $this->app->instance(StockService::class, $this->stockMock);

    app(ActiveCompanyContext::class)->setCompanyId($this->company->id);
});

afterEach(function () {
    Mockery::close();
});

test('open creates session with items', function () {
    $keeper = User::factory()->create(['company_id' => $this->company->id]);
    $keeper->assignRole('warehouse_keeper');

    $session = app(StockCountService::class)->open(
        $this->warehouse,
        $keeper,
        [$this->product->id],
    );

    $this->assertSame('counting', $session->status);
    $this->assertSame($this->warehouse->id, $session->warehouse_id);
    $this->assertSame($keeper->id, $session->opened_by);
    $this->assertCount(1, $session->items);
    $this->assertSame($this->product->id, $session->items->first()->product_id);
    $this->assertSame('10.000', $session->items->first()->expected_quantity);
});

test('open rejects unauthorized user', function () {
    $keeper = User::factory()->create(['company_id' => $this->company->id]);
    $keeper->assignRole('sales_rep');

    $this->expectException(DomainException::class);

    app(StockCountService::class)->open(
        $this->warehouse,
        $keeper,
        [$this->product->id],
    );
});

test('record sets physical quantity', function () {
    $keeper = User::factory()->create(['company_id' => $this->company->id]);
    $keeper->assignRole('warehouse_keeper');

    $session = app(StockCountService::class)->open(
        $this->warehouse,
        $keeper,
        [$this->product->id],
    );

    $item = app(StockCountService::class)->record(
        $session,
        $session->items->first()->id,
        '12.500',
        $keeper,
    );

    $this->assertSame('12.500', $item->physical_quantity);
    $this->assertNotNull($item->variance);
});

test('record rejects negative quantity', function () {
    $keeper = User::factory()->create(['company_id' => $this->company->id]);
    $keeper->assignRole('warehouse_keeper');

    $session = app(StockCountService::class)->open(
        $this->warehouse,
        $keeper,
        [$this->product->id],
    );

    $this->expectException(DomainException::class);

    app(StockCountService::class)->record(
        $session,
        $session->items->first()->id,
        '-1.000',
        $keeper,
    );
});

test('submit requires all lines recorded', function () {
    $keeper = User::factory()->create(['company_id' => $this->company->id]);
    $keeper->assignRole('warehouse_keeper');

    $session = app(StockCountService::class)->open(
        $this->warehouse,
        $keeper,
        [$this->product->id],
    );

    $this->expectException(DomainException::class);

    app(StockCountService::class)->submit($session, $keeper, 'routine count');
});

test('approve and apply reconciles stock', function () {
    $keeper = User::factory()->create(['company_id' => $this->company->id]);
    $keeper->assignRole('warehouse_keeper');
    $manager = User::factory()->create(['company_id' => $this->company->id]);
    $manager->assignRole('sales_manager');

    $session = app(StockCountService::class)->open(
        $this->warehouse,
        $keeper,
        [$this->product->id],
    );

    app(StockCountService::class)->record(
        $session,
        $session->items->first()->id,
        '9.500',
        $keeper,
    );

    app(StockCountService::class)->submit($session, $keeper, 'stock adjustment');

    $result = app(StockCountService::class)->approveAndApply(
        $session->fresh(),
        $manager,
    );

    $this->assertSame('applied', $result->status);
    $this->assertSame($manager->id, $result->approved_by);
    $this->assertNotNull($result->approved_at);
    $this->assertNotNull($result->applied_at);
});
