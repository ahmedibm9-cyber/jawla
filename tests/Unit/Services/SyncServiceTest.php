<?php

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Models\User;
use App\Services\Sync\Contracts\SyncHandler;
use App\Services\Sync\SyncHandlerRegistry;
use App\Services\Sync\SyncService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SyncServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeService(): array
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');

        $registry = Mockery::mock(SyncHandlerRegistry::class);
        $service = new SyncService($registry);

        return compact('company', 'rep', 'registry', 'service');
    }

    private function mockHandler(string $type, array $result = ['ok' => true]): SyncHandler
    {
        $handler = Mockery::mock(SyncHandler::class);
        $handler->shouldReceive('type')->andReturn($type);
        $handler->shouldReceive('handle')->andReturn($result);

        return $handler;
    }

    public function test_applies_new_operation(): void
    {
        ['rep' => $rep, 'registry' => $registry, 'service' => $service] = $this->makeService();

        $handler = $this->mockHandler('sale', ['invoice_id' => 42]);

        $registry->shouldReceive('has')->with('sale')->andReturn(true);
        $registry->shouldReceive('get')->with('sale')->andReturn($handler);

        $results = $service->process($rep, [
            ['key' => 'key-1', 'type' => 'sale', 'payload' => ['amount' => 100]],
        ]);

        $this->assertCount(1, $results);
        $this->assertEquals('applied', $results[0]['status']);
        $this->assertEquals(['invoice_id' => 42], $results[0]['result']);
    }

    public function test_returns_duplicate_on_replay(): void
    {
        ['rep' => $rep, 'registry' => $registry, 'service' => $service] = $this->makeService();

        $handler = $this->mockHandler('sale');

        $registry->shouldReceive('has')->with('sale')->andReturn(true);
        $registry->shouldReceive('get')->with('sale')->andReturn($handler);

        $op = ['key' => 'key-1', 'type' => 'sale', 'payload' => ['amount' => 100]];

        $results1 = $service->process($rep, [$op]);
        $this->assertEquals('applied', $results1[0]['status']);

        $results2 = $service->process($rep, [$op]);
        $this->assertEquals('duplicate', $results2[0]['status']);
    }

    public function test_returns_unsupported_for_unknown_type(): void
    {
        ['rep' => $rep, 'registry' => $registry, 'service' => $service] = $this->makeService();

        $registry->shouldReceive('has')->with('nonexistent')->andReturn(false);

        $results = $service->process($rep, [
            ['key' => 'key-1', 'type' => 'nonexistent', 'payload' => []],
        ]);

        $this->assertEquals('unsupported', $results[0]['status']);
    }

    public function test_returns_invalid_for_missing_key(): void
    {
        ['rep' => $rep, 'service' => $service] = $this->makeService();

        $results = $service->process($rep, [
            ['type' => 'sale', 'payload' => []],
        ]);

        $this->assertEquals('invalid', $results[0]['status']);
    }

    public function test_returns_invalid_for_missing_type(): void
    {
        ['rep' => $rep, 'service' => $service] = $this->makeService();

        $results = $service->process($rep, [
            ['key' => 'key-1', 'payload' => []],
        ]);

        $this->assertEquals('invalid', $results[0]['status']);
    }

    public function test_stores_receipt_on_apply(): void
    {
        ['company' => $company, 'rep' => $rep, 'registry' => $registry, 'service' => $service] = $this->makeService();

        $handler = $this->mockHandler('sale', ['invoice_id' => 42]);

        $registry->shouldReceive('has')->with('sale')->andReturn(true);
        $registry->shouldReceive('get')->with('sale')->andReturn($handler);

        $service->process($rep, [
            ['key' => 'key-1', 'type' => 'sale', 'payload' => ['amount' => 100]],
        ]);

        $this->assertDatabaseHas('sync_receipts', [
            'company_id' => $company->id,
            'user_id' => $rep->id,
            'idempotency_key' => 'key-1',
            'operation_type' => 'sale',
        ]);
    }

    public function test_process_multiple_operations(): void
    {
        ['rep' => $rep, 'registry' => $registry, 'service' => $service] = $this->makeService();

        $saleHandler = $this->mockHandler('sale', ['ok' => true]);
        $returnHandler = $this->mockHandler('return', ['ok' => true]);

        $registry->shouldReceive('has')->with('sale')->andReturn(true);
        $registry->shouldReceive('has')->with('return')->andReturn(true);
        $registry->shouldReceive('has')->with('unsupported')->andReturn(false);
        $registry->shouldReceive('get')->with('sale')->andReturn($saleHandler);
        $registry->shouldReceive('get')->with('return')->andReturn($returnHandler);

        $results = $service->process($rep, [
            ['key' => 'key-1', 'type' => 'sale', 'payload' => []],
            ['key' => 'key-2', 'type' => 'return', 'payload' => []],
            ['key' => 'key-3', 'type' => 'unsupported', 'payload' => []],
        ]);

        $this->assertCount(3, $results);
        $this->assertEquals('applied', $results[0]['status']);
        $this->assertEquals('applied', $results[1]['status']);
        $this->assertEquals('unsupported', $results[2]['status']);
    }

    public function test_payload_hash_mismatch_returns_mismatch(): void
    {
        ['rep' => $rep, 'registry' => $registry, 'service' => $service] = $this->makeService();

        $handler = $this->mockHandler('sale');

        $registry->shouldReceive('has')->with('sale')->andReturn(true);
        $registry->shouldReceive('get')->with('sale')->andReturn($handler);

        $service->process($rep, [
            ['key' => 'key-1', 'type' => 'sale', 'payload' => [], 'payload_hash' => 'abc123'],
        ]);

        $results = $service->process($rep, [
            ['key' => 'key-1', 'type' => 'sale', 'payload' => [], 'payload_hash' => 'different'],
        ]);

        $this->assertEquals('mismatch', $results[0]['status']);
    }
}
